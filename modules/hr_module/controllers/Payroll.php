<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payroll extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Payroll_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Employees_model');
        $this->load->model('hr_module/Departments_model');
        $this->load->model('hr_module/Shifts_model');
        $this->load->model('hr_module/Email_templates_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_payroll') && staff_cant('view_own', 'hr_payroll')) access_denied('hr_payroll');
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'payroll/table'));
            return;
        }
        // Payroll_items::index() requires global 'view' on hr_payroll - a
        // view_own-only employee clicking this link from here would just land
        // on access_denied, so only show it to someone who can actually open it.
        // Same reasoning for the department filter - table.php already forces
        // the list back to just the caller's own records otherwise.
        $can_view_all = is_admin() || staff_can('view', 'hr_payroll');
        $data['can_manage_items']  = $can_view_all;
        $data['show_dept_filter']  = $can_view_all;
        $data['title']       = _l('hr_payroll_list');
        $data['departments'] = $can_view_all ? $this->Departments_model->get_active() : [];
        $data['employees']   = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/payroll/index', $data);
    }

    public function generate()
    {
        if (staff_cant('create', 'hr_payroll')) access_denied('hr_payroll');

        if ($this->input->post()) {
            $emp_ids = $this->input->post('employee_ids');
            $month   = (int) $this->input->post('pay_month');
            $year    = (int) $this->input->post('pay_year');
            $notes   = $this->input->post('notes', true);

            if (empty($emp_ids) || !$month || !$year) {
                set_alert('danger', 'Please select employees, month, and year.');
                redirect(admin_url('hr_module/payroll/generate'));
            }

            $success = $skipped = 0;
            foreach ($emp_ids as $eid) {
                $r = $this->Payroll_model->generate((int) $eid, $month, $year, ['notes' => $notes]);
                if ($r['success']) $success++; else $skipped++;
            }
            if ($success > 0 && $this->Hr_module_model->notifications_enabled('notify_payroll')) {
                $this->_notify_payroll_generated($month, $year, $success, $skipped);
            }
            set_alert('success', "Generated: $success payroll(s). Skipped (already exists): $skipped.");
            redirect(admin_url('hr_module/payroll'));
        }

        $data['title']     = _l('hr_payroll_generate');
        $data['employees'] = $this->Employees_model->get_all(['status' => 'active']);
        $this->load->view('hr_module/payroll/generate', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_payroll') && staff_cant('view_own', 'hr_payroll')) access_denied('hr_payroll');
        $payroll = $this->Payroll_model->get($id);
        if (!$payroll) show_404();
        if (!staff_can('view', 'hr_payroll') && staff_can('view_own', 'hr_payroll')) {
            if ((int) $payroll->employee_id !== hr_get_own_employee_id()) access_denied('hr_payroll');
        }
        $period_from = sprintf('%04d-%02d-01', $payroll->pay_year, $payroll->pay_month);
        $period_to   = date('Y-m-t', strtotime($period_from));

        $data['title']   = _l('hr_payroll_view');
        $data['payroll'] = $payroll;
        $data['details'] = $this->Payroll_model->get_details($id);
        $data['shift_summary'] = $this->Shifts_model->get_employee_shift_summary($payroll->employee_id, $period_from, $period_to);
        $this->load->view('hr_module/payroll/view', $data);
    }

    public function mark_paid($id)
    {
        if (staff_cant('edit', 'hr_payroll')) access_denied('hr_payroll');
        if ($this->input->post()) {
            $method = $this->input->post('payment_method');
            $date   = $this->input->post('payment_date') ? to_sql_date($this->input->post('payment_date')) : date('Y-m-d');
            $result = $this->Payroll_model->mark_paid($id, $method, $date);
            if ($this->input->is_ajax_request()) {
                echo json_encode($result);
                return;
            }
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        }
        redirect(admin_url('hr_module/payroll/view/' . $id));
    }

    // Undoes mark_paid() - only ever needed to correct a mistaken/incorrect
    // payment (e.g. it was finalized against since-changed settings). Reverses
    // the loan repayment it actually applied and sends it back to draft.
    public function revert_to_draft($id)
    {
        if (staff_cant('edit', 'hr_payroll')) access_denied('hr_payroll');
        $result = $this->Payroll_model->revert_to_draft($id);
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/payroll/view/' . $id));
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_payroll')) access_denied('hr_payroll');
        $result = $this->Payroll_model->delete($id);
        if ($result['success']) set_alert('success', _l('hr_deleted_successfully'));
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/payroll'));
    }

    public function slip($id)
    {
        if (staff_cant('view', 'hr_payroll')) access_denied('hr_payroll');
        $payroll = $this->Payroll_model->get($id);
        if (!$payroll) show_404();
        $data['payroll']  = $payroll;
        $data['details']  = $this->Payroll_model->get_details($id);
        $data['settings'] = $this->Hr_module_model->get_all_settings();
        $this->load->view('hr_module/payroll/slip', $data);
    }

    // Notifies whoever can view payroll that a batch was just generated -
    // gated by the "Notify on Payroll Generation" Settings toggle.
    private function _notify_payroll_generated($month, $year, $success, $skipped)
    {
        $period = date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year;

        $placeholders = [
            '{period}'         => $period,
            '{success_count}'  => $success,
            '{skipped_count}'  => $skipped,
        ];
        $tpl  = $this->Email_templates_model->render('payroll_generated', $placeholders);
        $link = admin_url('hr_module/payroll');

        $this->Hr_module_model->send_notification_email($tpl->subject, $tpl->body, $link);
        $this->Hr_module_model->notify_by_permission(
            'view', 'hr_payroll',
            'not_hr_payroll_generated',
            'hr_module/payroll',
            [$period]
        );
    }
}
