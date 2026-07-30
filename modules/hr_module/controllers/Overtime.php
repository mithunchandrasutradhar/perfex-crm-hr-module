<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Overtime extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Overtime_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Departments_model');
        $this->load->model('hr_module/Employees_model');
        $this->load->model('hr_module/Email_templates_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_overtime') && staff_cant('view_own', 'hr_overtime')) access_denied('hr_overtime');
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'overtime/table'));
            return;
        }
        $data['title']       = _l('hr_overtime_list');
        $data['departments'] = $this->Departments_model->get_active();
        $data['employees']   = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/overtime/index', $data);
    }

    public function request()
    {
        if (staff_cant('create', 'hr_overtime')) access_denied('hr_overtime');
        // Any non-admin, non-global-viewer requests only for themselves
        $own_only   = !is_admin() && !staff_can('view', 'hr_overtime');
        $own_emp_id = $own_only ? hr_get_own_employee_id() : 0;

        if ($this->input->post()) {
            $employee_id = $own_only ? $own_emp_id : (int) $this->input->post('employee_id');
            $dates       = $this->_post_dates();
            $result = $this->Overtime_model->request([
                'employee_id' => $employee_id,
                'dates'       => $dates,
                'reason'      => $this->input->post('reason', true),
            ]);
            if ($result['success']) {
                if ($this->Hr_module_model->notifications_enabled('notify_overtime')) {
                    $emp = $this->Employees_model->get($employee_id);
                    $tpl = $this->Email_templates_model->render('overtime_apply', [
                        '{employee_name}' => $emp ? $emp->first_name . ' ' . $emp->last_name . ' (' . $emp->employee_code . ')' : 'Unknown',
                        '{dates}'         => implode(', ', array_map('_d', $dates)),
                        '{reason}'        => $this->input->post('reason', true) ?: '-',
                    ]);
                    $this->Hr_module_model->send_notification_email(
                        $tpl->subject,
                        $tpl->body,
                        admin_url('hr_module/overtime/view/' . $result['id'])
                    );
                    $this->Hr_module_model->notify_by_permission(
                        'edit', 'hr_overtime',
                        'not_hr_overtime_applied',
                        'hr_module/overtime/view/' . $result['id'],
                        [$emp ? $emp->first_name . ' ' . $emp->last_name : 'Unknown']
                    );
                }
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/overtime/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/overtime/request'));
        }
        $data['title']     = _l('hr_overtime_add');
        $data['own_only']  = $own_only;
        $data['own_emp_id']= $own_emp_id;

        if ($own_only) {
            $emp = $this->Employees_model->get($own_emp_id);
            $data['employees'] = $own_emp_id && $emp
                ? [$own_emp_id => $emp->first_name . ' ' . $emp->last_name]
                : [];
        } else {
            $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        }
        $data['overtime']  = null;
        $this->load->view('hr_module/overtime/form', $data);
    }

    public function edit($id)
    {
        $overtime = $this->Overtime_model->get($id);
        if (!$overtime) show_404();
        $is_owner = (int) $overtime->employee_id === hr_get_own_employee_id();
        $can_edit = staff_can('edit', 'hr_overtime') || ($is_owner && staff_can('create', 'hr_overtime'));
        if (!$can_edit) access_denied('hr_overtime');

        // Self-service users can only edit their own request, and can't reassign it to someone else.
        $own_only = !staff_can('edit', 'hr_overtime');

        if ($this->input->post()) {
            $employee_id = $own_only ? $overtime->employee_id : (int) $this->input->post('employee_id');
            $result = $this->Overtime_model->update([
                'employee_id' => $employee_id,
                'dates'       => $this->_post_dates(),
                'reason'      => $this->input->post('reason', true),
            ], $id);
            if ($result['success']) {
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/overtime/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/overtime/edit/' . $id));
        }
        $data['title']      = _l('hr_overtime_edit');
        $data['own_only']   = $own_only;
        $data['own_emp_id'] = $overtime->employee_id;
        $data['employees']  = $own_only
            ? [$overtime->employee_id => $overtime->first_name . ' ' . $overtime->last_name]
            : $this->Hr_module_model->get_active_employees_dropdown();
        $data['overtime']   = $overtime;
        $data['dates']      = $this->Overtime_model->get_dates($id);
        $this->load->view('hr_module/overtime/form', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_overtime') && staff_cant('view_own', 'hr_overtime')) access_denied('hr_overtime');
        $overtime = $this->Overtime_model->get($id);
        if (!$overtime) show_404();
        if (!staff_can('view', 'hr_overtime') && staff_can('view_own', 'hr_overtime')) {
            if ((int) $overtime->employee_id !== hr_get_own_employee_id()) access_denied('hr_overtime');
        }
        $data['title']    = _l('hr_overtime_view');
        $data['overtime'] = $overtime;
        $data['dates']    = $this->Overtime_model->get_dates($id);
        $this->load->view('hr_module/overtime/view', $data);
    }

    public function preview()
    {
        if (staff_cant('create', 'hr_overtime') && staff_cant('edit', 'hr_overtime')) access_denied('hr_overtime');
        $employee_id = (int) $this->input->get('employee_id');
        $date        = to_sql_date($this->input->get('overtime_date'));
        if (!$employee_id || !$date) {
            echo json_encode(['eligible' => false, 'message' => _l('hr_overtime_not_eligible_date')]);
            return;
        }
        $result = $this->Overtime_model->preview($employee_id, $date);
        unset($result['amount'], $result['multiplier']);
        echo json_encode($result);
    }

    public function approve($id)
    {
        if (staff_cant('edit', 'hr_overtime')) access_denied('hr_overtime');
        $result = $this->Overtime_model->approve($id);
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/overtime/view/' . $id));
    }

    public function reject($id)
    {
        if (staff_cant('edit', 'hr_overtime')) access_denied('hr_overtime');
        $reason = $this->input->post('rejection_reason', true);
        $result = $this->Overtime_model->reject($id, $reason);
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/overtime/view/' . $id));
    }

    public function delete($id)
    {
        $overtime = $this->Overtime_model->get($id);
        if (!$overtime) show_404();
        $is_owner = (int) $overtime->employee_id === hr_get_own_employee_id();
        $can_delete = staff_can('delete', 'hr_overtime')
            || ($is_owner && staff_can('create', 'hr_overtime') && $overtime->status === 'pending');
        if (!$can_delete) access_denied('hr_overtime');

        $result = $this->Overtime_model->delete($id);
        if ($result['success']) set_alert('success', _l('hr_deleted_successfully'));
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/overtime'));
    }

    // Overtime dates are submitted as overtime_date[] on the "request" form (one employee
    // can log several overtime days in a month in a single submission) - dedup and drop blanks.
    private function _post_dates()
    {
        $dates = $this->input->post('overtime_date');
        if (!is_array($dates)) {
            $dates = $dates ? [$dates] : [];
        }
        $dates = array_map('to_sql_date', array_filter($dates));
        return array_values(array_unique(array_filter($dates)));
    }
}
