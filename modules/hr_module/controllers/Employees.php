<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Employees extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Employees_model');
        $this->load->model('hr_module/Departments_model');
        $this->load->model('hr_module/Designations_model');
        $this->load->model('hr_module/Hr_module_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_employees')) {
            access_denied('hr_employees');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'employees/table'));
        }
        $data['title']       = _l('hr_employee_list');
        $data['departments'] = $this->Departments_model->get_active();
        $this->load->view('hr_module/employees/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_employees')) {
            access_denied('hr_employees');
        }
        if ($this->input->post()) {
            $prefix = $this->Hr_module_model->get_setting('employee_id_prefix', 'EMP');
            $code   = $this->input->post('employee_code', true);
            if (empty($code)) {
                $code = $this->Employees_model->get_next_code($prefix);
            }
            if ($this->Employees_model->code_exists($code)) {
                set_alert('danger', 'Employee code already exists.');
                redirect(admin_url('hr_module/employees/add'));
            }
            $data = $this->_prepare_post_data();
            $data['employee_code'] = $code;

            // Handle photo upload
            if (!empty($_FILES['photo']['name'])) {
                $upload = $this->Employees_model->handle_photo_upload();
                if ($upload['success']) {
                    $data['photo'] = $upload['filename'];
                }
            }

            $id = $this->Employees_model->add($data);
            if ($id) {
                set_alert('success', _l('hr_employee_added'));
                redirect(admin_url('hr_module/employees/view/' . $id));
            }
            set_alert('danger', _l('hr_error_save_failed'));
            redirect(admin_url('hr_module/employees/add'));
        }

        $data['title']        = _l('hr_employee_add');
        $data['employee']     = null;
        $data['departments']  = $this->Departments_model->get_active();
        $data['designations'] = $this->Designations_model->get_active();
        $data['staff_members'] = $this->db->select('staffid, CONCAT(firstname," ",lastname) as fullname')
            ->where('active', 1)->get(db_prefix() . 'staff')->result();
        $data['next_code'] = $this->Employees_model->get_next_code(
            $this->Hr_module_model->get_setting('employee_id_prefix', 'EMP')
        );
        $this->load->view('hr_module/employees/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_employees')) {
            access_denied('hr_employees');
        }
        $employee = $this->Employees_model->get($id);
        if (!$employee) {
            show_404();
        }
        if ($this->input->post()) {
            $data = $this->_prepare_post_data();
            $code = $this->input->post('employee_code', true);
            if (!empty($code) && $this->Employees_model->code_exists($code, $id)) {
                set_alert('danger', 'Employee code already exists.');
                redirect(admin_url('hr_module/employees/edit/' . $id));
            }
            if (!empty($code)) {
                $data['employee_code'] = $code;
            }
            if (!empty($_FILES['photo']['name'])) {
                $upload = $this->Employees_model->handle_photo_upload($employee->photo);
                if ($upload['success']) {
                    $data['photo'] = $upload['filename'];
                }
            }
            $this->Employees_model->update($data, $id);
            set_alert('success', _l('hr_employee_updated'));
            redirect(admin_url('hr_module/employees/view/' . $id));
        }

        $data['title']        = _l('hr_employee_edit') . ' — ' . $employee->first_name . ' ' . $employee->last_name;
        $data['employee']     = $employee;
        $data['departments']  = $this->Departments_model->get_active();
        $data['designations'] = $this->Designations_model->get_active();
        $data['staff_members'] = $this->db->select('staffid, CONCAT(firstname," ",lastname) as fullname')
            ->where('active', 1)->get(db_prefix() . 'staff')->result();
        $data['next_code'] = '';
        $this->load->view('hr_module/employees/form', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_employees')) {
            access_denied('hr_employees');
        }
        $employee = $this->Employees_model->get($id);
        if (!$employee) {
            show_404();
        }
        $data['title']    = $employee->first_name . ' ' . $employee->last_name;
        $data['employee'] = $employee;
        $this->load->view('hr_module/employees/view', $data);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_employees')) {
            access_denied('hr_employees');
        }
        $success = $this->Employees_model->delete($id);
        if ($success) {
            set_alert('success', _l('hr_employee_deleted'));
        } else {
            set_alert('danger', _l('hr_error_delete_failed'));
        }
        redirect(admin_url('hr_module/employees'));
    }

    public function get_designations_by_dept()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $dept_id = $this->input->get('dept_id');
        $this->db->where('status', 1);
        if ($dept_id) {
            $this->db->where('department_id', $dept_id);
        }
        $this->db->order_by('name', 'ASC');
        $rows = $this->db->get(db_prefix() . 'hr_designations')->result();
        echo json_encode($rows);
    }

    private function _prepare_post_data()
    {
        return [
            'first_name'              => $this->input->post('first_name', true),
            'last_name'               => $this->input->post('last_name', true),
            'email'                   => $this->input->post('email', true),
            'phone'                   => $this->input->post('phone', true),
            'gender'                  => $this->input->post('gender', true),
            'date_of_birth'           => $this->input->post('date_of_birth') ?: null,
            'address'                 => $this->input->post('address', true),
            'department_id'           => $this->input->post('department_id') ?: null,
            'designation_id'          => $this->input->post('designation_id') ?: null,
            'staff_id'                => $this->input->post('staff_id') ?: null,
            'joining_date'            => $this->input->post('joining_date') ?: null,
            'end_date'                => $this->input->post('end_date') ?: null,
            'basic_salary'            => (float) $this->input->post('basic_salary'),
            'bank_name'               => $this->input->post('bank_name', true),
            'bank_account'            => $this->input->post('bank_account', true),
            'bank_branch'             => $this->input->post('bank_branch', true),
            'tin_number'              => $this->input->post('tin_number', true),
            'nid_number'              => $this->input->post('nid_number', true),
            'passport_number'         => $this->input->post('passport_number', true),
            'blood_group'             => $this->input->post('blood_group', true),
            'religion'                => $this->input->post('religion', true),
            'marital_status'          => $this->input->post('marital_status', true),
            'emergency_contact_name'  => $this->input->post('emergency_contact_name', true),
            'emergency_contact_phone' => $this->input->post('emergency_contact_phone', true),
            'status'                  => $this->input->post('status') ? 1 : 0,
            'notes'                   => $this->input->post('notes', true),
        ];
    }
}
