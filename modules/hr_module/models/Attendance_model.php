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
        $data['created_by']    = get_staff_user_id();
        $data['created_at']    = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        $id = $this->db->insert_id();
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
        $data['updated_at']    = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update($this->table, $data);
        return ['success' => true];
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function record_exists($employee_id, $date, $exclude_id = null)
    {
        $this->db->where('employee_id', $employee_id)->where('attendance_date', $date);
        if ($exclude_id) $this->db->where('id !=', $exclude_id);
        return $this->db->count_all_results($this->table) > 0;
    }

    public function bulk_import($records)
    {
        $saved = 0;
        $skipped = 0;
        foreach ($records as $rec) {
            if ($this->record_exists($rec['employee_id'], $rec['attendance_date'])) {
                $skipped++;
                continue;
            }
            $rec['working_hours'] = $this->_calc_hours($rec['in_time'] ?? null, $rec['out_time'] ?? null);
            $rec['created_at']    = date('Y-m-d H:i:s');
            $this->db->insert($this->table, $rec);
            $saved++;
        }
        return ['saved' => $saved, 'skipped' => $skipped];
    }

    // Called by ZKTeco sync — inserts or updates
    public function upsert_from_device($employee_id, $date, $in_time, $out_time, $device_id)
    {
        $existing = $this->get_by_date($employee_id, $date);
        if ($existing) {
            // Only update if device record
            if ($existing->source === 'zkteco') {
                $this->db->where('id', $existing->id)->update($this->table, [
                    'in_time'      => $in_time ?: $existing->in_time,
                    'out_time'     => $out_time ?: $existing->out_time,
                    'working_hours'=> $this->_calc_hours($in_time ?: $existing->in_time, $out_time ?: $existing->out_time),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
            }
            return false;
        }
        $status = $this->_determine_status($in_time);
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
        return true;
    }

    public function get_by_date($employee_id, $date)
    {
        $this->db->where('employee_id', $employee_id)->where('attendance_date', $date);
        return $this->db->get($this->table)->row();
    }

    private function _calc_hours($in, $out)
    {
        if (!$in || !$out) return null;
        $diff = strtotime($out) - strtotime($in);
        return $diff > 0 ? round($diff / 3600, 2) : null;
    }

    private function _determine_status($in_time)
    {
        if (!$in_time) return 'absent';
        $CI = &get_instance();
        $CI->load->model('hr_module/Hr_module_model');
        $office_start = $CI->Hr_module_model->get_setting('office_start_time', '09:00');
        $threshold    = (int) $CI->Hr_module_model->get_setting('late_threshold_minutes', '15');
        $late_cutoff  = date('H:i', strtotime($office_start) + $threshold * 60);
        return (substr($in_time, 0, 5) > $late_cutoff) ? 'late' : 'present';
    }
}
