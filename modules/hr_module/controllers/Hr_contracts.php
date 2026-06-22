<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Hr_contracts extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Hr_contracts_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Departments_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_contracts')) access_denied('hr_contracts');
        if ($this->input->is_ajax_request() && !$this->input->post()) {
            $this->app->get_table_data(module_views_path('hr_module', 'contracts/table'));
            return;
        }
        $data['title']         = _l('hr_contract_list');
        $data['departments']   = $this->Departments_model->get_active();
        $data['employees']     = $this->Hr_module_model->get_active_employees_dropdown();
        $data['expiring_soon'] = $this->Hr_contracts_model->get_expiring_soon(30);
        $this->load->view('hr_module/contracts/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_contracts')) access_denied('hr_contracts');
        if ($this->input->post()) {
            $data = $this->_post_data();
            $this->_handle_attachment($data);
            $result = $this->Hr_contracts_model->add($data);
            if ($result['success']) {
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/hr_contracts/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/hr_contracts/add'));
        }
        $data['title']     = _l('hr_contract_add');
        $data['contract']  = null;
        $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/contracts/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_contracts')) access_denied('hr_contracts');
        $contract = $this->Hr_contracts_model->get($id);
        if (!$contract) show_404();
        if ($this->input->post()) {
            $data = $this->_post_data();
            $this->_handle_attachment($data);
            $result = $this->Hr_contracts_model->update($data, $id);
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
            redirect(admin_url('hr_module/hr_contracts/view/' . $id));
        }
        $data['title']     = _l('hr_contract_edit');
        $data['contract']  = $contract;
        $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/contracts/form', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_contracts')) access_denied('hr_contracts');
        $contract = $this->Hr_contracts_model->get($id);
        if (!$contract) show_404();
        $data['title']    = _l('hr_contract_view');
        $data['contract'] = $contract;
        $this->load->view('hr_module/contracts/view', $data);
    }

    public function sign($id)
    {
        if (staff_cant('edit', 'hr_contracts')) access_denied('hr_contracts');
        $date   = $this->input->post('signed_date') ?: date('Y-m-d');
        $result = $this->Hr_contracts_model->mark_signed($id, $date);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/hr_contracts/view/' . $id));
    }

    public function set_status($id)
    {
        if (staff_cant('edit', 'hr_contracts')) access_denied('hr_contracts');
        $status = $this->input->post('status');
        if ($status) $this->Hr_contracts_model->set_status($id, $status);
        redirect(admin_url('hr_module/hr_contracts/view/' . $id));
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_contracts')) access_denied('hr_contracts');
        $result = $this->Hr_contracts_model->delete($id);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/hr_contracts'));
    }

    private function _post_data()
    {
        return [
            'employee_id'   => $this->input->post('employee_id'),
            'title'         => $this->input->post('title', true),
            'contract_type' => $this->input->post('contract_type'),
            'start_date'    => $this->input->post('start_date'),
            'end_date'      => $this->input->post('end_date'),
            'value'         => $this->input->post('value'),
            'content'       => $this->input->post('content', true),
            'status'        => $this->input->post('status'),
            'signed'        => $this->input->post('signed'),
            'signed_date'   => $this->input->post('signed_date'),
            'notes'         => $this->input->post('notes', true),
        ];
    }

    private function _handle_attachment(&$data)
    {
        if (empty($_FILES['attachment']['name'])) return;
        $path = FCPATH . 'uploads/hr_module/contracts/';
        if (!is_dir($path)) mkdir($path, 0755, true);
        $this->load->library('upload', [
            'upload_path'   => $path,
            'allowed_types' => 'pdf|doc|docx',
            'max_size'      => 10240,
            'encrypt_name'  => true,
        ]);
        if ($this->upload->do_upload('attachment')) {
            $data['attachment'] = $this->upload->data('file_name');
        }
    }
}
