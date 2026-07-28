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

    // The staff members (if configured via the policy_approver_ids setting, a
    // comma-separated list of staffids) who may approve/reject policies - returns
    // an empty array if not yet configured.
    public function get_policy_approvers()
    {
        $csv = trim($this->get_setting('policy_approver_ids'));
        if ($csv === '') {
            return [];
        }
        $ids = array_values(array_filter(array_map('intval', explode(',', $csv))));
        if (empty($ids)) {
            return [];
        }
        return $this->db->select('staffid, email, firstname, lastname')
            ->where_in('staffid', $ids)
            ->get(db_prefix() . 'staff')->result();
    }

    // ─── Notifications ────────────────────────────────────────────────────

    // Sends a plain HTML notification email (with a direct link back to the
    // record) to the address configured on the module's Settings page whenever
    // a leave/loan/helpdesk/overtime request is submitted. No-op if that setting
    // is empty or invalid. Never throws - a failed send must never break the
    // request-creation flow that triggered it. $message is caller-built HTML
    // (see format_notification_details()) describing that specific request, so
    // it is not re-wrapped here.
    public function send_notification_email($subject, $message, $link_url)
    {
        $to = trim($this->get_setting('hr_notification_email'));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return $this->_send_hr_email($to, $subject, $message . $this->_notification_link_block($link_url));
    }

    // Sends a plain HTML status-update email directly to an arbitrary address
    // (e.g. an employee's own email, when their leave/loan/etc request is
    // approved/rejected) - independent of the hr_notification_email admin-inbox
    // setting used by send_notification_email(). No-op on an empty/invalid
    // address; never throws, for the same reason as above. $link_url is optional.
    public function send_employee_email($to, $subject, $message, $link_url = null)
    {
        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return $this->_send_hr_email($to, $subject, $message . ($link_url ? $this->_notification_link_block($link_url) : ''));
    }

    private function _notification_link_block($link_url)
    {
        return '<p><a href="' . htmlspecialchars($link_url) . '" style="display:inline-block;padding:8px 16px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:4px">View Details</a></p>'
            . '<p style="color:#888;font-size:12px">' . htmlspecialchars($link_url) . '</p>';
    }

    // Broadcasts a formal announcement to every active hr_module employee who is
    // mapped to a staff account (BCC'd, so recipients don't see each other's
    // addresses) - used when a leave request is approved, to let colleagues know
    // the employee will be out. $message is caller-built HTML (see
    // format_notification_details()). No-op if there's no one to notify; never
    // throws, for the same reason as the other senders here.
    public function send_leave_announcement($subject, $message)
    {
        $emails = array_filter(array_column(
            $this->db->select('email')
                ->where('status', 1)
                ->where('email !=', '')
                ->where('staff_id IS NOT NULL')
                ->where('staff_id !=', 0)
                ->get(db_prefix() . 'hr_employees')->result_array(),
            'email'
        ));
        if (empty($emails)) {
            return false;
        }

        $company_name = get_option('companyname');
        $body = $message
            . '<hr style="border:none;border-top:1px solid #e5e5e5;margin:20px 0">'
            . '<p style="color:#999;font-size:12px;margin:0">This is an automated announcement from the '
                . htmlspecialchars($company_name) . ' HR Department. Please do not reply to this email.</p>';

        try {
            $CI = &get_instance();
            $CI->email->clear(true);
            $CI->email->from(get_option('smtp_email'), get_option('companyname'));
            $CI->email->to(get_option('smtp_email'));
            $CI->email->bcc(implode(',', $emails));
            $CI->email->subject($subject);
            $CI->email->message($body);
            return (bool) $CI->email->send();
        } catch (Exception $e) {
            log_activity('HR Module leave announcement email failed: ' . $e->getMessage());
            return false;
        }
    }

    // Broadcasts a newly-published (or updated) policy to its audience - every active
    // hr_module employee mapped to a staff account when $department_id is null (a
    // public policy), or just those departments' employees when it's a private one
    // targeting one or more specific departments.
    public function send_policy_announcement($subject, $message, $department_ids = null, $link_url = null)
    {
        if (is_array($department_ids) && empty($department_ids)) {
            return false;
        }
        $this->db->select('email')
            ->where('status', 1)
            ->where('email !=', '')
            ->where('staff_id IS NOT NULL')
            ->where('staff_id !=', 0);
        if (is_array($department_ids)) {
            $this->db->where_in('department_id', $department_ids);
        } elseif ($department_ids !== null) {
            $this->db->where('department_id', $department_ids);
        }
        $emails = array_filter(array_column(
            $this->db->get(db_prefix() . 'hr_employees')->result_array(),
            'email'
        ));
        if (empty($emails)) {
            return false;
        }

        $company_name = get_option('companyname');
        $body = $message
            . ($link_url ? $this->_notification_link_block($link_url) : '')
            . '<hr style="border:none;border-top:1px solid #e5e5e5;margin:20px 0">'
            . '<p style="color:#999;font-size:12px;margin:0">This is an automated announcement from the '
                . htmlspecialchars($company_name) . ' HR Department. Please do not reply to this email.</p>';

        try {
            $CI = &get_instance();
            $CI->email->clear(true);
            $CI->email->from(get_option('smtp_email'), get_option('companyname'));
            $CI->email->to(get_option('smtp_email'));
            $CI->email->bcc(implode(',', $emails));
            $CI->email->subject($subject);
            $CI->email->message($body);
            return (bool) $CI->email->send();
        } catch (Exception $e) {
            log_activity('HR Module policy announcement email failed: ' . $e->getMessage());
            return false;
        }
    }

    private function _send_hr_email($to, $subject, $message)
    {
        $company_name = get_option('companyname');
        $body = $message
            . '<hr style="border:none;border-top:1px solid #e5e5e5;margin:20px 0">'
            . '<p style="color:#999;font-size:12px;margin:0">This is an automated notification from the '
                . htmlspecialchars($company_name) . ' HR Module. Please do not reply to this email.</p>';

        try {
            $CI = &get_instance();
            $CI->email->clear(true);
            $CI->email->from(get_option('smtp_email'), get_option('companyname'));
            $CI->email->to($to);
            $CI->email->subject($subject);
            $CI->email->message($body);
            return (bool) $CI->email->send();
        } catch (Exception $e) {
            log_activity('HR Module email failed: ' . $e->getMessage());
            return false;
        }
    }

    // Renders a caller-supplied [label => value] array as a simple HTML detail
    // table for send_notification_email() - keeps every request type's email body
    // (leave/loan/helpdesk/overtime) laid out consistently. Values are trusted
    // HTML (the caller escapes/formats them first, since some legitimately need
    // markup such as nl2br()); labels are always escaped here.
    public function format_notification_details($rows)
    {
        $html = '<table style="border-collapse:collapse;margin:12px 0;font-size:14px">';
        foreach ($rows as $label => $value) {
            $html .= '<tr>'
                . '<td style="padding:4px 16px 4px 0;color:#666;vertical-align:top;white-space:nowrap">' . htmlspecialchars($label) . '</td>'
                . '<td style="padding:4px 0;vertical-align:top">' . $value . '</td>'
                . '</tr>';
        }
        $html .= '</table>';
        return $html;
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

        // Latest performance sub-target + how many are still open, across all this
        // employee's targets (a sub-target only carries employee_id via its parent Target).
        $perf = $this->db->select('st.*', false)
            ->from(db_prefix() . 'hr_performance_sub_targets st')
            ->join(db_prefix() . 'hr_performance_targets t', 't.id = st.target_id')
            ->where('t.employee_id', $employee_id)
            ->order_by('st.id', 'DESC')->limit(1)
            ->get()->row();
        $stats['latest_task'] = $perf;

        $stats['open_tasks'] = $this->db
            ->from(db_prefix() . 'hr_performance_sub_targets st')
            ->join(db_prefix() . 'hr_performance_targets t', 't.id = st.target_id')
            ->where('t.employee_id', $employee_id)
            ->where_in('st.status', ['pending', 'in_progress', 'partially_completed'])
            ->count_all_results();

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
