<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Branches extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Branches_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_departments')) {
            access_denied('hr_departments');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'branches/table'));
        }
        $data['title'] = _l('hr_branch_list');
        $this->load->view('hr_module/branches/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_departments')) {
            access_denied('hr_departments');
        }
        if ($this->input->post()) {
            $id = $this->Branches_model->add($this->_post_data());
            if ($id) {
                set_alert('success', _l('hr_branch_added'));
                redirect(admin_url('hr_module/branches'));
            }
            set_alert('danger', _l('hr_error_save_failed'));
            redirect(admin_url('hr_module/branches/add'));
        }

        $data['title']  = _l('hr_branch_add');
        $data['branch'] = null;
        $this->load->view('hr_module/branches/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_departments')) {
            access_denied('hr_departments');
        }
        $branch = $this->Branches_model->get($id);
        if (!$branch) show_404();

        if ($this->input->post()) {
            $success = $this->Branches_model->update($this->_post_data(), $id);
            if ($success) {
                set_alert('success', _l('hr_branch_updated'));
            } else {
                set_alert('danger', _l('hr_error_save_failed'));
            }
            redirect(admin_url('hr_module/branches'));
        }

        $data['title']  = _l('hr_branch_edit');
        $data['branch'] = $branch;
        $this->load->view('hr_module/branches/form', $data);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_departments')) {
            access_denied('hr_departments');
        }
        $result = $this->Branches_model->delete($id);
        if ($result['success']) {
            set_alert('success', _l('hr_branch_deleted'));
        } else {
            set_alert('danger', $result['message']);
        }
        redirect(admin_url('hr_module/branches'));
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
