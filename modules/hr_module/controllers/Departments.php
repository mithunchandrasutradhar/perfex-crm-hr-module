<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Departments extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Departments_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_departments')) {
            access_denied('hr_departments');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'departments/table'));
        }
        $data['title'] = _l('hr_department_list');
        $this->load->model('hr_module/Hr_module_model');
        $data['staff_members'] = $this->db->select('staffid, CONCAT(firstname, " ", lastname) as fullname')
            ->where('active', 1)->get(db_prefix() . 'staff')->result();
        $data['departments'] = $this->Departments_model->get_active();
        $this->load->view('hr_module/departments/index', $data);
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
            'code'          => $this->input->post('code', true),
            'parent_id'     => $this->input->post('parent_id') ?: null,
            'head_staff_id' => $this->input->post('head_staff_id') ?: null,
            'description'   => $this->input->post('description', true),
            'status'        => $this->input->post('status') ? 1 : 0,
        ];
        $id = $this->Departments_model->add($data);
        if ($id) {
            echo json_encode(['success' => true, 'message' => _l('hr_department_added'), 'id' => $id]);
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
            $dept = $this->Departments_model->get($id);
            echo json_encode($dept);
            return;
        }
        if (!$this->input->post()) {
            show_404();
        }
        $data = [
            'name'          => $this->input->post('name', true),
            'code'          => $this->input->post('code', true),
            'parent_id'     => $this->input->post('parent_id') ?: null,
            'head_staff_id' => $this->input->post('head_staff_id') ?: null,
            'description'   => $this->input->post('description', true),
            'status'        => $this->input->post('status') ? 1 : 0,
        ];
        $success = $this->Departments_model->update($data, $id);
        if ($success) {
            echo json_encode(['success' => true, 'message' => _l('hr_department_updated')]);
        } else {
            echo json_encode(['success' => false, 'message' => _l('hr_error_save_failed')]);
        }
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_departments')) {
            access_denied('hr_departments');
        }
        $result = $this->Departments_model->delete($id);
        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => _l('hr_department_deleted')]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
    }
}
