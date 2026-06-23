<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leave extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Leave_model');
        $this->load->model('hr_module/Employees_model');
        $this->load->model('hr_module/Hr_module_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_leave') && staff_cant('view_own', 'hr_leave')) {
            access_denied('hr_leave');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'leave/table'));
        }
        $data['title']        = _l('hr_leave_list');
        $data['leave_types']  = $this->Leave_model->get_active_types();
        $this->load->model('hr_module/Departments_model');
        $data['departments']  = $this->Departments_model->get_active();
        $this->load->view('hr_module/leave/index', $data);
    }

    public function apply()
    {
        if (staff_cant('create', 'hr_leave')) {
            access_denied('hr_leave');
        }
        // Any non-admin, non-global-viewer applies only for themselves
        $own_only   = !is_admin() && !staff_can('view', 'hr_leave');
        $own_emp_id = $own_only ? hr_get_own_employee_id() : 0;

        if ($this->input->post()) {
            // Convert datepicker format (DD-MM-YYYY or any locale) to SQL YYYY-MM-DD
            $from = date('Y-m-d', strtotime($this->input->post('from_date')));
            $to   = date('Y-m-d', strtotime($this->input->post('to_date')));
            $half_day = (int) $this->input->post('is_half_day');
            $days     = $this->Leave_model->calculate_days($from, $to, $half_day);

            // view_own users can only apply for themselves — ignore any spoofed employee_id
            $posted_emp_id = (int) $this->input->post('employee_id');
            $resolved_emp_id = $own_only ? $own_emp_id : $posted_emp_id;

            $data = [
                'employee_id'   => $resolved_emp_id,
                'leave_type_id' => (int) $this->input->post('leave_type_id'),
                'from_date'     => $from,
                'to_date'       => $to,
                'total_days'    => $days,
                'is_half_day'   => $half_day,
                'reason'        => $this->input->post('reason', true),
            ];

            // Handle attachment
            if (!empty($_FILES['attachment']['name'])) {
                $upload_path = FCPATH . 'uploads/hr_module/leaves/';
                if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);
                $this->load->library('upload', [
                    'upload_path'   => $upload_path,
                    'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx',
                    'max_size'      => 4096,
                    'encrypt_name'  => true,
                ]);
                if ($this->upload->do_upload('attachment')) {
                    $data['attachment'] = $this->upload->data('file_name');
                }
            }

            $result = $this->Leave_model->apply($data);
            if ($result['success']) {
                set_alert('success', _l('hr_leave_applied_msg'));
                redirect(admin_url('hr_module/leave/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/leave/apply'));
        }

        $this->load->model('hr_module/Holidays_model');
        $year = (int) date('Y');

        $data['title']          = _l('hr_leave_add');
        $data['leave_types']    = $this->Leave_model->get_active_types();
        $data['own_only']       = $own_only;
        $data['own_emp_id']     = $own_emp_id;
        $data['holidays_json']  = json_encode($this->Holidays_model->get_as_json($year));
        $data['weekly_off_json']= json_encode($this->Holidays_model->get_weekly_off_days());

        if ($own_only) {
            $emp = $this->Employees_model->get($own_emp_id);
            $data['employees'] = $own_emp_id && $emp
                ? [$own_emp_id => $emp->first_name . ' ' . $emp->last_name]
                : [];
        } else {
            $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        }
        $this->load->view('hr_module/leave/apply', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_leave') && staff_cant('view_own', 'hr_leave')) {
            access_denied('hr_leave');
        }
        $request = $this->Leave_model->get_request($id);
        if (!$request) show_404();
        if (!staff_can('view', 'hr_leave') && staff_can('view_own', 'hr_leave')) {
            if ((int) $request->employee_id !== hr_get_own_employee_id()) {
                access_denied('hr_leave');
            }
        }

        $data['title']   = _l('hr_leave_view') . ' #' . $id;
        $data['request'] = $request;
        $data['balance'] = $this->Leave_model->get_balance(
            $request->employee_id, $request->leave_type_id, date('Y', strtotime($request->from_date))
        );
        $this->load->view('hr_module/leave/view', $data);
    }

    public function approve($id)
    {
        if (staff_cant('approve', 'hr_leave') && !is_admin()) {
            access_denied('hr_leave');
        }
        $notes  = $this->input->post('notes', true);
        $result = $this->Leave_model->approve($id, $notes);
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_leave_approved') : $result['message']);
        redirect(admin_url('hr_module/leave/view/' . $id));
    }

    public function reject($id)
    {
        if (staff_cant('approve', 'hr_leave') && !is_admin()) {
            access_denied('hr_leave');
        }
        $reason = $this->input->post('reason', true);
        $result = $this->Leave_model->reject($id, $reason);
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_leave_rejected_msg') : $result['message']);
        redirect(admin_url('hr_module/leave/view/' . $id));
    }

    public function cancel($id)
    {
        $result = $this->Leave_model->cancel($id);
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_leave_status_cancelled') : $result['message']);
        redirect(admin_url('hr_module/leave'));
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_leave')) {
            access_denied('hr_leave');
        }
        $this->Leave_model->delete_request($id);
        set_alert('success', _l('deleted_successfully', 'Leave Request'));
        redirect(admin_url('hr_module/leave'));
    }

    public function get_balance_ajax()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $emp_id  = $this->input->get('employee_id');
        $type_id = $this->input->get('leave_type_id');
        $year    = $this->input->get('year') ?: date('Y');
        $balance = $this->Leave_model->get_balance($emp_id, $type_id, $year);
        $remaining = 0;
        if ($balance) {
            $remaining = $balance->allocated_days + $balance->carry_forward_days - $balance->used_days;
        }
        echo json_encode(['balance' => $balance, 'remaining' => $remaining]);
    }
}
