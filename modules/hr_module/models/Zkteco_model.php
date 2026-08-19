<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Zkteco_model extends App_Model
{
    private $devices_table  = 'hr_zkteco_devices';
    private $mapping_table  = 'hr_zkteco_mapping';
    private $logs_table     = 'hr_zkteco_sync_logs';

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

    public function add_device($data)
    {
        $record = [
            'name'       => $data['name'],
            'ip_address' => $data['ip_address'],
            'port'       => (int) ($data['port'] ?? 4370),
            'location'   => $data['location'] ?? null,
            'notes'      => $data['notes'] ?? null,
            'status'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
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
            'name'       => $data['name'],
            'ip_address' => $data['ip_address'],
            'port'       => (int) ($data['port'] ?? 4370),
            'location'   => $data['location'] ?? null,
            'notes'      => $data['notes'] ?? null,
            'status'     => $data['status'] ?? 1,
            'updated_at' => date('Y-m-d H:i:s'),
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

    // ── Sync ─────────────────────────────────────────────────────────────────

    public function test_connection($ip, $port = 4370)
    {
        $timeout = 5;
        $sock = @fsockopen($ip, (int) $port, $errno, $errstr, $timeout);
        if ($sock) {
            fclose($sock);
            return ['success' => true, 'message' => 'Connection successful to ' . $ip . ':' . $port];
        }
        return ['success' => false, 'message' => 'Cannot connect to ' . $ip . ':' . $port . ' — ' . $errstr];
    }

    public function sync($device_id)
    {
        $this->load->model('hr_module/Attendance_model');

        $device = $this->get_device($device_id);
        if (!$device) return ['success' => false, 'message' => 'Device not found.'];

        $conn = $this->test_connection($device->ip_address, $device->port);
        if (!$conn['success']) {
            $this->_log_sync($device_id, 0, 0, 'failed', $conn['message']);
            return $conn;
        }

        $this->load->library('hr_module/Zkteco_lib');
        $result = $this->zkteco_lib->fetch_attendance($device->ip_address, $device->port);

        if (!$result['success']) {
            $this->_log_sync($device_id, 0, 0, 'failed', $result['message']);
            return $result;
        }

        $records_fetched = count($result['records']);
        $records_saved   = 0;

        foreach ($result['records'] as $rec) {
            $employee_id = $this->resolve_employee($device_id, $rec['user_id']);
            if (!$employee_id) continue;

            $check_date = date('Y-m-d', strtotime($rec['timestamp']));
            $check_time = date('H:i:s', strtotime($rec['timestamp']));

            // Check if attendance record already exists for this employee/date
            $existing = $this->db
                ->where('employee_id', $employee_id)
                ->where('attendance_date', $check_date)
                ->get(db_prefix() . 'hr_attendance')->row();

            if (!$existing) {
                $resolved = $this->Attendance_model->resolve_status_and_hours($employee_id, $check_date, $check_time, null);
                $this->db->insert(db_prefix() . 'hr_attendance', [
                    'employee_id'     => $employee_id,
                    'attendance_date' => $check_date,
                    'in_time'         => $check_time,
                    'status'          => $resolved['status'],
                    'source'          => 'zkteco',
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
                $records_saved++;
            } elseif (empty($existing->out_time) && $check_time > $existing->in_time) {
                // Update out_time if later than in_time, and recompute hours now
                // that both punches are known.
                $resolved = $this->Attendance_model->resolve_status_and_hours($employee_id, $check_date, $existing->in_time, $check_time);
                $this->db->where('id', $existing->id)->update(db_prefix() . 'hr_attendance', [
                    'out_time'      => $check_time,
                    'working_hours' => $resolved['working_hours'],
                ]);
            }
        }

        // Update last_sync_at
        $this->db->where('id', $device_id)->update(db_prefix() . $this->devices_table, [
            'last_sync_at' => date('Y-m-d H:i:s'),
        ]);

        $this->_log_sync($device_id, $records_fetched, $records_saved, 'success');

        log_activity('HR ZKTeco Sync Completed [Device ID: ' . $device_id . ', Fetched: ' . $records_fetched . ', Saved: ' . $records_saved . ']');

        return [
            'success'         => true,
            'records_fetched' => $records_fetched,
            'records_saved'   => $records_saved,
            'message'         => "Sync complete. Fetched: $records_fetched, Saved: $records_saved",
        ];
    }

    public function auto_sync_all_devices()
    {
        $devices = $this->get_devices(true);
        foreach ($devices as $device) {
            $this->sync($device->id);
        }
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

    // ── Sync Logs ─────────────────────────────────────────────────────────────

    public function get_logs($device_id = null, $limit = 100)
    {
        $this->db->select('l.*, d.name as device_name, d.ip_address')
            ->from(db_prefix() . $this->logs_table . ' l')
            ->join(db_prefix() . $this->devices_table . ' d', 'd.id = l.device_id', 'left');
        if ($device_id) $this->db->where('l.device_id', $device_id);
        return $this->db->order_by('l.sync_at', 'DESC')->limit($limit)->get()->result();
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

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

    private function _log_sync($device_id, $fetched, $saved, $status, $error = null)
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
}
