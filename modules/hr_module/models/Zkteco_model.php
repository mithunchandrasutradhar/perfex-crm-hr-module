<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Zkteco_model extends App_Model
{
    private $devices_table  = 'hr_zkteco_devices';
    private $mapping_table  = 'hr_zkteco_mapping';
    private $logs_table     = 'hr_zkteco_sync_logs';

    // A device with a clock that's drifted/misconfigured by a few hours
    // (e.g. a firmware timezone quirk) can push an otherwise-legitimate
    // same-moment punch under yesterday's or tomorrow's date. Backlog dumps
    // from a newly-connected/previously-offline device are days or weeks
    // old, so a several-hour tolerance still rejects real backlog while no
    // longer discarding a genuine punch just because the device's clock is
    // off by a couple of hours.
    private const BACKLOG_TOLERANCE_SECONDS = 6 * 3600;

    // ── Devices ──────────────────────────────────────────────────────────────

    public function get_device($id)
    {
        return $this->db->where('id', $id)
            ->get(db_prefix() . $this->devices_table)->row();
    }

    public function get_devices($active_only = false)
    {
        if ($active_only) $this->db->where('status', 1);
        return $this->db->order_by('name', 'ASC')
            ->get(db_prefix() . $this->devices_table)->result();
    }

    public function find_device_by_serial($serial_number)
    {
        return $this->db->where('serial_number', $serial_number)
            ->get(db_prefix() . $this->devices_table)->row();
    }

    public function add_device($data)
    {
        $record = [
            'name'          => $data['name'],
            'device_type'   => $data['device_type'] ?? 'zkteco',
            'ip_address'    => $data['ip_address'],
            'port'          => (int) ($data['port'] ?? 4370),
            'serial_number' => $data['serial_number'] ?? null,
            'location'      => $data['location'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'status'        => 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . $this->devices_table, $record);
        $id = $this->db->insert_id();
        if ($id) {
            log_activity('HR ZKTeco Device Added [ID: ' . $id . ', Name: ' . $data['name'] . ']');
        }
        return $id ? ['success' => true, 'id' => $id, 'message' => _l('hr_zkteco_device_added')]
                   : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function update_device($data, $id)
    {
        $this->db->where('id', $id)->update(db_prefix() . $this->devices_table, [
            'name'          => $data['name'],
            'device_type'   => $data['device_type'] ?? 'zkteco',
            'ip_address'    => $data['ip_address'],
            'port'          => (int) ($data['port'] ?? 4370),
            'serial_number' => $data['serial_number'] ?? null,
            'location'      => $data['location'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'status'        => $data['status'] ?? 1,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR ZKTeco Device Updated [ID: ' . $id . ']');
        return ['success' => true, 'message' => _l('hr_zkteco_device_updated')];
    }

    public function delete_device($id)
    {
        $this->db->where('device_id', $id)->delete(db_prefix() . $this->mapping_table);
        $this->db->where('device_id', $id)->delete(db_prefix() . $this->logs_table);
        $this->db->where('id', $id)->delete(db_prefix() . $this->devices_table);
        log_activity('HR ZKTeco Device Deleted [ID: ' . $id . ']');
        return ['success' => true, 'message' => _l('hr_zkteco_device_deleted')];
    }

    // ── ADMS Push Ingestion ──────────────────────────────────────────────────

    // Called on every authorized /iclock/* hit (handshake, heartbeat, push,
    // command ack) so the admin UI can show when the device was last heard
    // from - there's no outbound connection to the device to "test" anymore.
    public function record_contact($device_id)
    {
        $this->db->where('id', $device_id)->update(db_prefix() . $this->devices_table, [
            'last_sync_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // Hardcoded per readme.md's Production Security Requirements (100
    // req/min/device). Counts pushes logged in log_push() over the last
    // 60 seconds for this device.
    public function rate_limit_ok($device_id, $max_per_minute = 100)
    {
        $since = date('Y-m-d H:i:s', time() - 60);
        $count = $this->db->where('device_id', $device_id)
            ->where('sync_at >=', $since)
            ->count_all_results(db_prefix() . $this->logs_table);
        return $count < $max_per_minute;
    }

    // Maps the ATTLOG verify-mode code to a human label. readme.md's own
    // Verification Modes table (0/3=Card, 1=Fingerprint, 2=Password,
    // 15=Face) is incomplete for this device's actual firmware, which was
    // confirmed sending 4 for a real ID Card scan - kept alongside 0/3
    // rather than replacing them, since other devices/firmware may still
    // use those. An RFID tag/fob is read through the same physical reader
    // as an ID card - the device can't tell the two apart, so a tag punch
    // is expected to register under this same code too.
    private function _verify_mode_label($code)
    {
        $map = ['1' => 'Fingerprint', '15' => 'Face', '0' => 'ID Card', '3' => 'ID Card', '4' => 'ID Card', '2' => 'Password'];
        return $map[(string) $code] ?? ('Code ' . $code);
    }

    // Parses the raw tab-separated ATTLOG body pushed by the device (one
    // punch per line: device_user_id, timestamp, state, verify_mode, ...).
    //
    // Every individual punch is kept in hr_zkteco_punches (for the
    // attendance list's "View Log" popup). hr_attendance itself only ever
    // stores one row per employee+date: in_time is the first punch ever
    // seen for that day (set once, never overwritten by later batches),
    // out_time is always the latest punch seen so far - an employee can
    // step out and back any number of times during the day and the last
    // punch always wins, across any number of separate push batches.
    public function save_attlog_batch($device_id, array $lines)
    {
        $this->load->model('hr_module/Attendance_model');
        $saved = 0;

        // Group by employee+date and sort chronologically first - a
        // single push batch isn't guaranteed to list punches in order.
        //
        // Uses the server's own clock, not the timestamp the device embeds
        // in the line (cols[1]) - a device's internal clock can drift or be
        // misconfigured, and the server receiving the push in real time is
        // the more trustworthy source of "when this actually happened".
        // Each line gets its own second, offset by its position in the
        // batch, so a rare multi-punch backlog (several lines in one push)
        // still sorts in arrival order instead of collapsing onto one
        // identical instant.
        $groups   = [];
        $now      = time();
        $line_num = 0;
        foreach ($lines as $line) {
            $cols = preg_split('/\t+/', trim($line));
            if (count($cols) < 2) { $line_num++; continue; }

            $device_user_id = $cols[0];
            $device_ts_raw  = $cols[1] ?? null;
            $verify_mode    = $cols[3] ?? null;

            // A newly-connected (or previously-used-elsewhere) device
            // typically pushes its entire stored history on first contact -
            // that backlog is not wanted here, so anything not within
            // BACKLOG_TOLERANCE_SECONDS of right now (per the device's own
            // embedded timestamp) is dropped before it's ever recorded.
            // Once accepted, the value actually stored still comes from the
            // server's clock (below), not the device's, to guard against a
            // merely-drifted/misconfigured clock on an otherwise-legitimate
            // punch.
            $device_ts = $device_ts_raw ? strtotime($device_ts_raw) : false;
            if ($device_ts === false || abs($device_ts - $now) > self::BACKLOG_TOLERANCE_SECONDS) { $line_num++; continue; }

            $employee_id = $this->resolve_employee($device_id, $device_user_id);
            if (!$employee_id) { $line_num++; continue; }

            $ts = $now + $line_num;
            $line_num++;

            $date = date('Y-m-d', $ts);
            $groups[$employee_id . '|' . $date][] = [
                'employee_id'  => $employee_id,
                'date'         => $date,
                'time'         => date('H:i:s', $ts),
                'verify_mode'  => $verify_mode,
            ];
        }

        foreach ($groups as $punches) {
            usort($punches, function ($a, $b) { return strcmp($a['time'], $b['time']); });

            $employee_id = $punches[0]['employee_id'];
            $date        = $punches[0]['date'];
            $existing = $this->db
                ->where('employee_id', $employee_id)
                ->where('attendance_date', $date)
                ->get(db_prefix() . 'hr_attendance')->row();

            foreach ($punches as $p) {
                $verify_label = $this->_verify_mode_label($p['verify_mode']);

                $this->db->insert(db_prefix() . 'hr_zkteco_punches', [
                    'employee_id'     => $employee_id,
                    'attendance_date' => $date,
                    'punch_time'      => $p['time'],
                    'device_id'       => $device_id,
                    'verify_mode'     => $verify_label,
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);

                if (!$existing) {
                    $resolved = $this->Attendance_model->resolve_status_and_hours($employee_id, $date, $p['time'], null);
                    $this->db->insert(db_prefix() . 'hr_attendance', [
                        'employee_id'     => $employee_id,
                        'attendance_date' => $date,
                        'in_time'         => $p['time'],
                        'status'          => $resolved['status'],
                        'source'          => 'zkteco',
                        'verify_mode'     => $verify_label,
                        'device_id'       => $device_id,
                        'created_at'      => date('Y-m-d H:i:s'),
                    ]);
                    $saved++;
                    // Reflect the row we just inserted so later punches in
                    // this same group update it instead of inserting again.
                    $existing = (object) [
                        'id' => $this->db->insert_id(),
                        'in_time' => $p['time'],
                        'out_time' => null,
                    ];
                    continue;
                }

                if ($p['time'] > $existing->in_time) {
                    $new_out = $existing->out_time ? max($existing->out_time, $p['time']) : $p['time'];
                    if ($new_out !== $existing->out_time) {
                        $resolved = $this->Attendance_model->resolve_status_and_hours($employee_id, $date, $existing->in_time, $new_out);
                        // This punch is now the latest for the day (it just
                        // became out_time), so its verify method is what the
                        // Attendance list's Source column should show.
                        $this->db->where('id', $existing->id)->update(db_prefix() . 'hr_attendance', [
                            'out_time'      => $new_out,
                            'working_hours' => $resolved['working_hours'],
                            'verify_mode'   => $verify_label,
                        ]);
                        $existing->out_time = $new_out;
                        $saved++;
                    }
                }
            }
        }

        return $saved;
    }

    // Maps the AiFace protocol's log "mode" bit value (Appendix C of TIMY's
    // AiFace BS Communication Protocol doc) to the same human labels used
    // for ZKTeco punches, so the Attendance list's Source column and Punch
    // Log popup read identically regardless of which brand recorded them.
    // Per TIMMY's AI05 integration guide: 1=Fingerprint, 2=Password/PIN,
    // 3=RFID Card, 8=Face Recognition.
    private function _aiface_verify_mode_label($mode)
    {
        $map = [
            '1' => 'Fingerprint', '2' => 'Password', '3' => 'ID Card', '8' => 'Face Recognition',
        ];
        return $map[(string) $mode] ?? ('Code ' . $mode);
    }

    // Same shape/policies as save_attlog_batch() (server time not device
    // time, same-day-only staleness filter, first-punch-of-day=in_time,
    // latest-punch=out_time across any number of batches) applied to the
    // AiFace "sendlog" command's already-parsed JSON record array instead
    // of ZKTeco's raw tab-separated lines. Kept separate from
    // save_attlog_batch() rather than merged, so the live ZKTeco path is
    // never touched by this addition.
    public function save_aiface_log_batch($device_id, array $records)
    {
        $this->load->model('hr_module/Attendance_model');
        $saved = 0;

        $groups   = [];
        $now      = time();
        $line_num = 0;
        foreach ($records as $record) {
            $device_user_id = $record['enrollid'] ?? null;
            $device_ts_raw  = $record['time'] ?? null;
            $verify_mode    = $record['mode'] ?? null;
            if ($device_user_id === null || $device_user_id === '') { $line_num++; continue; }

            $device_ts = $device_ts_raw ? strtotime($device_ts_raw) : false;
            if ($device_ts === false || abs($device_ts - $now) > self::BACKLOG_TOLERANCE_SECONDS) { $line_num++; continue; }

            $employee_id = $this->resolve_employee($device_id, (string) $device_user_id);
            if (!$employee_id) { $line_num++; continue; }

            $ts = $now + $line_num;
            $line_num++;

            $date = date('Y-m-d', $ts);
            $groups[$employee_id . '|' . $date][] = [
                'employee_id'  => $employee_id,
                'date'         => $date,
                'time'         => date('H:i:s', $ts),
                'verify_mode'  => $verify_mode,
            ];
        }

        foreach ($groups as $punches) {
            usort($punches, function ($a, $b) { return strcmp($a['time'], $b['time']); });

            $employee_id = $punches[0]['employee_id'];
            $date        = $punches[0]['date'];
            $existing = $this->db
                ->where('employee_id', $employee_id)
                ->where('attendance_date', $date)
                ->get(db_prefix() . 'hr_attendance')->row();

            foreach ($punches as $p) {
                $verify_label = $this->_aiface_verify_mode_label($p['verify_mode']);

                $this->db->insert(db_prefix() . 'hr_zkteco_punches', [
                    'employee_id'     => $employee_id,
                    'attendance_date' => $date,
                    'punch_time'      => $p['time'],
                    'device_id'       => $device_id,
                    'verify_mode'     => $verify_label,
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);

                if (!$existing) {
                    $resolved = $this->Attendance_model->resolve_status_and_hours($employee_id, $date, $p['time'], null);
                    $this->db->insert(db_prefix() . 'hr_attendance', [
                        'employee_id'     => $employee_id,
                        'attendance_date' => $date,
                        'in_time'         => $p['time'],
                        'status'          => $resolved['status'],
                        'source'          => 'aiface',
                        'verify_mode'     => $verify_label,
                        'device_id'       => $device_id,
                        'created_at'      => date('Y-m-d H:i:s'),
                    ]);
                    $saved++;
                    $existing = (object) [
                        'id' => $this->db->insert_id(),
                        'in_time' => $p['time'],
                        'out_time' => null,
                    ];
                    continue;
                }

                if ($p['time'] > $existing->in_time) {
                    $new_out = $existing->out_time ? max($existing->out_time, $p['time']) : $p['time'];
                    if ($new_out !== $existing->out_time) {
                        $resolved = $this->Attendance_model->resolve_status_and_hours($employee_id, $date, $existing->in_time, $new_out);
                        $this->db->where('id', $existing->id)->update(db_prefix() . 'hr_attendance', [
                            'out_time'      => $new_out,
                            'working_hours' => $resolved['working_hours'],
                            'verify_mode'   => $verify_label,
                        ]);
                        $existing->out_time = $new_out;
                        $saved++;
                    }
                }
            }
        }

        return $saved;
    }

    // Raw punch history for an employee+date, for the attendance list's
    // "View Log" popup - latest punch first.
    public function get_punches($employee_id, $date)
    {
        $this->db->select('p.*, d.name as device_name, d.location as device_location')
            ->from(db_prefix() . 'hr_zkteco_punches p')
            ->join(db_prefix() . $this->devices_table . ' d', 'd.id = p.device_id', 'left')
            ->where('p.employee_id', $employee_id)
            ->where('p.attendance_date', $date)
            ->order_by('p.punch_time', 'DESC');
        return $this->db->get()->result();
    }

    // ── Employee Mapping ─────────────────────────────────────────────────────

    public function get_mappings($device_id = null)
    {
        $this->db->select('m.*, e.first_name, e.last_name, e.employee_code, d.name as device_name')
            ->from(db_prefix() . $this->mapping_table . ' m')
            ->join(db_prefix() . 'hr_employees e', 'e.id = m.employee_id', 'left')
            ->join(db_prefix() . $this->devices_table . ' d', 'd.id = m.device_id', 'left');
        if ($device_id) $this->db->where('m.device_id', $device_id);
        return $this->db->order_by('e.first_name')->get()->result();
    }

    public function save_mapping($employee_id, $device_id, $device_user_id)
    {
        $existing = $this->db
            ->where('employee_id', $employee_id)
            ->where('device_id', $device_id)
            ->get(db_prefix() . $this->mapping_table)->row();

        if ($existing) {
            $this->db->where('id', $existing->id)->update(db_prefix() . $this->mapping_table, [
                'device_user_id' => $device_user_id,
            ]);
        } else {
            $this->db->insert(db_prefix() . $this->mapping_table, [
                'employee_id'    => (int) $employee_id,
                'device_id'      => (int) $device_id,
                'device_user_id' => $device_user_id,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        }
        log_activity('HR ZKTeco Employee Mapping Saved [Employee ID: ' . $employee_id . ', Device ID: ' . $device_id . ']');
        return ['success' => true, 'message' => 'Mapping saved.'];
    }

    public function delete_mapping($id)
    {
        $this->db->where('id', $id)->delete(db_prefix() . $this->mapping_table);
        log_activity('HR ZKTeco Employee Mapping Deleted [ID: ' . $id . ']');
        return ['success' => true];
    }

    // Used by the Employee form - an employee can be enrolled on several
    // devices (multi-select), all sharing the same Device User ID, so this
    // returns every mapping row the employee currently has.
    public function get_mappings_for_employee($employee_id)
    {
        return $this->db->where('employee_id', $employee_id)
            ->get(db_prefix() . $this->mapping_table)->result();
    }

    // Employee form save: replaces the employee's whole set of device
    // mappings with exactly the devices just submitted (all sharing the
    // one Device User ID), removing mappings for any device that's no
    // longer selected instead of leaving it stale alongside the new ones.
    public function set_employee_device_mapping($employee_id, $device_ids, $device_user_id)
    {
        $device_ids     = array_values(array_unique(array_filter(array_map('intval', (array) $device_ids))));
        $device_user_id = trim((string) $device_user_id);

        if (empty($device_ids) || $device_user_id === '') {
            $this->db->where('employee_id', $employee_id)->delete(db_prefix() . $this->mapping_table);
            return;
        }

        $this->db->where('employee_id', $employee_id)->where_not_in('device_id', $device_ids)
            ->delete(db_prefix() . $this->mapping_table);

        foreach ($device_ids as $device_id) {
            $this->save_mapping($employee_id, $device_id, $device_user_id);
        }
    }

    // Public so file-based imports (e.g. Attendance::_parse_attlog()) can
    // resolve the same device-user-id -> employee mappings set up on the
    // Employee Mapping screen, without duplicating the lookup.
    public function resolve_employee($device_id, $device_user_id)
    {
        $map = $this->db
            ->where('device_id', $device_id)
            ->where('device_user_id', $device_user_id)
            ->get(db_prefix() . $this->mapping_table)->row();
        return $map ? $map->employee_id : null;
    }

    // ── Push Logs ────────────────────────────────────────────────────────────

    public function get_logs($device_id = null, $limit = 100)
    {
        $this->db->select('l.*, d.name as device_name, d.ip_address')
            ->from(db_prefix() . $this->logs_table . ' l')
            ->join(db_prefix() . $this->devices_table . ' d', 'd.id = l.device_id', 'left');
        if ($device_id) $this->db->where('l.device_id', $device_id);
        return $this->db->order_by('l.sync_at', 'DESC')->limit($limit)->get()->result();
    }

    public function log_push($device_id, $fetched, $saved, $status, $error = null)
    {
        $this->db->insert(db_prefix() . $this->logs_table, [
            'device_id'       => $device_id,
            'sync_at'         => date('Y-m-d H:i:s'),
            'records_fetched' => $fetched,
            'records_saved'   => $saved,
            'status'          => $status,
            'error_message'   => $error,
        ]);
    }

    // Settings > Attendance Devices > "Sync Log Retention (days)" - $days <= 0
    // means the setting is unset/disabled, so logs are kept forever.
    public function delete_old_sync_logs($days)
    {
        $days = (int) $days;
        if ($days <= 0) return;
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        $this->db->where('sync_at <', $cutoff)->delete(db_prefix() . $this->logs_table);
    }
}
