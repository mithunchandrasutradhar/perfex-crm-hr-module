<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payroll_items extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Payroll_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_payroll')) access_denied('hr_payroll');
        $data['title'] = _l('hr_payroll_items_list');
        $data['items'] = $this->Payroll_model->get_items();
        $this->load->view('hr_module/payroll_items/index', $data);
    }

    public function add()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (staff_cant('create', 'hr_payroll')) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }
        $result = $this->Payroll_model->add_item($this->_post_data());
        echo json_encode($result);
    }

    public function edit($id)
    {
        if ($this->input->is_ajax_request() && !$this->input->post()) {
            echo json_encode($this->Payroll_model->get_item($id));
            return;
        }
        if (!$this->input->is_ajax_request()) show_404();
        if (staff_cant('edit', 'hr_payroll')) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }
        echo json_encode($this->Payroll_model->update_item($this->_post_data(), $id));
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_payroll')) access_denied('hr_payroll');
        $result = $this->Payroll_model->delete_item($id);
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/payroll_items'));
    }

    private function _post_data()
    {
        return [
            'name'             => $this->input->post('name', true),
            'type'             => $this->input->post('type'),
            'calculation_type' => $this->input->post('calculation_type'),
            'value'            => (float) $this->input->post('value'),
            'taxable'          => $this->input->post('taxable') ? 1 : 0,
            'description'      => $this->input->post('description', true),
            'status'           => $this->input->post('status') ? 1 : 0,
        ];
    }
}
