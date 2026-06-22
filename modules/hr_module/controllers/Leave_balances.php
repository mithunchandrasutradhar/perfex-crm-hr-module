<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leave_balances extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Leave_model');
        $this->load->model('hr_module/Departments_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_leave')) {
            access_denied('hr_leave');
        }
        $year    = $this->input->get('year') ?: date('Y');
        $dept_id = $this->input->get('dept_id');

        $data['title']       = _l('hr_leave_balances_list');
        $data['balances']    = $this->Leave_model->get_all_balances($year, $dept_id);
        $data['year']        = $year;
        $data['departments'] = $this->Departments_model->get_active();
        $this->load->view('hr_module/leave_balances/index', $data);
    }

    public function allocate()
    {
        if (!is_admin() && staff_cant('approve', 'hr_leave')) {
            access_denied('hr_leave');
        }
        $year  = $this->input->post('year') ?: date('Y');
        $count = $this->Leave_model->allocate_balances($year);
        set_alert('success', $count . ' leave balance records allocated for ' . $year);
        redirect(admin_url('hr_module/leave_balances'));
    }
}
