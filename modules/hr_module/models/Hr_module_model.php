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

        // Total departments (from Perfex core departments table)
        $stats['total_departments'] = $this->db->count_all(db_prefix() . 'departments');

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

    public function get_employee_dashboard_stats($employee_id)
    {
        $stats = [];
        $today = date('Y-m-d');
        $year  = (int) date('Y');
        $month = (int) date('n');

        // Attendance today
        $att = $this->db->where('employee_id', $employee_id)
            ->where('attendance_date', $today)
            ->get(db_prefix() . 'hr_attendance')->row();
        $stats['attendance_today'] = $att ? $att->status : null;

        // Leave balance — total remaining days this year
        $this->db->select('SUM(allocated_days + carry_forward_days - used_days) as remaining', false)
            ->where('employee_id', $employee_id)
            ->where('year', $year);
        $bal = $this->db->get(db_prefix() . 'hr_leave_balances')->row();
        $stats['leave_balance_remaining'] = ($bal && $bal->remaining !== null) ? (float) $bal->remaining : 0;

        // Used leave days this year
        $this->db->select('SUM(used_days) as used', false)
            ->where('employee_id', $employee_id)
            ->where('year', $year);
        $used = $this->db->get(db_prefix() . 'hr_leave_balances')->row();
        $stats['leave_days_used'] = ($used && $used->used !== null) ? (float) $used->used : 0;

        // Pending leave requests
        $this->db->where('employee_id', $employee_id)->where('status', 'pending');
        $stats['pending_leaves'] = $this->db->count_all_results(db_prefix() . 'hr_leave_requests');

        // Approved leave requests this year
        $this->db->where('employee_id', $employee_id)
            ->where('status', 'approved')
            ->where('YEAR(from_date)', $year);
        $stats['approved_leaves'] = $this->db->count_all_results(db_prefix() . 'hr_leave_requests');

        // Active loan
        $loan = $this->db->where('employee_id', $employee_id)
            ->where('status', 'active')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get(db_prefix() . 'hr_loans')->row();
        $stats['active_loan'] = $loan;

        // Pending overtime this month
        $this->db->where('employee_id', $employee_id)->where('status', 'pending');
        $stats['pending_overtime'] = $this->db->count_all_results(db_prefix() . 'hr_overtime');

        // Approved overtime hours this month
        $this->db->select('SUM(hours) as total_hours', false)
            ->where('employee_id', $employee_id)
            ->where('status', 'approved')
            ->where('MONTH(overtime_date)', $month)
            ->where('YEAR(overtime_date)', $year);
        $ot = $this->db->get(db_prefix() . 'hr_overtime')->row();
        $stats['approved_overtime_hours'] = ($ot && $ot->total_hours !== null) ? (float) $ot->total_hours : 0;

        // Open helpdesk tickets
        $this->db->where('employee_id', $employee_id)->where_in('status', ['open', 'in_progress']);
        $stats['open_tickets'] = $this->db->count_all_results(db_prefix() . 'hr_helpdesk');

        // Current month payroll
        $payroll = $this->db->where('employee_id', $employee_id)
            ->where('pay_month', $month)
            ->where('pay_year', $year)
            ->get(db_prefix() . 'hr_payroll')->row();
        if (!$payroll) {
            // Try last month
            $lm = $month === 1 ? 12 : $month - 1;
            $ly = $month === 1 ? $year - 1 : $year;
            $payroll = $this->db->where('employee_id', $employee_id)
                ->where('pay_month', $lm)->where('pay_year', $ly)
                ->get(db_prefix() . 'hr_payroll')->row();
        }
        $stats['latest_payroll'] = $payroll;

        // Latest performance review
        $perf = $this->db->where('employee_id', $employee_id)
            ->order_by('id', 'DESC')->limit(1)
            ->get(db_prefix() . 'hr_performance_reviews')->row();
        $stats['latest_review'] = $perf;

        // Upcoming / ongoing trainings (enrolled, not yet completed)
        $this->db->select('t.id, t.title, t.start_date, t.end_date, t.status', false)
            ->from(db_prefix() . 'hr_training_participants p')
            ->join(db_prefix() . 'hr_training t', 't.id = p.training_id', 'left')
            ->where('p.employee_id', $employee_id)
            ->where('p.completed', 0)
            ->where_in('t.status', ['scheduled', 'in_progress'])
            ->order_by('t.start_date', 'ASC')
            ->limit(3);
        $stats['upcoming_trainings'] = $this->db->get()->result();

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
        $this->db->select('e.id, e.employee_code,
                CONCAT(COALESCE(s.firstname, e.first_name), " ", COALESCE(s.lastname, e.last_name)) as name', false)
            ->from(db_prefix() . 'hr_employees e')
            ->join(db_prefix() . 'staff s', 's.staffid = e.staff_id', 'left')
            ->where('e.status', 1)
            ->order_by('s.firstname, e.first_name', 'ASC');
        $rows   = $this->db->get()->result();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = $row->employee_code . ' - ' . $row->name;
        }
        return $result;
    }

    public function get_departments_dropdown()
    {
        $this->db->select('departmentid as id, name');
        $this->db->order_by('name', 'ASC');
        $rows   = $this->db->get(db_prefix() . 'departments')->result();
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
