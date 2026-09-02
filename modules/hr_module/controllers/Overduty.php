<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Overduty extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Overduty_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Departments_model');
        $this->load->model('hr_module/Employees_model');
        $this->load->model('hr_module/Email_templates_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_overtime') && staff_cant('view_own', 'hr_overtime') && staff_cant('view_department', 'hr_overtime')) access_denied('hr_overtime');
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'overduty/table'));
            return;
        }
        // The department filter only makes sense for someone who can see more
        // than their own requests - table.php already forces the list back to
        // just the caller's own records otherwise, so a company-wide department
        // picker for that viewer would just be confusing, unusable UI.
        $can_view_all = is_admin() || staff_can('view', 'hr_overtime');
        $data['title']            = _l('hr_overtime_list');
        $data['show_dept_filter'] = $can_view_all;
        $data['departments']      = $can_view_all ? $this->Departments_model->get_active() : [];
        $data['employees']        = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/overduty/index', $data);
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

            // Self-service employees may only request overtime within the
            // current calendar month - a past/future month's overtime would
            // sync into that month's payroll after it may already be settled,
            // conflicting with what was actually paid out.
            if ($own_only) {
                $current_ym = date('Y-m');
                foreach ($dates as $d) {
                    if (substr($d, 0, 7) !== $current_ym) {
                        set_alert('danger', _l('hr_overtime_current_month_only'));
                        redirect(admin_url('hr_module/overduty/request'));
                    }
                }
            }

            $result = $this->Overduty_model->request([
                'employee_id' => $employee_id,
                'dates'       => $dates,
                'reason'      => $this->input->post('reason', true),
            ]);
            if ($result['success']) {
                if ($this->Hr_module_model->notifications_enabled('notify_overtime')) {
                    $emp = $this->Employees_model->get($employee_id);
                    $placeholders = [
                        '{employee_name}' => $emp ? $emp->first_name . ' ' . $emp->last_name . ' (' . $emp->employee_code . ')' : 'Unknown',
                        '{dates}'         => implode(', ', array_map('_d', $dates)),
                        '{reason}'        => $this->input->post('reason', true) ?: '-',
                    ];
                    $tpl  = $this->Email_templates_model->render('overtime_apply', $placeholders);
                    $link = admin_url('hr_module/overduty/view/' . $result['id']);
                    $this->Hr_module_model->send_notification_email($tpl->subject, $tpl->body, $link);
                    $this->Hr_module_model->notify_by_permission(
                        'edit', 'hr_overtime',
                        'not_hr_overtime_applied',
                        'hr_module/overduty/view/' . $result['id'],
                        [$emp ? $emp->first_name . ' ' . $emp->last_name : 'Unknown']
                    );
                }
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/overduty/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/overduty/request'));
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
        $this->load->view('hr_module/overduty/form', $data);
    }

    public function edit($id)
    {
        $overtime = $this->Overduty_model->get($id);
        if (!$overtime) show_404();
        // Editing requires the full 'edit' capability - there is no
        // ownership-based self-edit exception for overduty requests.
        if (staff_cant('edit', 'hr_overtime')) access_denied('hr_overtime');

        // Self-service users can only edit their own request, and can't reassign it to someone else.
        $own_only = !staff_can('edit', 'hr_overtime');

        if ($this->input->post()) {
            $employee_id = $own_only ? $overtime->employee_id : (int) $this->input->post('employee_id');
            $result = $this->Overduty_model->update([
                'employee_id' => $employee_id,
                'dates'       => $this->_post_dates(),
                'reason'      => $this->input->post('reason', true),
            ], $id);
            if ($result['success']) {
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/overduty/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/overduty/edit/' . $id));
        }
        $data['title']      = _l('hr_overtime_edit');
        $data['own_only']   = $own_only;
        $data['own_emp_id'] = $overtime->employee_id;
        $data['employees']  = $own_only
            ? [$overtime->employee_id => $overtime->first_name . ' ' . $overtime->last_name]
            : $this->Hr_module_model->get_active_employees_dropdown();
        $data['overtime']   = $overtime;
        $data['dates']      = $this->Overduty_model->get_dates($id);
        $this->load->view('hr_module/overduty/form', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_overtime') && staff_cant('view_own', 'hr_overtime') && staff_cant('view_department', 'hr_overtime')) access_denied('hr_overtime');
        $overtime = $this->Overduty_model->get($id);
        if (!$overtime) show_404();
        if (!staff_can('view', 'hr_overtime')) {
            $allowed = staff_can('view_own', 'hr_overtime') && (int) $overtime->employee_id === hr_get_own_employee_id();
            if (!$allowed && staff_can('view_department', 'hr_overtime')) {
                $allowed = $overtime->employee_department_id && (int) $overtime->employee_department_id === hr_get_own_department_id();
            }
            if (!$allowed) access_denied('hr_overtime');
        }
        $data['title']            = _l('hr_overtime_view');
        $data['overtime']         = $overtime;
        $data['dates']            = $this->Overduty_model->get_dates($id);
        $data['can_soft_approve'] = staff_can('soft_approve', 'hr_overtime');
        $this->load->view('hr_module/overduty/view', $data);
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
        $result = $this->Overduty_model->preview($employee_id, $date);
        unset($result['amount'], $result['multiplier']);
        echo json_encode($result);
    }

    public function approve($id)
    {
        if (staff_cant('edit', 'hr_overtime')) access_denied('hr_overtime');
        $result = $this->Overduty_model->approve($id);
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/overduty/view/' . $id));
    }

    public function reject($id)
    {
        if (staff_cant('edit', 'hr_overtime')) access_denied('hr_overtime');
        $reason = $this->input->post('rejection_reason', true);
        $result = $this->Overduty_model->reject($id, $reason);
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/overduty/view/' . $id));
    }

    // Informational-only pre-approval step (see Overduty_model::soft_approve()) -
    // gated by its own 'soft_approve' capability, independent of 'edit' (which is
    // what gates the real approve/reject above).
    public function soft_approve($id)
    {
        if (staff_cant('soft_approve', 'hr_overtime')) access_denied('hr_overtime');
        $result = $this->Overduty_model->soft_approve($id);
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        if ($result['success']) set_alert('success', _l('hr_overtime_soft_approve'));
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/overduty/view/' . $id));
    }

    public function soft_reject($id)
    {
        if (staff_cant('soft_approve', 'hr_overtime')) access_denied('hr_overtime');
        $result = $this->Overduty_model->soft_reject($id);
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        if ($result['success']) set_alert('success', _l('hr_overtime_soft_reject'));
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/overduty/view/' . $id));
    }

    public function delete($id)
    {
        $overtime = $this->Overduty_model->get($id);
        if (!$overtime) show_404();
        // Deleting requires the full 'delete' capability - there is no
        // ownership-based self-delete exception for overduty requests.
        if (staff_cant('delete', 'hr_overtime')) access_denied('hr_overtime');

        $result = $this->Overduty_model->delete($id);
        if ($result['success']) set_alert('success', _l('hr_deleted_successfully'));
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/overduty'));
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
