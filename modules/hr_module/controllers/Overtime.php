<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Overtime extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Overtime_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Departments_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_overtime') && staff_cant('view_own', 'hr_overtime')) access_denied('hr_overtime');
        if ($this->input->is_ajax_request() && !$this->input->post()) {
            $this->app->get_table_data(module_views_path('hr_module', 'overtime/table'));
            return;
        }
        $data['title']       = _l('hr_overtime_list');
        $data['departments'] = $this->Departments_model->get_active();
        $data['employees']   = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/overtime/index', $data);
    }

    public function request()
    {
        if (staff_cant('create', 'hr_overtime')) access_denied('hr_overtime');
        if ($this->input->post()) {
            $result = $this->Overtime_model->request($this->_post_data());
            if ($result['success']) {
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/overtime/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/overtime/request'));
        }
        $data['title']     = _l('hr_overtime_add');
        $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        $data['overtime']  = null;
        $this->load->view('hr_module/overtime/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_overtime')) access_denied('hr_overtime');
        $overtime = $this->Overtime_model->get($id);
        if (!$overtime) show_404();
        if ($this->input->post()) {
            $result = $this->Overtime_model->update($this->_post_data(), $id);
            if ($result['success']) {
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/overtime/view/' . $id));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/overtime/edit/' . $id));
        }
        $data['title']     = _l('hr_overtime_edit');
        $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        $data['overtime']  = $overtime;
        $this->load->view('hr_module/overtime/form', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_overtime') && staff_cant('view_own', 'hr_overtime')) access_denied('hr_overtime');
        $overtime = $this->Overtime_model->get($id);
        if (!$overtime) show_404();
        if (!staff_can('view', 'hr_overtime') && staff_can('view_own', 'hr_overtime')) {
            if ((int) $overtime->employee_id !== hr_get_own_employee_id()) access_denied('hr_overtime');
        }
        $data['title']    = _l('hr_overtime_view');
        $data['overtime'] = $overtime;
        $this->load->view('hr_module/overtime/view', $data);
    }

    public function approve($id)
    {
        if (staff_cant('edit', 'hr_overtime')) access_denied('hr_overtime');
        $result = $this->Overtime_model->approve($id);
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/overtime/view/' . $id));
    }

    public function reject($id)
    {
        if (staff_cant('edit', 'hr_overtime')) access_denied('hr_overtime');
        $reason = $this->input->post('rejection_reason', true);
        $result = $this->Overtime_model->reject($id, $reason);
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/overtime/view/' . $id));
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_overtime')) access_denied('hr_overtime');
        $result = $this->Overtime_model->delete($id);
        if ($result['success']) set_alert('success', _l('hr_deleted_successfully'));
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/overtime'));
    }

    private function _post_data()
    {
        return [
            'employee_id'     => (int) $this->input->post('employee_id'),
            'overtime_date'   => $this->input->post('overtime_date'),
            'hours'           => (float) $this->input->post('hours'),
            'rate_multiplier' => (float) $this->input->post('rate_multiplier'),
            'reason'          => $this->input->post('reason', true),
        ];
    }
}
