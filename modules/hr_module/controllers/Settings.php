<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Settings extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Shifts_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_settings') && !is_admin()) {
            access_denied('hr_settings');
        }

        // Fallback for when the page's AJAX submit handler didn't run (JS blocked,
        // disabled, or failed to bind): the <form> posts here directly, so still
        // save it rather than silently re-rendering the old values.
        if ($this->input->post() && !$this->input->is_ajax_request()) {
            if (staff_cant('edit', 'hr_settings') && !is_admin()) {
                access_denied('hr_settings');
            }
            $result = $this->_save_settings();
            set_alert($result['success'] ? 'success' : 'danger', $result['success'] ? _l('hr_settings_saved') : $result['message']);
            redirect(admin_url('hr_module/settings'));
        }

        $data['title']       = _l('hr_module_settings');
        $data['settings']    = $this->Hr_module_model->get_all_settings();
        $data['admin_staff'] = $this->db->where('admin', 1)->where('active', 1)
            ->order_by('firstname', 'ASC')->get(db_prefix() . 'staff')->result();
        $data['shift_types'] = $this->Shifts_model->get_type();

        $this->load->view('hr_module/settings/index', $data);
    }

    // Sends a canned test message using whatever is currently typed into the
    // WhatsApp fields (even if not saved yet), so the admin can verify
    // credentials/targets without waiting for a real event.
    public function send_whatsapp_test()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (staff_cant('edit', 'hr_settings') && !is_admin()) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }

        $base_url = trim($this->input->post('base_url')) ?: 'https://waha.abutalha.com.bd';
        $session  = trim($this->input->post('session')) ?: 'default';
        $api_key  = trim($this->input->post('api_key'));
        $group_id = trim($this->input->post('group_id'));
        $phone    = trim($this->input->post('phone'));
        $targets  = array_filter([$group_id, $phone]);

        if (empty($targets)) {
            echo json_encode(['success' => false, 'message' => _l('hr_settings_whatsapp_no_target')]);
            return;
        }

        $this->load->library('hr_module/Waha_lib');
        $text = "*HR Module Test Message*\n\nThis is a test message from " . get_option('companyname') . "'s HR module to verify your WhatsApp notification settings.";

        $sent_any = false;
        $errors   = [];
        foreach ($targets as $target) {
            $result = $this->waha_lib->send_text($base_url, $session, $api_key, $target, $text);
            if ($result['success']) {
                $sent_any = true;
            } else {
                $errors[] = $target . ': ' . $result['message'];
            }
        }

        echo json_encode([
            'success' => $sent_any,
            'message' => $sent_any
                ? _l('hr_settings_whatsapp_test_sent')
                : (_l('hr_settings_whatsapp_test_failed') . (empty($errors) ? '' : ' (' . implode('; ', $errors) . ')')),
        ]);
    }

    // ── Shift Types (name + start/end time) ─────────────────────────────

    public function add_shift()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (staff_cant('edit', 'hr_settings') && !is_admin()) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }
        $name  = trim($this->input->post('name', true));
        $start = $this->input->post('start_time');
        $end   = $this->input->post('end_time');

        if (!$name || !$start || !$end) {
            echo json_encode(['success' => false, 'message' => 'Name, start time, and end time are required.']);
            return;
        }

        $id = $this->Shifts_model->add_type([
            'name'       => $name,
            'start_time' => date('H:i:s', strtotime($start)),
            'end_time'   => date('H:i:s', strtotime($end)),
        ]);
        echo json_encode(['success' => (bool) $id, 'id' => $id]);
    }

    public function edit_shift($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (staff_cant('edit', 'hr_settings') && !is_admin()) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }
        $name  = trim($this->input->post('name', true));
        $start = $this->input->post('start_time');
        $end   = $this->input->post('end_time');

        if (!$name || !$start || !$end) {
            echo json_encode(['success' => false, 'message' => 'Name, start time, and end time are required.']);
            return;
        }

        $updated = $this->Shifts_model->update_type([
            'name'       => $name,
            'start_time' => date('H:i:s', strtotime($start)),
            'end_time'   => date('H:i:s', strtotime($end)),
        ], (int) $id);
        echo json_encode(['success' => (bool) $updated]);
    }

    // Plain navigate-and-redirect (not AJAX) - matches the `_delete` class
    // convention used by every other delete action in this module, so it's
    // handled by Perfex's own global confirm-dialog handler in app.js.
    public function delete_shift($id)
    {
        if (staff_cant('edit', 'hr_settings') && !is_admin()) {
            access_denied('hr_settings');
        }
        $result = $this->Shifts_model->delete_type((int) $id);
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_deleted_successfully') : $result['message']);
        redirect(admin_url('hr_module/settings'));
    }

    public function save()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (staff_cant('edit', 'hr_settings') && !is_admin()) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }

        $result = $this->_save_settings();
        if (!$result['success']) {
            echo json_encode($result);
            return;
        }

        echo json_encode(['success' => true, 'message' => _l('hr_settings_saved')]);
    }

    private function _save_settings()
    {
        // The view's <input min/max> only enforces this client-side - a direct
        // POST could otherwise save anything (e.g. a negative late threshold,
        // or a payroll generation day of 999), so the same ranges are checked
        // here before anything is written.
        $numeric_ranges = [
            'working_days_per_week'          => [1, 7,    'Working days per week must be between 1 and 7.'],
            'working_hours_per_day'          => [1, 24,   'Working hours per day must be between 1 and 24.'],
            'late_threshold_minutes'         => [0, 120,  'Late threshold must be between 0 and 120 minutes.'],
            'default_overtime_rate'          => [1, 5,    'Overtime rate must be between 1 and 5.'],
            'overtime_holiday_rate'          => [1, 5,    'Holiday overtime rate must be between 1 and 5.'],
            'overtime_day_divisor'           => [1, 31,   'Overtime day divisor must be between 1 and 31.'],
            'shift_allowance_evening_amount' => [0, null, 'Evening shift allowance cannot be negative.'],
            'shift_allowance_night_amount'   => [0, null, 'Night shift allowance cannot be negative.'],
            'fiscal_year_start_month'        => [1, 12,   'Fiscal year start month must be between 1 and 12.'],
            'payroll_generation_day'         => [1, 31,   'Payroll generation day must be between 1 and 31.'],
            'default_max_loan_amount'        => [0, 99999999.99, 'Default maximum loan amount must be between 0 and 99,999,999.99.'],
        ];
        foreach ($numeric_ranges as $key => list($min, $max, $message)) {
            $posted = $this->input->post($key);
            if ($posted === null || $posted === '') continue;
            if (!is_numeric($posted) || $posted < $min || ($max !== null && $posted > $max)) {
                return ['success' => false, 'message' => $message];
            }
        }

        $allowed_keys = [
            'working_days_per_week',
            'working_hours_per_day',
            'office_start_time',
            'office_end_time',
            'late_threshold_minutes',
            'default_overtime_rate',
            'overtime_holiday_rate',
            'overtime_day_divisor',
            'shift_allowance_evening_amount',
            'shift_allowance_night_amount',
            'employee_id_prefix',
            'fiscal_year_start_month',
            'payroll_generation_day',
            'default_max_loan_amount',
            'currency',
            'notify_leave_apply',
            'notify_leave_approve',
            'notify_leave_cancellation',
            'notify_loan_apply',
            'notify_loan_approve',
            'notify_loan_deduction',
            'notify_overtime',
            'notify_helpdesk',
            'notify_shift',
            'notify_policy',
            'notify_training',
            'notify_payroll',
            'hr_notification_email',
            'zkteco_enabled',
            'aiface_enabled',
            'holiday_reminder_enabled',
            'holiday_reminder_time',
            'whatsapp_enabled',
            'whatsapp_base_url',
            'whatsapp_session',
            'whatsapp_api_key',
            'whatsapp_group_id',
            'whatsapp_phone_number',
            'whatsapp_notify_leave_announcement',
            'whatsapp_notify_leave_cancellation_announcement',
            'whatsapp_notify_holiday_reminder',
            'whatsapp_notify_policy_announcement',
        ];

        // input->post() returns NULL (not false) for a key absent from the
        // request - e.g. an unchecked checkbox. Must check for NULL here, or
        // every unchecked/omitted field is saved as NULL instead of '0'.
        $save_data = [];
        foreach ($allowed_keys as $key) {
            $posted = $this->input->post($key);
            // The API key is never re-rendered into the settings form (see
            // views/settings/index.php) so a blank submission means "left
            // untouched", not "clear it" - keep whatever is already saved.
            if ($key === 'whatsapp_api_key' && $posted === '') {
                $save_data[$key] = $this->Hr_module_model->get_setting('whatsapp_api_key', '');
                continue;
            }
            $save_data[$key] = $posted !== null ? $posted : '0';
        }

        // Multi-select (policy_approver_ids[]) posts as an array - store as CSV,
        // since hr_settings.setting_value is a plain string column.
        $approver_ids = $this->input->post('policy_approver_ids');
        $save_data['policy_approver_ids'] = is_array($approver_ids)
            ? implode(',', array_values(array_filter(array_map('intval', $approver_ids))))
            : '';
        if ($save_data['policy_approver_ids'] !== $this->Hr_module_model->get_setting('policy_approver_ids')) {
            log_activity('HR Policy Approvers Updated [Staff IDs: ' . ($save_data['policy_approver_ids'] ?: 'none') . ']');
        }

        // Admin-only, deliberately not in $allowed_keys: only a full admin may
        // flip this, and only when the checkbox is actually checked.
        if (is_admin()) {
            $save_data['allow_data_removal_on_uninstall'] = $this->input->post('allow_data_removal_on_uninstall') !== null ? '1' : '0';
        }

        $this->Hr_module_model->save_settings($save_data);
        return ['success' => true];
    }
}
