<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Shifts extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Shifts_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Employees_model');
        $this->load->model('hr_module/Departments_model');
        $this->load->model('hr_module/Email_templates_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_shifts') && staff_cant('view_own', 'hr_shifts')) {
            access_denied('hr_shifts');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'shifts/table'));
            return;
        }
        $data['title']       = _l('hr_shift_list');
        $data['departments'] = $this->Departments_model->get_active();
        $data['shift_types'] = $this->Shifts_model->get_active_types();
        $data['can_manage']  = is_admin() || staff_can('create', 'hr_shifts') || staff_can('edit', 'hr_shifts');
        $this->load->view('hr_module/shifts/index', $data);
    }

    public function apply()
    {
        if (staff_cant('create', 'hr_shifts')) {
            access_denied('hr_shifts');
        }
        // Any non-admin, non-global-viewer applies only for themselves
        $own_only   = !is_admin() && !staff_can('view', 'hr_shifts');
        $own_emp_id = $own_only ? hr_get_own_employee_id() : 0;

        if ($this->input->post()) {
            $posted_emp_id = (int) $this->input->post('employee_id');
            $employee_id   = $own_only ? $own_emp_id : $posted_emp_id;
            $reason        = $this->input->post('reason', true);

            $dates          = (array) $this->input->post('dates');
            $shift_type_ids = (array) $this->input->post('shift_type_ids');

            // Each date is its own single-day assignment (with its own shift
            // selection), not a shared range - so a request can mix, e.g.,
            // Night on one date and Morning on another.
            $created_ids = [];
            $errors      = [];
            foreach ($dates as $i => $raw_date) {
                $date          = to_sql_date($raw_date);
                $shift_type_id = (int) ($shift_type_ids[$i] ?? 0);
                if (!$date || !$shift_type_id) {
                    continue;
                }
                $result = $this->Shifts_model->apply([
                    'employee_id'   => $employee_id,
                    'shift_type_id' => $shift_type_id,
                    'from_date'     => $date,
                    'to_date'       => $date,
                    'reason'        => $reason,
                    'created_by'    => get_staff_user_id(),
                ]);
                if ($result['success']) {
                    $created_ids[] = $result['id'];
                } else {
                    $errors[] = _d($date) . ': ' . $result['message'];
                }
            }

            if (empty($created_ids) && empty($errors)) {
                set_alert('danger', 'Please add at least one date and select a shift.');
                redirect(admin_url('hr_module/shifts/apply'));
            }

            if ($this->Hr_module_model->notifications_enabled('notify_shift')) {
                foreach ($created_ids as $id) {
                    $this->_notify_submitted($id);
                }
            }

            if (!empty($created_ids)) {
                set_alert('success', _l('hr_shift_applied_msg'));
            }
            if (!empty($errors)) {
                set_alert('danger', implode('<br>', $errors));
            }

            if (count($created_ids) === 1 && empty($errors)) {
                redirect(admin_url('hr_module/shifts/view/' . $created_ids[0]));
            }
            redirect(admin_url('hr_module/shifts'));
        }

        $data['title']       = _l('hr_shift_add_request');
        $data['own_only']    = $own_only;
        $data['own_emp_id']  = $own_emp_id;
        $data['shift_types'] = $this->Shifts_model->get_active_types();

        if ($own_only) {
            $emp = $this->Employees_model->get($own_emp_id);
            $data['employees'] = $own_emp_id && $emp
                ? [$own_emp_id => $emp->first_name . ' ' . $emp->last_name]
                : [];
        } else {
            $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        }
        $this->load->view('hr_module/shifts/form', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_shifts') && staff_cant('view_own', 'hr_shifts')) {
            access_denied('hr_shifts');
        }
        $assignment = $this->Shifts_model->get($id);
        if (!$assignment) show_404();
        if (!staff_can('view', 'hr_shifts') && staff_can('view_own', 'hr_shifts')) {
            if ((int) $assignment->employee_id !== hr_get_own_employee_id()) {
                access_denied('hr_shifts');
            }
        }
        $data['title']       = _l('hr_shift_view');
        $data['assignment']  = $assignment;
        $data['can_approve'] = is_admin() || staff_can('approve', 'hr_shifts');
        $this->load->view('hr_module/shifts/view', $data);
    }

    public function approve($id)
    {
        if (staff_cant('approve', 'hr_shifts') && !is_admin()) {
            access_denied('hr_shifts');
        }
        $result = $this->Shifts_model->approve($id);
        if ($result['success'] && $this->Hr_module_model->notifications_enabled('notify_shift')) {
            $this->_notify_status($id, 'approved');
        }
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_shift_approved_msg') : $result['message']);
        redirect(admin_url('hr_module/shifts/view/' . $id));
    }

    public function reject($id)
    {
        if (staff_cant('approve', 'hr_shifts') && !is_admin()) {
            access_denied('hr_shifts');
        }
        $reason = $this->input->post('reason', true);
        $result = $this->Shifts_model->reject($id, $reason);
        if ($result['success'] && $this->Hr_module_model->notifications_enabled('notify_shift')) {
            $this->_notify_status($id, 'rejected');
        }
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_shift_rejected_msg') : $result['message']);
        redirect(admin_url('hr_module/shifts/view/' . $id));
    }

    public function delete($id)
    {
        $assignment = $this->Shifts_model->get($id);
        if (!$assignment) show_404();
        $is_owner   = (int) $assignment->employee_id === hr_get_own_employee_id();
        $can_delete = staff_can('delete', 'hr_shifts')
            || ($is_owner && staff_can('create', 'hr_shifts') && $assignment->status === 'pending');
        if (!$can_delete) {
            access_denied('hr_shifts');
        }
        $this->Shifts_model->delete($id);
        set_alert('success', _l('hr_deleted_successfully'));
        redirect(admin_url('hr_module/shifts'));
    }

    // Notifies whoever can approve shifts that a new request needs review.
    private function _notify_submitted($id)
    {
        $a = $this->Shifts_model->get($id);
        if (!$a) return;

        $range = _d($a->from_date) . ($a->to_date !== $a->from_date ? ' - ' . _d($a->to_date) : '');
        $placeholders = [
            '{employee_name}' => $a->employee_name . ' (' . $a->employee_code . ')',
            '{department}'    => $a->department_name ?: '-',
            '{designation}'   => $a->designation_name ?: '-',
            '{shift_name}'    => $a->shift_name,
            '{date_range}'    => $range,
            '{reason}'        => $a->reason ?: '-',
        ];
        $tpl  = $this->Email_templates_model->render('shift_applied', $placeholders);
        $link = admin_url('hr_module/shifts/view/' . $id);
        $this->Hr_module_model->send_notification_email($tpl->subject, $tpl->body, $link);
        $this->Hr_module_model->notify_by_permission(
            'approve', 'hr_shifts',
            'not_hr_shift_applied',
            'hr_module/shifts/view/' . $id,
            [$a->employee_name]
        );
    }

    // Emails/notifies the requesting employee once their shift assignment
    // request has been approved/rejected. No-op if no email/staff account on file.
    private function _notify_status($id, $status)
    {
        $a = $this->Shifts_model->get($id);
        if (!$a) return;

        $range = _d($a->from_date) . ($a->to_date !== $a->from_date ? ' - ' . _d($a->to_date) : '');

        if (!empty($a->employee_email)) {
            if ($status === 'approved') {
                $template_key = 'shift_approved';
                $placeholders = [
                    '{employee_name}' => $a->employee_name,
                    '{shift_name}'    => $a->shift_name,
                    '{date_range}'    => $range,
                ];
            } else {
                $template_key = 'shift_rejected';
                $placeholders = [
                    '{employee_name}' => $a->employee_name,
                    '{shift_name}'    => $a->shift_name,
                    '{date_range}'    => $range,
                    '{reason}'        => $a->rejection_reason ?: '-',
                ];
            }
            $tpl  = $this->Email_templates_model->render($template_key, $placeholders);
            $link = admin_url('hr_module/shifts/view/' . $id);
            $this->Hr_module_model->send_employee_email($a->employee_email, $tpl->subject, $tpl->body, $link);
        }
        $this->Hr_module_model->notify_staff($a->employee_staff_id, 'not_hr_shift_status', 'hr_module/shifts/view/' . $id, [$status]);
    }
}
