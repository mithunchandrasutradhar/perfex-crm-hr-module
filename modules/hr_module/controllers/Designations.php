<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Designations extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Designations_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_departments')) {
            access_denied('hr_departments');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'designations/table'));
        }
        $data['title'] = _l('hr_designation_list');
        $this->load->model('hr_module/Departments_model');
        $data['departments'] = $this->Departments_model->get_active();
        $this->load->view('hr_module/designations/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_departments')) {
            access_denied('hr_departments');
        }
        if (!$this->input->post()) {
            show_404();
        }
        $data = [
            'name'          => $this->input->post('name', true),
            'department_id' => $this->input->post('department_id') ?: null,
            'description'   => $this->input->post('description', true),
            'status'        => $this->input->post('status') ? 1 : 0,
        ];
        $id = $this->Designations_model->add($data);
        if ($id) {
            echo json_encode(['success' => true, 'message' => _l('hr_designation_added'), 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'message' => _l('hr_error_save_failed')]);
        }
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_departments')) {
            access_denied('hr_departments');
        }
        if ($this->input->is_ajax_request() && !$this->input->post()) {
            $row = $this->Designations_model->get($id);
            echo json_encode($row);
            return;
        }
        if (!$this->input->post()) {
            show_404();
        }
        $data = [
            'name'          => $this->input->post('name', true),
            'department_id' => $this->input->post('department_id') ?: null,
            'description'   => $this->input->post('description', true),
            'status'        => $this->input->post('status') ? 1 : 0,
        ];
        $success = $this->Designations_model->update($data, $id);
        if ($success) {
            echo json_encode(['success' => true, 'message' => _l('hr_designation_updated')]);
        } else {
            echo json_encode(['success' => false, 'message' => _l('hr_error_save_failed')]);
        }
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_departments')) {
            access_denied('hr_departments');
        }
        $result = $this->Designations_model->delete($id);
        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => _l('hr_designation_deleted')]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
    }
}
