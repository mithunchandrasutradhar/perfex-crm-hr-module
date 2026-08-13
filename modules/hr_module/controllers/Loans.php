<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Loans extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Loans_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Departments_model');
        $this->load->model('hr_module/Email_templates_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_loans') && staff_cant('view_own', 'hr_loans')) access_denied('hr_loans');
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'loans/table'));
            return;
        }
        // The department filter only makes sense for someone who can see more
        // than their own loans - table.php already forces the list back to just
        // the caller's own records otherwise.
        $can_view_all = is_admin() || staff_can('view', 'hr_loans');
        $data['title']            = _l('hr_loan_list');
        $data['show_dept_filter'] = $can_view_all;
        $data['departments']      = $can_view_all ? $this->Departments_model->get_active() : [];
        $data['employees']        = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/loans/index', $data);
    }

    public function apply()
    {
        if (staff_cant('create', 'hr_loans')) access_denied('hr_loans');

        // Any non-admin, non-global-viewer applies only for themselves
        $own_only   = !is_admin() && !staff_can('view', 'hr_loans');
        $own_emp_id = $own_only ? hr_get_own_employee_id() : 0;

        if ($this->input->post()) {
            // view_own users can only apply for themselves — ignore any spoofed employee_id
            $posted_emp_id   = (int) $this->input->post('employee_id');
            $resolved_emp_id = $own_only ? $own_emp_id : $posted_emp_id;

            // hr_loans.amount is decimal(15,2) - an unvalidated amount beyond that
            // (or a huge amount divided by a tiny installment, blowing up the
            // computed repayment_months past what its int column can hold) hits an
            // uncaught "out of range" DB error on insert instead of a clean message.
            $posted_amount = (float) $this->input->post('amount');
            if ($posted_amount <= 0 || $posted_amount > 99999999.99) {
                set_alert('danger', 'Loan amount must be greater than 0 and no more than 99,999,999.99.');
                redirect(admin_url('hr_module/loans/apply'));
            }

            // max_loan_amount is a revolving cap on TOTAL current exposure, not a
            // per-request cap - what's actually available is the ceiling minus
            // whatever's still outstanding on approved/active loans.
            $capacity = $this->_get_remaining_loan_capacity($resolved_emp_id);
            if ($posted_amount > $capacity['remaining']) {
                // _l()'s $label arg IS the %s substitution (it sprintf()s internally) -
                // an array of labels fills multiple %s placeholders in order.
                set_alert('danger', _l('hr_loan_exceeds_remaining_capacity', [
                    number_format(max(0, $capacity['remaining']), 2),
                    number_format($capacity['exposure'], 2),
                    number_format($capacity['max'], 2),
                ]));
                redirect(admin_url('hr_module/loans/apply'));
            }

            $data = [
                'employee_id'         => $resolved_emp_id,
                'amount'              => (float) $this->input->post('amount'),
                'reason'              => $this->input->post('reason', true),
                'repayment_months'    => (int) $this->input->post('repayment_months'),
                'monthly_installment' => (float) $this->input->post('monthly_installment'),
                'notes'               => $this->input->post('notes', true),
            ];

            // Handle attachment
            $upload_path = FCPATH . 'uploads/hr_module/loans/';
            if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);
            hr_lock_upload_dir($upload_path);
            if (!empty($_FILES['attachment']['name'])) {
                $this->load->library('upload', [
                    'upload_path'   => $upload_path,
                    'allowed_types' => 'pdf|jpg|jpeg|png',
                    'max_size'      => 2048,
                    'encrypt_name'  => true,
                ]);
                if ($this->upload->do_upload('attachment')) {
                    $data['attachment'] = $this->upload->data('file_name');
                }
            }

            $result = $this->Loans_model->apply($data);
            if ($result['success']) {
                if ($this->Hr_module_model->notifications_enabled('notify_loan_apply')) {
                    $this->load->model('hr_module/Employees_model');
                    $emp = $this->Employees_model->get($data['employee_id']);
                    $placeholders = [
                        '{employee_name}'         => $emp ? $emp->first_name . ' ' . $emp->last_name . ' (' . $emp->employee_code . ')' : 'Unknown',
                        '{department}'            => $emp && $emp->department_name ? $emp->department_name : '-',
                        '{designation}'           => $emp && $emp->designation_name ? $emp->designation_name : '-',
                        '{amount}'                => number_format($data['amount'], 2),
                        '{monthly_installment}'   => number_format($data['monthly_installment'], 2),
                        '{repayment_months}'      => $data['repayment_months'],
                        '{reason}'                => $data['reason'] ?: '-',
                    ];
                    $tpl  = $this->Email_templates_model->render('loan_apply', $placeholders);
                    $link = admin_url('hr_module/loans/view/' . $result['id']);
                    $this->Hr_module_model->send_notification_email($tpl->subject, $tpl->body, $link);
                    $this->Hr_module_model->notify_by_permission(
                        'edit', 'hr_loans',
                        'not_hr_loan_applied',
                        'hr_module/loans/view/' . $result['id'],
                        [$emp ? $emp->first_name . ' ' . $emp->last_name : 'Unknown']
                    );
                }
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/loans/view/' . $result['id']));
            } else {
                set_alert('danger', $result['message']);
                redirect(admin_url('hr_module/loans/apply'));
            }
        }

        $data['title']      = _l('hr_loan_add');
        $data['own_only']   = $own_only;
        $data['own_emp_id'] = $own_emp_id;
        $default_max = (float) $this->Hr_module_model->get_setting('default_max_loan_amount', 99999999.99);
        if ($own_only) {
            $this->load->model('hr_module/Employees_model');
            $emp = $this->Employees_model->get($own_emp_id);
            $data['employees'] = $own_emp_id && $emp
                ? [$own_emp_id => $emp->first_name . ' ' . $emp->last_name]
                : [];
            $capacity = $own_emp_id
                ? $this->_get_remaining_loan_capacity($own_emp_id)
                : ['max' => $default_max, 'exposure' => 0.0, 'remaining' => $default_max];
            $data['own_max_loan_amount']    = $capacity['max'];
            $data['own_loan_exposure']      = $capacity['exposure'];
            $data['own_remaining_capacity'] = $capacity['remaining'];
            $data['max_loan_json']          = json_encode([]);
            $data['exposure_json']          = json_encode([]);
        } else {
            $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
            // One query for every active employee's custom limit, and one
            // aggregate query for their current approved/active loan exposure -
            // rather than a separate lookup per dropdown option, the hint just
            // reads these preloaded maps locally when the employee selection changes.
            $rows = $this->db->select('id, max_loan_amount')->where('status', 1)
                ->get(db_prefix() . 'hr_employees')->result();
            $max_map = [];
            foreach ($rows as $row) {
                $max_map[$row->id] = ($row->max_loan_amount !== null && $row->max_loan_amount !== '')
                    ? (float) $row->max_loan_amount : $default_max;
            }
            $exposure_rows = $this->db->select('employee_id, SUM(outstanding) as exposure')
                ->where_in('status', ['approved', 'active'])
                ->group_by('employee_id')
                ->get(db_prefix() . 'hr_loans')->result();
            $exposure_map = [];
            foreach ($exposure_rows as $row) {
                $exposure_map[$row->employee_id] = (float) $row->exposure;
            }
            $data['max_loan_json']          = json_encode($max_map);
            $data['exposure_json']          = json_encode($exposure_map);
            $data['own_max_loan_amount']    = 0;
            $data['own_loan_exposure']      = 0;
            $data['own_remaining_capacity'] = 0;
        }
        $data['default_max_loan_amount'] = $default_max;
        $this->load->view('hr_module/loans/apply', $data);
    }

    // Effective loan ceiling for an employee: their own custom limit if set on
    // their HR profile, otherwise the site-wide default from Settings >
    // General - falling back to the absolute hr_loans.amount column ceiling if
    // neither is configured yet (e.g. right after upgrading, before anyone has
    // visited Settings).
    private function _get_max_loan_amount($employee_id)
    {
        $this->load->model('hr_module/Employees_model');
        $emp = $this->Employees_model->get($employee_id);
        if ($emp && $emp->max_loan_amount !== null && $emp->max_loan_amount !== '') {
            return (float) $emp->max_loan_amount;
        }
        return (float) $this->Hr_module_model->get_setting('default_max_loan_amount', 99999999.99);
    }

    // Sum of outstanding balance across this employee's approved/active loans.
    // Only these two statuses count against the cap: a pending request hasn't
    // been approved yet, a rejected one never happened, and a closed
    // (fully repaid) loan no longer ties up any of the employee's limit.
    private function _get_current_loan_exposure($employee_id)
    {
        $row = $this->db->select_sum('outstanding')
            ->where('employee_id', $employee_id)
            ->where_in('status', ['approved', 'active'])
            ->get(db_prefix() . 'hr_loans')->row();
        return ($row && $row->outstanding !== null) ? (float) $row->outstanding : 0.0;
    }

    // What's actually available to borrow right now: the employee's ceiling
    // (custom or site default) minus whatever they currently owe on
    // approved/active loans - this is what a new request is checked against,
    // not the flat ceiling itself.
    private function _get_remaining_loan_capacity($employee_id)
    {
        $max      = $this->_get_max_loan_amount($employee_id);
        $exposure = $this->_get_current_loan_exposure($employee_id);
        return ['max' => $max, 'exposure' => $exposure, 'remaining' => $max - $exposure];
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_loans') && staff_cant('view_own', 'hr_loans')) access_denied('hr_loans');
        $loan = $this->Loans_model->get($id);
        if (!$loan) show_404();
        if (!staff_can('view', 'hr_loans') && staff_can('view_own', 'hr_loans')) {
            if ((int) $loan->employee_id !== hr_get_own_employee_id()) access_denied('hr_loans');
        }
        $data['title']               = _l('hr_loan_view');
        $data['loan']                = $loan;
        $data['repayments']          = $this->Loans_model->get_repayments($id);
        $data['deduction_requests']  = $this->Loans_model->get_deduction_requests(['loan_id' => $id]);
        // Approving/rejecting a deduction request stays HR-only ('edit'); submitting one
        // (including this loan's own view() ownership check above already guarantees it's theirs).
        $data['can_manage_deductions'] = staff_can('edit', 'hr_loans') || staff_can('create', 'hr_loans');
        $this->load->view('hr_module/loans/view', $data);
    }

    // Same ownership check as view() above, proxied so the attachment isn't a
    // directly-fetchable static file.
    public function download($id)
    {
        if (staff_cant('view', 'hr_loans') && staff_cant('view_own', 'hr_loans')) access_denied('hr_loans');
        $loan = $this->Loans_model->get($id);
        if (!$loan) show_404();
        if (!staff_can('view', 'hr_loans') && staff_can('view_own', 'hr_loans')) {
            if ((int) $loan->employee_id !== hr_get_own_employee_id()) access_denied('hr_loans');
        }
        if (empty($loan->attachment)) show_404();
        $this->load->helper('download');
        force_download(FCPATH . 'uploads/hr_module/loans/' . basename($loan->attachment), null);
    }

    public function approve($id)
    {
        if (staff_cant('edit', 'hr_loans')) access_denied('hr_loans');
        $date   = to_sql_date($this->input->post('disbursement_date')) ?: date('Y-m-d');
        $result = $this->Loans_model->approve($id, $date);
        if ($result['success']) {
            if ($this->Hr_module_model->notifications_enabled('notify_loan_approve')) {
                $this->_send_loan_status_email($id, 'approved');
            }
            set_alert('success', $result['message']);
        } else {
            set_alert('danger', $result['message']);
        }
        redirect(admin_url('hr_module/loans/view/' . $id));
    }

    public function reject($id)
    {
        if (staff_cant('edit', 'hr_loans')) access_denied('hr_loans');
        $reason = $this->input->post('rejection_reason', true);
        $result = $this->Loans_model->reject($id, $reason);
        if ($result['success']) {
            if ($this->Hr_module_model->notifications_enabled('notify_loan_approve')) {
                $this->_send_loan_status_email($id, 'rejected', $reason);
            }
            set_alert('success', $result['message']);
        } else {
            set_alert('danger', $result['message']);
        }
        redirect(admin_url('hr_module/loans/view/' . $id));
    }

    // Emails the requesting employee at their own registered address once their
    // loan request has been approved/rejected. No-op if no email is on file.
    private function _send_loan_status_email($id, $status, $reason = null)
    {
        $loan = $this->Loans_model->get($id);
        if (!$loan || empty($loan->employee_email)) {
            return;
        }

        $placeholders = [
            '{employee_name}'       => $loan->first_name . ' ' . $loan->last_name,
            '{department}'          => $loan->department_name ?: '-',
            '{designation}'         => $loan->designation_name ?: '-',
            '{amount}'              => number_format($loan->amount, 2),
            '{monthly_installment}' => number_format($loan->monthly_installment, 2),
            '{repayment_months}'    => $loan->repayment_months,
        ];
        if ($status === 'approved') {
            $placeholders['{disbursement_date}'] = $loan->disbursement_date ? _d($loan->disbursement_date) : '-';
        }
        if ($status === 'rejected') {
            $placeholders['{reason}'] = $reason ?: '-';
        }

        $template_key = $status === 'approved' ? 'loan_approved' : 'loan_rejected';
        $tpl  = $this->Email_templates_model->render($template_key, $placeholders);
        $link = admin_url('hr_module/loans/view/' . $id);
        $this->Hr_module_model->send_employee_email($loan->employee_email, $tpl->subject, $tpl->body, $link);
        $this->Hr_module_model->notify_staff($loan->employee_staff_id, 'not_hr_loan_status', 'hr_module/loans/view/' . $id, [$status]);
    }

    public function add_repayment($id)
    {
        if (staff_cant('edit', 'hr_loans')) access_denied('hr_loans');
        $amount = (float) $this->input->post('amount');
        $date   = to_sql_date($this->input->post('repayment_date'));
        $notes  = $this->input->post('notes', true);
        $result = $this->Loans_model->add_manual_repayment($id, $amount, $date, $notes);
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/loans/view/' . $id));
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_loans')) access_denied('hr_loans');
        $result = $this->Loans_model->delete($id);
        if ($result['success']) set_alert('success', _l('hr_deleted_successfully'));
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/loans'));
    }

    // ── Deduction Requests ────────────────────────────────────────────────────

    public function deduction_requests()
    {
        if (staff_cant('view', 'hr_loans')) access_denied('hr_loans');
        $filters = [];
        foreach (['status','pay_month','pay_year','employee_id'] as $k) {
            $v = $this->input->get($k);
            if ($v !== null && $v !== '') $filters[$k] = $v;
        }
        $data['title']    = 'Loan Deduction Requests';
        $data['requests'] = $this->Loans_model->get_deduction_requests($filters);
        $data['filters']  = $filters;
        $this->load->view('hr_module/loans/deduction_requests', $data);
    }

    public function request_deduction($loan_id)
    {
        if (staff_cant('edit', 'hr_loans') && staff_cant('create', 'hr_loans')) {
            access_denied('hr_loans');
        }
        // Self-service employees (no global view/edit) may only request deductions on their own loan
        if (!staff_can('edit', 'hr_loans') && !staff_can('view', 'hr_loans')) {
            $loan = $this->Loans_model->get($loan_id);
            if (!$loan || (int) $loan->employee_id !== hr_get_own_employee_id()) {
                access_denied('hr_loans');
            }
        }
        if ($this->input->post()) {
            $is_skip   = (bool) $this->input->post('is_skip');
            $pay_month = (int)  $this->input->post('pay_month');
            $pay_year  = (int)  $this->input->post('pay_year');
            $amount    = (float) $this->input->post('amount');
            $notes     = $this->input->post('notes', true);
            $result = $this->Loans_model->submit_deduction_request(
                $loan_id, $pay_month, $pay_year, $amount, $notes, $is_skip,
                $this->input->post('carry_option')
            );
            if ($result['success']) {
                if ($this->Hr_module_model->notifications_enabled('notify_loan_deduction')) {
                    $this->_notify_deduction_request_submitted($loan_id, $pay_month, $pay_year, $amount, $is_skip, $notes);
                }
                set_alert('success', $result['message']);
            } else {
                set_alert('danger', $result['message']);
            }
        }
        redirect(admin_url('hr_module/loans/view/' . $loan_id));
    }

    // Notifies the configured HR inbox (hr_notification_email) whenever an
    // employee submits a loan deduction request, with a link back to the loan.
    private function _notify_deduction_request_submitted($loan_id, $pay_month, $pay_year, $amount, $is_skip, $notes)
    {
        $loan = $this->Loans_model->get($loan_id);
        $month_name = date('F', mktime(0, 0, 0, $pay_month, 1));
        $placeholders = [
            '{employee_name}'       => $loan ? $loan->first_name . ' ' . $loan->last_name . ' (' . $loan->employee_code . ')' : 'Unknown',
            '{department}'          => $loan && $loan->department_name ? $loan->department_name : '-',
            '{designation}'         => $loan && $loan->designation_name ? $loan->designation_name : '-',
            '{loan_amount}'         => $loan ? number_format($loan->amount, 2) : '-',
            '{outstanding}'         => $loan ? number_format($loan->outstanding, 2) : '-',
            '{pay_period}'          => $month_name . ' ' . $pay_year,
            '{amount}'              => number_format($amount, 2),
            '{type}'                => $is_skip ? 'Skip this installment' : 'Adjusted deduction amount',
            '{notes}'               => $notes ?: '-',
        ];
        $tpl  = $this->Email_templates_model->render('loan_deduction_request', $placeholders);
        $link = admin_url('hr_module/loans/view/' . $loan_id);
        $this->Hr_module_model->send_notification_email($tpl->subject, $tpl->body, $link);
        $this->Hr_module_model->notify_by_permission(
            'edit', 'hr_loans',
            'not_hr_deduction_applied',
            'hr_module/loans/view/' . $loan_id,
            [$loan ? $loan->first_name . ' ' . $loan->last_name : 'Unknown']
        );
    }

    public function approve_deduction($id)
    {
        if (staff_cant('edit', 'hr_loans')) access_denied('hr_loans');
        $req    = $this->Loans_model->get_deduction_request($id);
        $result = $this->Loans_model->approve_deduction($id);
        if ($result['success'] && $this->Hr_module_model->notifications_enabled('notify_loan_deduction')) {
            $this->_send_deduction_status_email($req, 'approved');
        }
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        $back = $this->input->post('back') ?: ($req ? admin_url('hr_module/loans/view/' . $req->loan_id) : admin_url('hr_module/loans/deduction_requests'));
        redirect($back);
    }

    public function reject_deduction($id)
    {
        if (staff_cant('edit', 'hr_loans')) access_denied('hr_loans');
        $req    = $this->Loans_model->get_deduction_request($id);
        $result = $this->Loans_model->reject_deduction($id);
        if ($result['success'] && $this->Hr_module_model->notifications_enabled('notify_loan_deduction')) {
            $this->_send_deduction_status_email($req, 'rejected');
        }
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        $back = $this->input->post('back') ?: ($req ? admin_url('hr_module/loans/view/' . $req->loan_id) : admin_url('hr_module/loans/deduction_requests'));
        redirect($back);
    }

    // Emails the requesting employee at their own registered address once their
    // loan deduction request (a skip/adjustment for a specific pay period) has
    // been approved/rejected. No-op if the request wasn't found or has no email.
    private function _send_deduction_status_email($req, $status)
    {
        if (!$req || empty($req->employee_email)) {
            return;
        }

        $month_name = date('F', mktime(0, 0, 0, (int) $req->pay_month, 1));
        $template_key = $status === 'approved' ? 'loan_deduction_approved' : 'loan_deduction_rejected';
        $placeholders = [
            '{employee_name}' => $req->first_name . ' ' . $req->last_name,
            '{department}'    => $req->department_name ?: '-',
            '{designation}'   => $req->designation_name ?: '-',
            '{pay_period}'    => $month_name . ' ' . $req->pay_year,
            '{amount}'        => number_format($req->amount, 2),
            '{type}'          => $req->is_skip ? 'Skip this installment' : 'Adjusted deduction amount',
            '{notes}'         => $req->notes ?: '-',
        ];
        $tpl  = $this->Email_templates_model->render($template_key, $placeholders);
        $link = admin_url('hr_module/loans/view/' . $req->loan_id);

        $this->Hr_module_model->send_employee_email($req->employee_email, $tpl->subject, $tpl->body, $link);
        $this->Hr_module_model->notify_staff($req->employee_staff_id, 'not_hr_deduction_status', 'hr_module/loans/view/' . $req->loan_id, [$status]);
    }

    public function delete_deduction_request($id)
    {
        $req = $this->Loans_model->get_deduction_request($id);
        if (!$req) show_404();

        // Same access model as submitting one: HR (edit) can delete any; self-service
        // employees (no global view/edit) may only delete their own loan's request.
        if (staff_cant('edit', 'hr_loans') && staff_cant('create', 'hr_loans')) {
            access_denied('hr_loans');
        }
        if (!staff_can('edit', 'hr_loans') && !staff_can('view', 'hr_loans')) {
            if ((int) $req->employee_id !== hr_get_own_employee_id()) {
                access_denied('hr_loans');
            }
        }

        $result = $this->Loans_model->delete_deduction_request($id);
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/loans/view/' . $req->loan_id));
    }
}
