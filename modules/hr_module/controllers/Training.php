<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Training extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Training_model');
        $this->load->model('hr_module/Hr_module_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_training') && staff_cant('view_own', 'hr_training')) access_denied('hr_training');
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'training/table'));
            return;
        }
        $data['title'] = _l('hr_training_list');
        $this->load->view('hr_module/training/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_training')) access_denied('hr_training');
        if ($this->input->post()) {
            $data = $this->_post_data();
            $this->_handle_attachment($data);
            $result = $this->Training_model->add($data);
            if ($result['success']) {
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/training/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/training/add'));
        }
        $data['title']    = _l('hr_training_add');
        $data['training'] = null;
        $this->load->view('hr_module/training/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_training')) access_denied('hr_training');
        $training = $this->Training_model->get($id);
        if (!$training) show_404();
        if ($this->input->post()) {
            $data = $this->_post_data();
            $this->_handle_attachment($data);
            $result = $this->Training_model->update($data, $id);
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
            redirect(admin_url('hr_module/training/view/' . $id));
        }
        $data['title']    = _l('hr_training_edit');
        $data['training'] = $training;
        $this->load->view('hr_module/training/form', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_training') && staff_cant('view_own', 'hr_training')) access_denied('hr_training');
        $training = $this->Training_model->get($id);
        if (!$training) show_404();
        $data['title']        = _l('hr_training_view');
        $data['training']     = $training;
        $data['participants'] = $this->Training_model->get_participants($id);
        $data['employees']    = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/training/view', $data);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_training')) access_denied('hr_training');
        $result = $this->Training_model->delete($id);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/training'));
    }

    public function enroll($id)
    {
        if (staff_cant('edit', 'hr_training')) access_denied('hr_training');
        if ($this->input->is_ajax_request()) {
            $emp_ids = $this->input->post('employee_ids') ?: [];
            $result  = $this->Training_model->enroll($id, $emp_ids);
            echo json_encode($result);
            return;
        }
        redirect(admin_url('hr_module/training/view/' . $id));
    }

    public function remove_participant($training_id, $employee_id)
    {
        if (staff_cant('edit', 'hr_training')) access_denied('hr_training');
        $this->Training_model->remove_participant($training_id, $employee_id);
        set_alert('success', 'Participant removed.');
        redirect(admin_url('hr_module/training/view/' . $training_id));
    }

    public function mark_completed($training_id, $employee_id)
    {
        if (staff_cant('edit', 'hr_training')) access_denied('hr_training');
        $date = $this->input->post('completion_date') ?: date('Y-m-d');
        $this->Training_model->mark_completed($training_id, $employee_id, $date);
        set_alert('success', 'Marked as completed.');
        redirect(admin_url('hr_module/training/view/' . $training_id));
    }

    private function _post_data()
    {
        return [
            'title'       => $this->input->post('title', true),
            'trainer'     => $this->input->post('trainer', true),
            'venue'       => $this->input->post('venue', true),
            'start_date'  => $this->input->post('start_date'),
            'end_date'    => $this->input->post('end_date'),
            'cost'        => $this->input->post('cost'),
            'capacity'    => $this->input->post('capacity'),
            'description' => $this->input->post('description', true),
            'status'      => $this->input->post('status'),
        ];
    }

    private function _handle_attachment(&$data)
    {
        if (empty($_FILES['attachment']['name'])) return;
        $path = FCPATH . 'uploads/hr_module/training/';
        if (!is_dir($path)) mkdir($path, 0755, true);
        $this->load->library('upload', [
            'upload_path'   => $path,
            'allowed_types' => 'pdf|doc|docx|ppt|pptx|jpg|png',
            'max_size'      => 5120,
            'encrypt_name'  => true,
        ]);
        if ($this->upload->do_upload('attachment')) {
            $data['attachment'] = $this->upload->data('file_name');
        }
    }
}
