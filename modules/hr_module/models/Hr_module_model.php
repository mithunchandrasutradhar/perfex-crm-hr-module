<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Hr_module_model extends App_Model
{
    // Settings are read many times over the course of one request (e.g. the
    // Payroll list recomputes live overtime/shift/tax figures per row, each
    // pulling half a dozen settings) - cache the full table per-request so
    // that fans out to one query instead of one per lookup.
    private $_settings_cache = null;

    public function __construct()
    {
        parent::__construct();
    }

    // ─── Settings ─────────────────────────────────────────────────────────

    public function get_setting($key, $default = '')
    {
        $settings = $this->get_all_settings();
        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    // Whether a given "Notify on X" checkbox (Settings > Notification Settings)
    // is turned on - defaults to enabled ('1') when never saved, so wiring this
    // guard into a notification call site doesn't silently go quiet for any
    // install that hasn't touched this settings panel yet.
    public function notifications_enabled($key)
    {
        return $this->get_setting($key, '1') == '1';
    }

    public function get_all_settings()
    {
        if ($this->_settings_cache === null) {
            $rows    = $this->db->get(db_prefix() . 'hr_settings')->result();
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row->setting_key] = $row->setting_value;
            }
            $this->_settings_cache = $settings;
        }
        return $this->_settings_cache;
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
        // Invalidate the cache so anything reading settings later in this
        // same request (there isn't currently a caller that does, but a
        // future one shouldn't silently get stale values) sees the update.
        $this->_settings_cache = null;
        log_activity('HR Module Settings Updated [Keys: ' . implode(', ', array_keys($data)) . ']');
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

    // ─── Central (bell-icon) notifications ─────────────────────────────────
    // Perfex core has no built-in "notify everyone with permission X" helper -
    // every core feature that needs this hand-rolls it (see Clients_model,
    // Estimates_model), so these mirror that same pattern for hr_module.

    // Active staff ids who hold $capability on $feature - used to target a
    // central notification "by role" instead of a fixed setting/address.
    private function _staff_ids_with_permission($capability, $feature)
    {
        $ids = [];
        foreach ($this->db->select('staffid')->where('active', 1)->get(db_prefix() . 'staff')->result() as $s) {
            if (staff_can($capability, $feature, $s->staffid)) {
                $ids[] = (int) $s->staffid;
            }
        }
        return $ids;
    }

    // Creates one central notification for a single staff member. $description
    // must be a registered language key (hr_module_lang.php) - the bell dropdown
    // renders it via _l($description, $additional_data), not as raw text.
    // $link is relative (e.g. 'hr_module/leave/view/5'), no leading slash.
    public function notify_staff($staff_id, $description, $link, $additional_data = [])
    {
        if (!$staff_id) {
            return false;
        }
        return (bool) add_notification([
            'touserid'        => (int) $staff_id,
            'description'     => $description,
            'link'            => $link,
            'fromcompany'     => 1,
            'fromuserid'      => 0,
            'additional_data' => serialize($additional_data),
        ]);
    }

    // Notifies every staff member already known to be the audience (e.g. the
    // configured policy approvers, or the same mapped-employee list an
    // announcement email already went to) - avoids re-deriving it.
    public function notify_staff_list($staff_ids, $description, $link, $additional_data = [])
    {
        $notified = [];
        foreach (array_unique(array_filter(array_map('intval', (array) $staff_ids))) as $id) {
            if ($this->notify_staff($id, $description, $link, $additional_data)) {
                $notified[] = $id;
            }
        }
        if (!empty($notified) && function_exists('pusher_trigger_notification')) {
            pusher_trigger_notification($notified);
        }
        return $notified;
    }

    // Notifies every active staff member who holds $capability on $feature -
    // i.e. whoever can actually act on this, so visibility follows the same
    // role/permission system already governing that feature.
    public function notify_by_permission($capability, $feature, $description, $link, $additional_data = [])
    {
        return $this->notify_staff_list($this->_staff_ids_with_permission($capability, $feature), $description, $link, $additional_data);
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


    // ─── WhatsApp notifications (via WAHA) ──────────────────────────────────
    // Only ever used for public, broadcast-style team announcements (leave
    // announcement, leave cancellation announcement, holiday reminder, policy
    // published/updated) - never for individual/HR-only notifications, and
    // never to a personal phone number, only the configured WhatsApp group.
    // Uses its own admin-editable template store (Whatsapp_templates_model),
    // independent from the email wording - see hr_module/whatsapp_templates.
    // No-op unless whatsapp_enabled is on AND a group is configured. Never
    // throws - a WAHA outage must never break the real workflow that
    // triggered it, same rule every other sender here follows.
    // Which per-event "Notify on..." checkbox (WhatsApp Notifications panel)
    // gates each announcement template - lets an admin turn off WhatsApp for
    // one specific event without touching the master whatsapp_enabled switch
    // or the separate email-side notify_* toggles.
    private $whatsapp_event_settings = [
        'leave_announcement'              => 'whatsapp_notify_leave_announcement',
        'leave_cancellation_announcement' => 'whatsapp_notify_leave_cancellation_announcement',
        'holiday_reminder'                => 'whatsapp_notify_holiday_reminder',
        'policy_published'                => 'whatsapp_notify_policy_announcement',
        'policy_updated'                   => 'whatsapp_notify_policy_announcement',
    ];

    public function send_whatsapp_announcement($template_key, array $placeholders, $link_url = null)
    {
        try {
            if ($this->get_setting('whatsapp_enabled') != '1') {
                return false;
            }

            $event_setting = $this->whatsapp_event_settings[$template_key] ?? null;
            if ($event_setting && $this->get_setting($event_setting, '1') != '1') {
                return false;
            }

            $group = trim($this->get_setting('whatsapp_group_id'));
            if ($group === '') {
                return false;
            }

            $CI = &get_instance();
            $CI->load->model('hr_module/Whatsapp_templates_model');
            $tpl = $CI->Whatsapp_templates_model->render($template_key, $placeholders);
            if (!$tpl) {
                return false;
            }

            $text = '*' . $tpl->subject . "*\n\n" . $tpl->body;
            if ($link_url) {
                $text .= "\n\n" . $link_url;
            }

            $base_url = $this->get_setting('whatsapp_base_url', 'https://waha.abutalha.com.bd');
            $session  = $this->get_setting('whatsapp_session', 'default');
            $api_key  = $this->get_setting('whatsapp_api_key');

            $CI->load->library('hr_module/Waha_lib');
            $result = $CI->waha_lib->send_text($base_url, $session, $api_key, $group, $text);
            if (!$result['success']) {
                log_activity('HR WhatsApp announcement failed [' . $template_key . ']: ' . $result['message']);
            }
            return $result['success'];
        } catch (Exception $e) {
            log_activity('HR WhatsApp announcement failed [' . $template_key . ']: ' . $e->getMessage());
            return false;
        }
    }

    private function _notification_link_block($link_url)
    {
        return '<p><a href="' . htmlspecialchars($link_url) . '" style="display:inline-block;padding:8px 16px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:4px">View Details</a></p>'
            . '<p style="color:#888;font-size:12px">' . htmlspecialchars($link_url) . '</p>';
    }

    // Broadcasts a formal announcement to every active hr_module employee who is
    // mapped to a staff account - used when a leave request is approved, to let
    // colleagues know the employee will be out. $message is caller-built HTML
    // (see format_notification_details()). No-op if there's no one to notify;
    // never throws, for the same reason as the other senders here.
    // Sent one by one via send_employee_email() rather than one big BCC blast,
    // so each recipient gets their own copy - relies on Perfex's own core mail
    // queue (Setup > Settings > Email > "Enable email queue") to keep that from
    // blocking whatever triggered it: with that setting on, each call here is
    // just a fast DB insert into Perfex's own mail_queue, sent later in the
    // background by the site's existing cron; with it off, they send immediately.
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

        $sent = 0;
        foreach ($emails as $email) {
            if ($this->send_employee_email($email, $subject, $message)) $sent++;
        }
        return $sent > 0;
    }

    // Broadcasts a newly-published (or updated) policy to its audience - every active
    // hr_module employee mapped to a staff account when $department_id is null (a
    // public policy), or just those departments' employees when it's a private one
    // targeting one or more specific departments.
    // Sent one by one via send_employee_email(), same reasoning as
    // send_leave_announcement() above - relies on Perfex's own core mail queue
    // setting to avoid blocking on a large audience, rather than a separate
    // hr_module-specific queue.
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

        $sent = 0;
        foreach ($emails as $email) {
            if ($this->send_employee_email($email, $subject, $message, $link_url)) $sent++;
        }
        return $sent > 0;
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

        // Leave balance — Casual Leave remaining days this year (dashboard widget
        // shows only this one leave type, not a combined total across all types)
        $this->db->select('SUM(b.allocated_days + b.carry_forward_days - b.used_days) as remaining', false)
            ->from(db_prefix() . 'hr_leave_balances b')
            ->join(db_prefix() . 'hr_leave_types lt', 'lt.id = b.leave_type_id', 'left')
            ->where('b.employee_id', $employee_id)
            ->where('b.year', $year)
            ->where('lt.name', 'Casual Leave');
        $bal = $this->db->get()->row();
        $stats['leave_balance_remaining'] = ($bal && $bal->remaining !== null) ? (float) $bal->remaining : 0;

        // Used Casual Leave days this year
        $this->db->select('SUM(b.used_days) as used', false)
            ->from(db_prefix() . 'hr_leave_balances b')
            ->join(db_prefix() . 'hr_leave_types lt', 'lt.id = b.leave_type_id', 'left')
            ->where('b.employee_id', $employee_id)
            ->where('b.year', $year)
            ->where('lt.name', 'Casual Leave');
        $used = $this->db->get()->row();
        $stats['leave_days_used'] = ($used && $used->used !== null) ? (float) $used->used : 0;

        // Pending leave requests
        $this->db->where('employee_id', $employee_id)->where('status', 'pending');
        $stats['pending_leaves'] = $this->db->count_all_results(db_prefix() . 'hr_leave_requests');

        // Approved leave requests this year
        $this->db->where('employee_id', $employee_id)
            ->where('status', 'approved')
            ->where('YEAR(from_date)', $year);
        $stats['approved_leaves'] = $this->db->count_all_results(db_prefix() . 'hr_leave_requests');

        // Active loan - 'approved' and 'active' both mean "still owed" everywhere
        // else in the module (Loans_model, loans/view.php, loans/table.php): a loan
        // stays 'approved' until its first payroll deduction flips it to 'active'
        // (see Payroll_model), so checking 'active' alone missed a just-approved
        // loan that hasn't had a payroll cycle run against it yet.
        $loan = $this->db->where('employee_id', $employee_id)
            ->where_in('status', ['approved', 'active'])
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get(db_prefix() . 'hr_loans')->row();
        $stats['active_loan'] = $loan;

        // Pending overtime this month
        $this->db->where('employee_id', $employee_id)->where('status', 'pending');
        $stats['pending_overtime'] = $this->db->count_all_results(db_prefix() . 'hr_overtime');

        // Approved overtime days this month - overtime is tracked per day (see
        // Overtime_model), not hourly, so this counts approved day-rows rather
        // than summing the unused legacy 'hours' column (never populated by
        // Overtime_model::request()/update(), always 0).
        $this->db->where('employee_id', $employee_id)
            ->where('status', 'approved')
            ->where('MONTH(overtime_date)', $month)
            ->where('YEAR(overtime_date)', $year);
        $stats['approved_overtime_days'] = $this->db->count_all_results(db_prefix() . 'hr_overtime');

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

        // Upcoming / ongoing trainings (enrolled, not yet completed) - the
        // first day's start_time (if one was set) comes from hr_training_sessions,
        // since hr_training itself only stores the date, not a time-of-day.
        $this->db->select('t.id, t.title, t.start_date, t.end_date, t.status, s.start_time', false)
            ->from(db_prefix() . 'hr_training_participants p')
            ->join(db_prefix() . 'hr_training t', 't.id = p.training_id', 'left')
            ->join(db_prefix() . 'hr_training_sessions s', 's.training_id = t.id AND s.session_date = t.start_date', 'left')
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

    // ─── Holiday reminder (day-before, all employees) ──────────────────────

    // Cron-driven (see hr_module_cron_tasks()) - checks once per calendar day,
    // once the configured time-of-day (holiday_reminder_time, default 09:00)
    // has passed, whether tomorrow is a government/company holiday, and if so
    // emails every active mapped employee. No-op unless holiday_reminder_enabled
    // is turned on in Settings. Guarded by holiday_reminder_last_sent_date so
    // later cron ticks the same day don't resend - a ">=" time check (rather
    // than an exact-minute match) since the cron polling cadence isn't
    // guaranteed to land on the exact configured minute.
    //
    // The configured time (and "today"/"tomorrow") are always evaluated in
    // Bangladesh Standard Time (Asia/Dhaka, UTC+6) regardless of the server's
    // own timezone, so a non-technical HR user can enter a plain local time
    // (e.g. 4:01 PM) and get exactly that, even if the server itself runs on
    // UTC or any other zone.
    public function send_holiday_reminder()
    {
        if ($this->get_setting('holiday_reminder_enabled') != '1') {
            return false;
        }

        $now_bd = new DateTime('now', new DateTimeZone('Asia/Dhaka'));

        $target_time = $this->get_setting('holiday_reminder_time', '09:00');
        if ($now_bd->format('H:i') < $target_time) {
            return false;
        }

        $today = $now_bd->format('Y-m-d');
        if ($this->get_setting('holiday_reminder_last_sent_date') === $today) {
            return false;
        }
        // Mark as checked for today regardless of whether a holiday is found,
        // so this only ever evaluates once per calendar day.
        $this->save_settings(['holiday_reminder_last_sent_date' => $today]);

        $CI = &get_instance();
        $CI->load->model('hr_module/Holidays_model');
        $tomorrow = (clone $now_bd)->modify('+1 day')->format('Y-m-d');
        // Only the FIRST day of a holiday's range triggers this - a multi-day
        // holiday (e.g. a 6-day Eid period stored as one row) must only ever
        // fire one "day before" reminder, not once per day of the range.
        $holiday  = $CI->Holidays_model->get_holiday_starting_on($tomorrow);
        if (!$holiday) {
            return false;
        }

        $day_name   = $CI->Holidays_model->day_name_label($holiday);
        $date_label = $CI->Holidays_model->date_label($holiday);

        $CI->load->model('hr_module/Email_templates_model');
        $placeholders = [
            '{holiday_name}' => $holiday->name,
            '{day_name}'     => $day_name,
            '{date}'         => $date_label,
        ];
        $tpl = $CI->Email_templates_model->render('holiday_reminder', $placeholders);

        $result = $this->send_leave_announcement($tpl->subject, $tpl->body);
        $this->send_whatsapp_announcement('holiday_reminder', $placeholders);
        if ($result) {
            $CI->Holidays_model->mark_announcement_sent($holiday->id);
        }
        return $result;
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

    // employee_id => gender map for active employees - used by Leave::apply() to
    // filter gender-restricted leave types (e.g. Maternity/Paternity) client-side
    // once a staff member picks who they're applying on behalf of.
    public function get_active_employees_genders()
    {
        $rows = $this->db->select('id, gender')->where('status', 1)
            ->get(db_prefix() . 'hr_employees')->result();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = strtolower((string) $row->gender);
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
