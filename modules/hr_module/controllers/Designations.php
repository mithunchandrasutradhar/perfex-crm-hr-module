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
        $this->load->view('hr_module/designations/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_departments')) {
            access_denied('hr_departments');
        }
        if ($this->input->post()) {
            $id = $this->Designations_model->add($this->_post_data());
            if ($id) {
                set_alert('success', _l('hr_designation_added'));
                redirect(admin_url('hr_module/designations'));
            }
            set_alert('danger', _l('hr_error_save_failed'));
            redirect(admin_url('hr_module/designations/add'));
        }

        $data['title']       = _l('hr_designation_add');
        $data['designation'] = null;
        $this->load->view('hr_module/designations/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_departments')) {
            access_denied('hr_departments');
        }
        $designation = $this->Designations_model->get($id);
        if (!$designation) show_404();

        if ($this->input->post()) {
            $success = $this->Designations_model->update($this->_post_data(), $id);
            if ($success) {
                set_alert('success', _l('hr_designation_updated'));
            } else {
                set_alert('danger', _l('hr_error_save_failed'));
            }
            redirect(admin_url('hr_module/designations'));
        }

        $data['title']       = _l('hr_designation_edit');
        $data['designation'] = $designation;
        $this->load->view('hr_module/designations/form', $data);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_departments')) {
            access_denied('hr_departments');
        }
        $result = $this->Designations_model->delete($id);
        if ($result['success']) {
            set_alert('success', _l('hr_designation_deleted'));
        } else {
            set_alert('danger', $result['message']);
        }
        redirect(admin_url('hr_module/designations'));
    }

    private function _post_data()
    {
        return [
            'name'        => $this->input->post('name', true),
            'description' => $this->input->post('description', true),
            'status'      => $this->input->post('status') ? 1 : 0,
        ];
    }
}
