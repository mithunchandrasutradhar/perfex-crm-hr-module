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

        $allowed_keys = [
            'working_days_per_week',
            'working_hours_per_day',
            'office_start_time',
            'office_end_time',
            'late_threshold_minutes',
            'default_overtime_rate',
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

        $save_data = [];
        foreach ($allowed_keys as $key) {
            $save_data[$key] = $this->input->post($key) !== false ? $this->input->post($key) : '0';
        }

        $this->Hr_module_model->save_settings($save_data);

        echo json_encode(['success' => true, 'message' => _l('hr_settings_saved')]);
    }
}
