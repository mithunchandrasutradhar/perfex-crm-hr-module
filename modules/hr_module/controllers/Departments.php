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
        $this->load->view('hr_module/departments/index', $data);
    }

    // Departments are now managed through Perfex CRM's core setup.
    public function add()    { redirect(admin_url('hr_module/departments')); }
    public function edit($id){ redirect(admin_url('hr_module/departments')); }
    public function delete($id){ redirect(admin_url('hr_module/departments')); }
}
