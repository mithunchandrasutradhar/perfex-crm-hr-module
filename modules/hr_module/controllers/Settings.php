<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Settings extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Hr_module_model');
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
            $this->_save_settings();
            set_alert('success', _l('hr_settings_saved'));
            redirect(admin_url('hr_module/settings'));
        }

        $data['title']    = _l('hr_module_settings');
        $data['settings'] = $this->Hr_module_model->get_all_settings();

        $this->load->view('hr_module/settings/index', $data);
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

        $this->_save_settings();

        echo json_encode(['success' => true, 'message' => _l('hr_settings_saved')]);
    }

    private function _save_settings()
    {
        $allowed_keys = [
            'working_days_per_week',
            'working_hours_per_day',
            'office_start_time',
            'office_end_time',
            'late_threshold_minutes',
            'default_overtime_rate',
            'overtime_holiday_rate',
            'employee_id_prefix',
            'fiscal_year_start_month',
            'payroll_generation_day',
            'currency',
            'notify_leave_apply',
            'notify_leave_approve',
            'notify_loan_apply',
            'notify_payroll',
            'zkteco_enabled',
            'zkteco_sync_interval',
        ];

        // input->post() returns NULL (not false) for a key absent from the
        // request - e.g. an unchecked checkbox. Must check for NULL here, or
        // every unchecked/omitted field is saved as NULL instead of '0'.
        $save_data = [];
        foreach ($allowed_keys as $key) {
            $posted = $this->input->post($key);
            $save_data[$key] = $posted !== null ? $posted : '0';
        }

        // Admin-only, deliberately not in $allowed_keys: only a full admin may
        // flip this, and only when the checkbox is actually checked.
        if (is_admin()) {
            $save_data['allow_data_removal_on_uninstall'] = $this->input->post('allow_data_removal_on_uninstall') !== null ? '1' : '0';
        }

        $this->Hr_module_model->save_settings($save_data);
    }
}
