<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Hr_module_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // ─── Settings ─────────────────────────────────────────────────────────

    public function get_setting($key, $default = '')
    {
        $this->db->where('setting_key', $key);
        $row = $this->db->get(db_prefix() . 'hr_settings')->row();
        if ($row) {
            return $row->setting_value;
        }
        return $default;
    }

    public function get_all_settings()
    {
        $rows    = $this->db->get(db_prefix() . 'hr_settings')->result();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        return $settings;
    }

    public function save_settings($data)
    {
        $now = date('Y-m-d H:i:s');
        foreach ($data as $key => $value) {
            $this->db->where('setting_key', $key);
            $existing = $this->db->get(db_prefix() . 'hr_settings')->row();
            if ($existing) {
                $this->db->where('setting_key', $key);
                $this->db->update(db_prefix() . 'hr_settings', [
                    'setting_value' => $value,
                    'updated_at'    => $now,
                ]);
            } else {
                $this->db->insert(db_prefix() . 'hr_settings', [
                    'setting_key'   => $key,
                    'setting_value' => $value,
                    'created_at'    => $now,
                ]);
            }
        }
        return true;
    }

    // ─── Dashboard stats ──────────────────────────────────────────────────

    public function get_dashboard_stats()
    {
        $stats = [];
        $today = date('Y-m-d');
        $year  = date('Y');
        $month = date('n');

        // Total & active employees
        $stats['total_employees']  = $this->db->count_all(db_prefix() . 'hr_employees');
        $this->db->where('status', 1);
        $stats['active_employees'] = $this->db->count_all_results(db_prefix() . 'hr_employees');

        // Total departments
        $this->db->where('status', 1);
        $stats['total_departments'] = $this->db->count_all_results(db_prefix() . 'hr_departments');

        // On leave today
        $this->db->where('status', 'approved');
        $this->db->where('from_date <=', $today);
        $this->db->where('to_date >=', $today);
        $stats['on_leave_today'] = $this->db->count_all_results(db_prefix() . 'hr_leave_requests');

        // Pending leaves
        $this->db->where('status', 'pending');
        $stats['pending_leaves'] = $this->db->count_all_results(db_prefix() . 'hr_leave_requests');

        // Pending loans
        $this->db->where('status', 'pending');
        $stats['pending_loans'] = $this->db->count_all_results(db_prefix() . 'hr_loans');

        // Pending overtime
        $this->db->where('status', 'pending');
        $stats['pending_overtime'] = $this->db->count_all_results(db_prefix() . 'hr_overtime');

        // Today attendance
        $this->db->where('attendance_date', $today);
        $this->db->where('status', 'present');
        $stats['present_today'] = $this->db->count_all_results(db_prefix() . 'hr_attendance');

        $this->db->where('attendance_date', $today);
        $this->db->where('status', 'late');
        $stats['late_today'] = $this->db->count_all_results(db_prefix() . 'hr_attendance');

        return $stats;
    }

    // ─── Audit Trail ──────────────────────────────────────────────────────

    public function log_audit($module, $action, $record_id, $old_value = null, $new_value = null)
    {
        $CI = &get_instance();
        $this->db->insert(db_prefix() . 'hr_audit_trail', [
            'module'       => $module,
            'action'       => $action,
            'record_id'    => $record_id,
            'old_value'    => $old_value ? json_encode($old_value) : null,
            'new_value'    => $new_value ? json_encode($new_value) : null,
            'performed_by' => get_staff_user_id(),
            'ip_address'   => $CI->input->ip_address(),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    // ─── Contract expiry notifications ────────────────────────────────────

    public function notify_expiring_contracts()
    {
        $alert_date = date('Y-m-d', strtotime('+30 days'));
        $this->db->where('status', 'active');
        $this->db->where('end_date', $alert_date);
        $contracts = $this->db->get(db_prefix() . 'hr_contracts')->result();

        foreach ($contracts as $contract) {
            // Fire hook for notifications — Phase 4 will add the email handler
            hooks()->do_action('hr_contract_expiring', $contract);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function get_active_employees_dropdown()
    {
        $this->db->select('id, CONCAT(first_name, " ", last_name) as name, employee_code');
        $this->db->where('status', 1);
        $this->db->order_by('first_name', 'ASC');
        $rows   = $this->db->get(db_prefix() . 'hr_employees')->result();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = $row->employee_code . ' - ' . $row->name;
        }
        return $result;
    }

    public function get_departments_dropdown()
    {
        $this->db->select('id, name');
        $this->db->where('status', 1);
        $this->db->order_by('name', 'ASC');
        $rows   = $this->db->get(db_prefix() . 'hr_departments')->result();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = $row->name;
        }
        return $result;
    }

    public function get_designations_dropdown()
    {
        $this->db->select('id, name');
        $this->db->where('status', 1);
        $this->db->order_by('name', 'ASC');
        $rows   = $this->db->get(db_prefix() . 'hr_designations')->result();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = $row->name;
        }
        return $result;
    }

    public function get_leave_types_dropdown()
    {
        $this->db->select('id, name');
        $this->db->where('status', 1);
        $this->db->order_by('name', 'ASC');
        $rows   = $this->db->get(db_prefix() . 'hr_leave_types')->result();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = $row->name;
        }
        return $result;
    }

    public function get_employee_by_staff_id($staff_id)
    {
        $this->db->where('staff_id', $staff_id);
        $this->db->where('status', 1);
        return $this->db->get(db_prefix() . 'hr_employees')->row();
    }

    public function get_next_employee_code()
    {
        $prefix = $this->get_setting('employee_id_prefix', 'EMP');
        $this->db->select_max('id');
        $row = $this->db->get(db_prefix() . 'hr_employees')->row();
        $next_id = ($row && $row->id) ? ((int)$row->id + 1) : 1;
        return $prefix . str_pad($next_id, 4, '0', STR_PAD_LEFT);
    }
}
