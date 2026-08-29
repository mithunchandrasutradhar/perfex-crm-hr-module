<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Attendance_model extends App_Model
{
    private $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'hr_attendance';
    }

    public function get($id = null)
    {
        $this->db->select('a.*, CONCAT(e.first_name," ",e.last_name) as employee_name,
            e.employee_code, d.name as department_name', false)
            ->from($this->table . ' a')
            ->join(db_prefix() . 'hr_employees e', 'e.id = a.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left');
        if ($id) {
            $this->db->where('a.id', $id);
            return $this->db->get()->row();
        }
        return $this->db->get()->result();
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('a.*, CONCAT(e.first_name," ",e.last_name) as employee_name,
            e.employee_code, d.name as department_name', false)
            ->from($this->table . ' a')
            ->join(db_prefix() . 'hr_employees e', 'e.id = a.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left');

        if (!empty($filters['employee_id']))   $this->db->where('a.employee_id', $filters['employee_id']);
        if (!empty($filters['department_id'])) $this->db->where('e.department_id', $filters['department_id']);
        if (!empty($filters['status']))        $this->db->where('a.status', $filters['status']);
        if (!empty($filters['from_date']))     $this->db->where('a.attendance_date >=', $filters['from_date']);
        if (!empty($filters['to_date']))       $this->db->where('a.attendance_date <=', $filters['to_date']);
        if (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('CONCAT(e.first_name," ",e.last_name)', $filters['search'])
                ->or_like('e.employee_code', $filters['search'])
                ->or_like('d.name', $filters['search'])
                ->group_end();
        }
        if (!empty($filters['month']) && !empty($filters['year'])) {
            $this->db->where('MONTH(a.attendance_date)', $filters['month']);
            $this->db->where('YEAR(a.attendance_date)', $filters['year']);
        }

        $this->db->order_by('a.attendance_date', 'DESC');
        return $this->db->get()->result();
    }

    public function get_monthly($employee_id, $month, $year)
    {
        $this->db->where('employee_id', $employee_id)
            ->where('MONTH(attendance_date)', $month)
            ->where('YEAR(attendance_date)', $year)
            ->order_by('attendance_date', 'ASC');
        $rows   = $this->db->get($this->table)->result();
        $indexed = [];
        foreach ($rows as $r) {
            $indexed[$r->attendance_date] = $r;
        }
        return $indexed;
    }

    public function get_summary($employee_id, $month, $year)
    {
        $this->db->select('status, COUNT(*) as cnt')
            ->where('employee_id', $employee_id)
            ->where('MONTH(attendance_date)', $month)
            ->where('YEAR(attendance_date)', $year)
            ->group_by('status');
        $rows = $this->db->get($this->table)->result();
        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'total_hours' => 0];
        foreach ($rows as $r) {
            $summary[$r->status] = (int) $r->cnt;
        }
        // Total working hours
        $this->db->select_sum('working_hours')
            ->where('employee_id', $employee_id)
            ->where('MONTH(attendance_date)', $month)
            ->where('YEAR(attendance_date)', $year);
        $h = $this->db->get($this->table)->row();
        $summary['total_hours'] = $h ? round($h->working_hours, 2) : 0;
        return $summary;
    }

    public function add($data)
    {
        if ($this->record_exists($data['employee_id'], $data['attendance_date'])) {
            return ['success' => false, 'message' => _l('hr_val_duplicate_attendance')];
        }
        $data['working_hours'] = $this->_calc_hours($data['in_time'] ?? null, $data['out_time'] ?? null);
        $data['status']        = $this->_normalize_status($data);
        $data['created_by']    = get_staff_user_id();
        $data['created_at']    = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        $id = $this->db->insert_id();
        if ($id) {
            log_activity('HR Attendance Record Added [ID: ' . $id . ', Employee ID: ' . $data['employee_id'] . ', Date: ' . $data['attendance_date'] . ']');
        }
        return $id ? ['success' => true, 'id' => $id] : ['success' => false, 'message' => _l('hr_error_save_failed')];
    }

    public function update($data, $id)
    {
        $existing = $this->get($id);
        if ($existing && $data['attendance_date'] !== $existing->attendance_date) {
            if ($this->record_exists($existing->employee_id, $data['attendance_date'], $id)) {
                return ['success' => false, 'message' => _l('hr_val_duplicate_attendance')];
            }
        }
        $data['working_hours'] = $this->_calc_hours($data['in_time'] ?? null, $data['out_time'] ?? null);
        $data['status']        = $this->_normalize_status($data);
        $data['updated_at']    = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update($this->table, $data);
        log_activity('HR Attendance Record Edited [ID: ' . $id . ']');
        return ['success' => true];
    }

    // 'present'/'late' must always reflect in_time against the office start time + late
    // threshold, not whatever the status dropdown happened to have selected - otherwise a
    // manually-entered 09:15 clock-in can get saved as "Present" if the field was left as-is.
    // 'absent'/'half_day' are legitimate manual calls (e.g. an approved half day) and are kept as-is.
    private function _normalize_status($data)
    {
        $status = $data['status'] ?? null;
        if (in_array($status, ['absent', 'half_day'], true)) {
            return $status;
        }
        return $this->_determine_status($data['in_time'] ?? null, $data['employee_id'] ?? null, $data['attendance_date'] ?? null);
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete($this->table);
        $deleted = $this->db->affected_rows() > 0;
        if ($deleted) {
            log_activity('HR Attendance Record Deleted [ID: ' . $id . ']');
        }
        return $deleted;
    }

    public function record_exists($employee_id, $date, $exclude_id = null)
    {
        $this->db->where('employee_id', $employee_id)->where('attendance_date', $date);
        if ($exclude_id) $this->db->where('id !=', $exclude_id);
        return $this->db->count_all_results($this->table) > 0;
    }

    // When several exports (different devices, or a device export plus the
    // monthly report) cover the same employee+date, later imports merge into
    // an existing row instead of being skipped - each field only fills in
    // gaps or widens the punch span, so one source's blank never erases
    // another source's real value.
    public function bulk_import($records)
    {
        $saved   = 0;
        $merged  = 0;
        $skipped = 0;

        foreach ($records as $rec) {
            $existing = $this->get_by_date($rec['employee_id'], $rec['attendance_date']);

            if (!$existing) {
                $rec['working_hours'] = $this->_calc_hours($rec['in_time'] ?? null, $rec['out_time'] ?? null);
                $rec['status']        = $this->_normalize_status($rec);
                $rec['created_at']    = date('Y-m-d H:i:s');
                $this->db->insert($this->table, $rec);
                $saved++;
                continue;
            }

            $mergedIn  = $this->_merge_time($existing->in_time, $rec['in_time'] ?? null, true);
            $mergedOut = $this->_merge_time($existing->out_time, $rec['out_time'] ?? null, false);

            if ($mergedIn === $existing->in_time && $mergedOut === $existing->out_time) {
                $skipped++;
                continue;
            }

            // Computed before queuing the update's where() below - _determine_status()
            // runs its own nested query (via Shifts_model), and building it inline
            // inside the update() call's array argument runs it while that where()
            // is still pending on the same shared query builder, corrupting both.
            $status = $mergedIn
                ? $this->_determine_status($mergedIn, $rec['employee_id'], $rec['attendance_date'])
                : ($existing->status ?: ($rec['status'] ?? 'absent'));

            $this->db->where('id', $existing->id)->update($this->table, [
                'in_time'       => $mergedIn,
                'out_time'      => $mergedOut,
                'working_hours' => $this->_calc_hours($mergedIn, $mergedOut),
                'status'        => $status,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            $merged++;
        }

        log_activity('HR Attendance Bulk Import Completed [Saved: ' . $saved . ', Merged: ' . $merged . ', Skipped: ' . $skipped . ']');
        return ['saved' => $saved, 'merged' => $merged, 'skipped' => $skipped];
    }

    // Earliest wins for in_time, latest wins for out_time - keeps the widest
    // punch span across sources, consistent with the single-device
    // first-punch/last-punch rule. A blank on either side never wins.
    private function _merge_time($existing, $new, $preferEarliest)
    {
        if (!$existing) return $new ?: null;
        if (!$new) return $existing;
        return $preferEarliest ? min($existing, $new) : max($existing, $new);
    }

    // Called by ZKTeco sync — inserts or updates
    public function upsert_from_device($employee_id, $date, $in_time, $out_time, $device_id)
    {
        $existing = $this->get_by_date($employee_id, $date);
        if ($existing) {
            // Only update if device record
            if ($existing->source === 'zkteco') {
                $in  = $in_time ?: $existing->in_time;
                $out = $out_time ?: $existing->out_time;
                $this->db->where('id', $existing->id)->update($this->table, [
                    'in_time'      => $in,
                    'out_time'     => $out,
                    'working_hours'=> $this->_calc_hours($in, $out),
                    'status'       => $this->_determine_status($in, $employee_id, $date),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
                log_activity('HR Attendance Record Updated From Device [Employee ID: ' . $employee_id . ', Date: ' . $date . ', Device ID: ' . $device_id . ']');
            }
            return false;
        }
        $status = $this->_determine_status($in_time, $employee_id, $date);
        $this->db->insert($this->table, [
            'employee_id'     => $employee_id,
            'attendance_date' => $date,
            'in_time'         => $in_time,
            'out_time'        => $out_time,
            'working_hours'   => $this->_calc_hours($in_time, $out_time),
            'status'          => $status,
            'source'          => 'zkteco',
            'device_id'       => $device_id,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Attendance Record Added From Device [Employee ID: ' . $employee_id . ', Date: ' . $date . ', Device ID: ' . $device_id . ']');
        return true;
    }

    public function get_by_date($employee_id, $date)
    {
        $this->db->where('employee_id', $employee_id)->where('attendance_date', $date);
        return $this->db->get($this->table)->row();
    }

    // Shared shift-aware status/hours resolution - used by manual entry/import
    // above and by Zkteco_model::sync() so device-synced punches get the same
    // shift-based late/hours calculation instead of a separate, divergent path.
    public function resolve_status_and_hours($employee_id, $date, $in_time, $out_time)
    {
        return [
            'status'        => $this->_determine_status($in_time, $employee_id, $date),
            'working_hours' => $this->_calc_hours($in_time, $out_time),
        ];
    }

    // Handles overnight shifts (e.g. Night Shift 22:00 -> 06:00), where out_time
    // is technically earlier in the clock than in_time because it falls on the
    // next calendar day.
    private function _calc_hours($in, $out)
    {
        if (!$in || !$out) return null;
        $diff = strtotime($out) - strtotime($in);
        if ($diff <= 0) {
            $diff += 86400;
        }
        return round($diff / 3600, 2);
    }

    // Looks up the employee's approved shift assignment for this date and uses
    // its start time as the late-arrival reference point instead of the global
    // office_start_time - so a Night Shift employee clocking in at 22:05 isn't
    // marked "late" against a 09:00 office start. Falls back to the global
    // office hours when the employee has no shift assigned for that date, so
    // non-shift employees are completely unaffected.
    private function _determine_status($in_time, $employee_id = null, $date = null)
    {
        if (!$in_time) return 'absent';
        $CI = &get_instance();
        $CI->load->model('hr_module/Hr_module_model');

        $start_time = null;
        if ($employee_id && $date) {
            $CI->load->model('hr_module/Shifts_model');
            $shift = $CI->Shifts_model->get_employee_shift_for_date($employee_id, $date);
            if ($shift) {
                $start_time = $shift->start_time;
            }
        }
        if (!$start_time) {
            $start_time = $CI->Hr_module_model->get_setting('office_start_time', '09:00');
        }

        $threshold   = (int) $CI->Hr_module_model->get_setting('late_threshold_minutes', '15');
        $late_cutoff = date('H:i', strtotime($start_time) + $threshold * 60);
        return (substr($in_time, 0, 5) > $late_cutoff) ? 'late' : 'present';
    }
}
