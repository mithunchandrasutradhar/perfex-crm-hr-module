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
        $this->load->model('hr_module/Zkteco_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_employees') && staff_cant('view_own', 'hr_employees')) {
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

            $device_user_id = $this->input->post('device_user_id', true);
            if (empty($device_user_id)) {
                set_alert('danger', 'Please enter the Device Number.');
                redirect(admin_url('hr_module/employees/add'));
            }
            $prefix = $this->Hr_module_model->get_setting('employee_id_prefix', 'EMP');
            $code   = $prefix . $device_user_id;
            if ($this->Employees_model->code_exists($code)) {
                set_alert('danger', 'This Device Number is already in use by another employee.');
                redirect(admin_url('hr_module/employees/add'));
            }

            $data                  = $this->_prepare_post_data();
            $data['employee_code'] = $code;
            $data['staff_id']      = $staff_id;
            $data['status']        = $this->_staff_active_status($staff_id);

            if (!empty($_FILES['photo']['name'])) {
                $upload = $this->Employees_model->handle_photo_upload();
                if ($upload['success']) $data['photo'] = $upload['filename'];
            }

            $id = $this->Employees_model->add($data);
            if ($id) {
                // Allocate this year's leave balances immediately so the new
                // employee shows up on the Leave Balances page and can apply for
                // leave right away, instead of being invisible until the next
                // site-wide "Allocate" run.
                $this->load->model('hr_module/Leave_model');
                $this->Leave_model->allocate_for_employee($id);
                $this->Zkteco_model->set_employee_device_mapping(
                    $id, $this->input->post('zkteco_device_id'), $device_user_id
                );
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
        $data['devices']         = $this->Zkteco_model->get_devices(true);
        $data['device_mappings'] = [];
        $data['employee_id_prefix'] = $this->Hr_module_model->get_setting('employee_id_prefix', 'EMP');
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
            // The Edit form's staff select is disabled (read-only) once a
            // profile exists - always trust the employee's existing link,
            // not whatever "staff_id" the request carries, so a tampered
            // POST can't silently re-point this profile at a different
            // staff account.
            $staff_id = (int) $employee->staff_id;

            $data = $this->_prepare_post_data();
            $data['staff_id'] = $staff_id;
            $data['status']   = $this->_staff_active_status($staff_id);

            $device_user_id = $this->input->post('device_user_id', true);
            if (empty($device_user_id)) {
                set_alert('danger', 'Please enter the Device Number.');
                redirect(admin_url('hr_module/employees/edit/' . $id));
            }
            $prefix = $this->Hr_module_model->get_setting('employee_id_prefix', 'EMP');
            $code   = $prefix . $device_user_id;
            if ($this->Employees_model->code_exists($code, $id)) {
                set_alert('danger', 'This Device Number is already in use by another employee.');
                redirect(admin_url('hr_module/employees/edit/' . $id));
            }
            $data['employee_code'] = $code;

            if (!empty($_FILES['photo']['name'])) {
                $upload = $this->Employees_model->handle_photo_upload($employee->photo);
                if ($upload['success']) $data['photo'] = $upload['filename'];
            }

            $this->Employees_model->update($data, $id);
            $this->Zkteco_model->set_employee_device_mapping(
                $id, $this->input->post('zkteco_device_id'), $device_user_id
            );
            set_alert('success', _l('hr_employee_updated'));
            redirect(admin_url('hr_module/employees/view/' . $id));
        }

        // For edit, show all active staff (including the currently linked one)
        $data['title']        = _l('hr_employee_edit') . ' — ' . $employee->first_name . ' ' . $employee->last_name;
        $data['employee']     = $employee;
        $data['departments']  = $this->Departments_model->get_active();
        $data['designations'] = $this->Designations_model->get_active();
        $data['devices']         = $this->Zkteco_model->get_devices(true);
        $data['device_mappings'] = $this->Zkteco_model->get_mappings_for_employee($id);
        $data['employee_id_prefix'] = $this->Hr_module_model->get_setting('employee_id_prefix', 'EMP');
        $data['staff_members'] = $this->db->select('staffid, firstname, lastname, email, phonenumber, profile_image')
            ->where('active', 1)
            ->order_by('firstname', 'ASC')
            ->get(db_prefix() . 'staff')->result();
        $data['next_code'] = '';
        $this->load->view('hr_module/employees/form', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_employees') && staff_cant('view_own', 'hr_employees')) {
            access_denied('hr_employees');
        }
        $employee = $this->Employees_model->get($id);
        if (!$employee) show_404();
        if (!staff_can('view', 'hr_employees') && staff_can('view_own', 'hr_employees')) {
            if ((int) $employee->staff_id !== (int) get_staff_user_id()) {
                access_denied('hr_employees');
            }
        }
        $data['title']    = $employee->first_name . ' ' . $employee->last_name;
        $data['employee'] = $employee;
        $this->load->view('hr_module/employees/view', $data);
    }

    // Serves the HR profile photo inline (not force_download - it's rendered as
    // an <img>). Same audience as view()/edit(): anyone who can see this
    // employee's profile at all, plus edit/create holders (the edit form shows
    // the current photo too).
    public function photo($id)
    {
        $can_see = staff_can('view', 'hr_employees') || staff_can('edit', 'hr_employees') || staff_can('create', 'hr_employees');
        if (!$can_see && !staff_can('view_own', 'hr_employees')) {
            access_denied('hr_employees');
        }
        $employee = $this->Employees_model->get($id);
        if (!$employee) show_404();
        if (!$can_see && (int) $employee->staff_id !== (int) get_staff_user_id()) {
            access_denied('hr_employees');
        }
        if (empty($employee->photo)) show_404();
        $path = FCPATH . 'uploads/hr_module/employees/' . basename($employee->photo);
        if (!is_file($path)) show_404();

        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'][$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=86400');
        readfile($path);
        exit;
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

    private function _prepare_post_data()
    {
        // Note: first_name, last_name, email, phone are synced from tblstaff in the model
        return [
            'gender'                  => $this->input->post('gender', true),
            'date_of_birth'           => to_sql_date($this->input->post('date_of_birth')) ?: null,
            'address'                 => $this->input->post('address', true),
            'department_id'           => $this->input->post('department_id') ?: null,
            'designation_id'          => $this->input->post('designation_id') ?: null,
            'joining_date'            => to_sql_date($this->input->post('joining_date')) ?: null,
            'end_date'                => to_sql_date($this->input->post('end_date')) ?: null,
            'basic_salary'            => (float) $this->input->post('basic_salary'),
            // Blank means "no custom limit" - the employee falls back to the
            // site-wide default from Settings > General (see Loans::apply()).
            'max_loan_amount'         => $this->input->post('max_loan_amount') !== '' && $this->input->post('max_loan_amount') !== null
                ? (float) $this->input->post('max_loan_amount')
                : null,
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
            'notes'                   => $this->input->post('notes', true),
        ];
    }

    // Employee status always mirrors the linked staff account's active status -
    // it is not independently editable in the HR profile.
    private function _staff_active_status($staff_id)
    {
        $staff = get_staff($staff_id);
        return $staff ? (int) $staff->active : 0;
    }
}
