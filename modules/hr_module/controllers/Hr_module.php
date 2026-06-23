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

        if (is_admin() || staff_can('view', 'hr_employees')) {
            // Admin / HR manager — global company stats
            $data['is_own'] = false;
            $data['stats']  = $this->Hr_module_model->get_dashboard_stats();
        } else {
            // Regular staff — always show their own personal data
            $employee_id = hr_get_own_employee_id();
            if ($employee_id) {
                $data['is_own']      = true;
                $data['employee_id'] = $employee_id;
                $data['stats']       = $this->Hr_module_model->get_employee_dashboard_stats($employee_id);
            } else {
                $data['is_own']     = false;
                $data['no_profile'] = true;
                $data['stats']      = [];
            }
        }

        $this->load->view('hr_module/dashboard/index', $data);
    }
}
