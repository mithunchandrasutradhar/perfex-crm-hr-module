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
        if (staff_cant('view', 'hr_employees')) access_denied('hr_employees');
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'employees/table'));
        }
        $data['title']       = _l('hr_employee_list');
        $data['departments'] = $this->Departments_model->get_active();
        $this->load->view('hr_module/employees/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_employees')) access_denied('hr_employees');

        if ($this->input->post()) {
            $staff_id = (int) $this->input->post('staff_id');
            if (!$staff_id) {
                set_alert('danger', 'Please select a staff member.');
                redirect(admin_url('hr_module/employees/add'));
            }
            $existing = $this->Employees_model->get_by_staff_id($staff_id);
            if ($existing) {
                set_alert('danger', 'This staff member already has an HR profile.');
                redirect(admin_url('hr_module/employees/add'));
            }

            $prefix = $this->Hr_module_model->get_setting('employee_id_prefix', 'EMP');
            $code   = $this->input->post('employee_code', true);
            if (empty($code)) $code = $this->Employees_model->get_next_code($prefix);
            if ($this->Employees_model->code_exists($code)) {
                set_alert('danger', 'Employee code already exists.');
                redirect(admin_url('hr_module/employees/add'));
            }

            $data                  = $this->_prepare_post_data();
            $data['employee_code'] = $code;
            $data['staff_id']      = $staff_id;

            if (!empty($_FILES['photo']['name'])) {
                $upload = $this->Employees_model->handle_photo_upload();
                if ($upload['success']) $data['photo'] = $upload['filename'];
            }

            $id = $this->Employees_model->add($data);
            if ($id) {
                set_alert('success', _l('hr_employee_added'));
                redirect(admin_url('hr_module/employees/view/' . $id));
            }
            set_alert('danger', _l('hr_error_save_failed'));
            redirect(admin_url('hr_module/employees/add'));
        }

        $data['title']         = _l('hr_employee_add');
        $data['employee']      = null;
        $data['departments']   = $this->Departments_model->get_active();
        $data['designations']  = $this->Designations_model->get_active();
        $data['staff_members'] = $this->Employees_model->get_unlinked_staff();
        $data['next_code']     = $this->Employees_model->get_next_code(
            $this->Hr_module_model->get_setting('employee_id_prefix', 'EMP')
        );
        $this->load->view('hr_module/employees/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_employees')) access_denied('hr_employees');
        $employee = $this->Employees_model->get($id);
        if (!$employee) show_404();

        if ($this->input->post()) {
            $staff_id = (int) $this->input->post('staff_id');
            if (!$staff_id) {
                set_alert('danger', 'Staff member link is required.');
                redirect(admin_url('hr_module/employees/edit/' . $id));
            }
            // If staff_id changed, make sure it's not already linked to another profile
            if ($staff_id != $employee->staff_id) {
                $conflict = $this->Employees_model->get_by_staff_id($staff_id);
                if ($conflict && $conflict->id != $id) {
                    set_alert('danger', 'This staff member already has an HR profile.');
                    redirect(admin_url('hr_module/employees/edit/' . $id));
                }
            }

            $data = $this->_prepare_post_data();
            $data['staff_id'] = $staff_id;
            $code = $this->input->post('employee_code', true);
            if (!empty($code) && $this->Employees_model->code_exists($code, $id)) {
                set_alert('danger', 'Employee code already exists.');
                redirect(admin_url('hr_module/employees/edit/' . $id));
            }
            if (!empty($code)) $data['employee_code'] = $code;

            if (!empty($_FILES['photo']['name'])) {
                $upload = $this->Employees_model->handle_photo_upload($employee->photo);
                if ($upload['success']) $data['photo'] = $upload['filename'];
            }

            $this->Employees_model->update($data, $id);
            set_alert('success', _l('hr_employee_updated'));
            redirect(admin_url('hr_module/employees/view/' . $id));
        }

        // For edit, show all active staff (including the currently linked one)
        $data['title']        = _l('hr_employee_edit') . ' — ' . $employee->first_name . ' ' . $employee->last_name;
        $data['employee']     = $employee;
        $data['departments']  = $this->Departments_model->get_active();
        $data['designations'] = $this->Designations_model->get_active();
        $data['staff_members'] = $this->db->select('staffid, firstname, lastname, email, phonenumber, profile_image')
            ->where('active', 1)
            ->order_by('firstname', 'ASC')
            ->get(db_prefix() . 'staff')->result();
        $data['next_code'] = '';
        $this->load->view('hr_module/employees/form', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_employees')) access_denied('hr_employees');
        $employee = $this->Employees_model->get($id);
        if (!$employee) show_404();
        $data['title']    = $employee->first_name . ' ' . $employee->last_name;
        $data['employee'] = $employee;
        $this->load->view('hr_module/employees/view', $data);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_employees')) access_denied('hr_employees');
        $success = $this->Employees_model->delete($id);
        if ($success) set_alert('success', _l('hr_employee_deleted'));
        else          set_alert('danger', _l('hr_error_delete_failed'));
        redirect(admin_url('hr_module/employees'));
    }

    // AJAX: return staff info for the selected staff_id
    public function get_staff_info()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = (int) $this->input->get('staff_id');
        $staff    = $this->db->select('staffid, firstname, lastname, email, phonenumber, profile_image')
            ->where('staffid', $staff_id)->get(db_prefix() . 'staff')->row();
        if (!$staff) {
            echo json_encode(['success' => false]);
            return;
        }
        $existing = $this->Employees_model->get_by_staff_id($staff_id);
        echo json_encode([
            'success'     => true,
            'firstname'   => $staff->firstname,
            'lastname'    => $staff->lastname,
            'email'       => $staff->email,
            'phone'       => $staff->phonenumber ?? '',
            'photo'       => $staff->profile_image ?? '',
            'has_profile' => (bool) $existing,
            'profile_id'  => $existing ? $existing->id : null,
        ]);
    }

    public function get_designations_by_dept()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $dept_id = $this->input->get('dept_id');
        $this->db->where('status', 1);
        if ($dept_id) $this->db->where('department_id', $dept_id);
        $rows = $this->db->order_by('name', 'ASC')->get(db_prefix() . 'hr_designations')->result();
        echo json_encode($rows);
    }

    private function _prepare_post_data()
    {
        // Note: first_name, last_name, email, phone are synced from tblstaff in the model
        return [
            'gender'                  => $this->input->post('gender', true),
            'date_of_birth'           => $this->input->post('date_of_birth') ?: null,
            'address'                 => $this->input->post('address', true),
            'department_id'           => $this->input->post('department_id') ?: null,
            'designation_id'          => $this->input->post('designation_id') ?: null,
            'joining_date'            => to_sql_date($this->input->post('joining_date')) ?: null,
            'end_date'                => to_sql_date($this->input->post('end_date')) ?: null,
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
