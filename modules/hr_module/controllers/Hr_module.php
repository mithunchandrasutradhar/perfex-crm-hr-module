<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Hr_module extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Hr_module_model');
    }

    public function index()
    {
        $data['title']     = _l('hr_dashboard_title');
        $data['bodyclass'] = 'hr-module-dashboard';

        $is_manager  = is_admin() || staff_can('view', 'hr_employees');
        $employee_id = hr_get_own_employee_id();

        // An HR manager/admin is also a company employee themselves - if they
        // have their own linked employee profile they get BOTH dashboards (the
        // view renders a tab switch), not just the managerial one.
        $data['is_manager']  = $is_manager;
        $data['employee_id'] = $employee_id;
        $data['no_profile']  = !$is_manager && !$employee_id;

        // Set only when hr_module_require_employee_profile() (hr_module.php)
        // redirected here from another page for this exact reason - shows
        // which section they were trying to reach instead of the generic
        // dashboard title. Ignored otherwise (e.g. landing here directly).
        if ($data['no_profile']) {
            $blocked = $this->input->get('blocked');
            $labels  = hr_module_personal_controller_labels();
            if ($blocked && isset($labels[$blocked])) {
                $data['no_profile_title'] = $labels[$blocked];
            }
        }

        if ($is_manager) {
            $data['manager_stats'] = $this->Hr_module_model->get_dashboard_stats();
        }
        if ($employee_id) {
            $data['own_stats'] = $this->Hr_module_model->get_employee_dashboard_stats($employee_id);
        }

        $this->load->view('hr_module/dashboard/index', $data);
    }
}
