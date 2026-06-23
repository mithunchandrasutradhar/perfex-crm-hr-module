<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leave_types extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Leave_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_leave')) {
            access_denied('hr_leave');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'leave_types/table'));
        }
        $data['title'] = _l('hr_leave_types_list');
        $this->load->view('hr_module/leave_types/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_leave')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]); return;
        }
        if (!$this->input->post()) show_404();
        $data = [
            'name'                => $this->input->post('name', true),
            'days_per_year'       => (int) $this->input->post('days_per_year'),
            'carry_forward'       => $this->input->post('carry_forward') ? 1 : 0,
            'max_carry_forward_days' => (int) $this->input->post('max_carry_forward_days'),
            'requires_attachment' => $this->input->post('requires_attachment') ? 1 : 0,
            'allow_half_day'      => $this->input->post('allow_half_day') ? 1 : 0,
            'description'         => $this->input->post('description', true),
            'status'              => $this->input->post('status') ? 1 : 0,
        ];
        $id = $this->Leave_model->add_type($data);
        echo json_encode($id
            ? ['success' => true,  'message' => _l('hr_leave_type_added')]
            : ['success' => false, 'message' => _l('hr_error_save_failed')]);
    }

    public function edit($id)
    {
        if ($this->input->is_ajax_request() && !$this->input->post()) {
            if (staff_cant('view', 'hr_leave')) { echo json_encode(null); return; }
            echo json_encode($this->Leave_model->get_type($id));
            return;
        }
        if (staff_cant('edit', 'hr_leave')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]); return;
        }
        if (!$this->input->post()) show_404();
        $data = [
            'name'                => $this->input->post('name', true),
            'days_per_year'       => (int) $this->input->post('days_per_year'),
            'carry_forward'       => $this->input->post('carry_forward') ? 1 : 0,
            'max_carry_forward_days' => (int) $this->input->post('max_carry_forward_days'),
            'requires_attachment' => $this->input->post('requires_attachment') ? 1 : 0,
            'allow_half_day'      => $this->input->post('allow_half_day') ? 1 : 0,
            'description'         => $this->input->post('description', true),
            'status'              => $this->input->post('status') ? 1 : 0,
        ];
        $this->Leave_model->update_type($data, $id);
        echo json_encode(['success' => true, 'message' => _l('hr_leave_type_updated')]);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_leave')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]); return;
        }
        $result = $this->Leave_model->delete_type($id);
        echo json_encode($result);
    }
}
