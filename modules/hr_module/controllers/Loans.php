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
    }

    public function index()
    {
        if (staff_cant('view', 'hr_loans') && staff_cant('view_own', 'hr_loans')) access_denied('hr_loans');
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'loans/table'));
            return;
        }
        $data['title']       = _l('hr_loan_list');
        $data['departments'] = $this->Departments_model->get_active();
        $data['employees']   = $this->Hr_module_model->get_active_employees_dropdown();
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
            $posted_emp_id = (int) $this->input->post('employee_id');
            $data = [
                'employee_id'         => $own_only ? $own_emp_id : $posted_emp_id,
                'amount'              => (float) $this->input->post('amount'),
                'reason'              => $this->input->post('reason', true),
                'repayment_months'    => (int) $this->input->post('repayment_months'),
                'monthly_installment' => (float) $this->input->post('monthly_installment'),
                'notes'               => $this->input->post('notes', true),
            ];

            // Handle attachment
            $upload_path = FCPATH . 'uploads/hr_module/loans/';
            if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);
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
        if ($own_only) {
            $this->load->model('hr_module/Employees_model');
            $emp = $this->Employees_model->get($own_emp_id);
            $data['employees'] = $own_emp_id && $emp
                ? [$own_emp_id => $emp->first_name . ' ' . $emp->last_name]
                : [];
        } else {
            $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        }
        $this->load->view('hr_module/loans/apply', $data);
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

    public function approve($id)
    {
        if (staff_cant('edit', 'hr_loans')) access_denied('hr_loans');
        $date   = $this->input->post('disbursement_date') ?: date('Y-m-d');
        $result = $this->Loans_model->approve($id, $date);
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/loans/view/' . $id));
    }

    public function reject($id)
    {
        if (staff_cant('edit', 'hr_loans')) access_denied('hr_loans');
        $reason = $this->input->post('rejection_reason', true);
        $result = $this->Loans_model->reject($id, $reason);
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        redirect(admin_url('hr_module/loans/view/' . $id));
    }

    public function add_repayment($id)
    {
        if (staff_cant('edit', 'hr_loans')) access_denied('hr_loans');
        $amount = (float) $this->input->post('amount');
        $date   = $this->input->post('repayment_date');
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
            $is_skip = (bool) $this->input->post('is_skip');
            $result = $this->Loans_model->submit_deduction_request(
                $loan_id,
                (int)   $this->input->post('pay_month'),
                (int)   $this->input->post('pay_year'),
                (float) $this->input->post('amount'),
                $this->input->post('notes', true),
                $is_skip,
                $this->input->post('carry_option')
            );
            if ($result['success']) set_alert('success', $result['message']);
            else                    set_alert('danger',  $result['message']);
        }
        redirect(admin_url('hr_module/loans/view/' . $loan_id));
    }

    public function approve_deduction($id)
    {
        if (staff_cant('edit', 'hr_loans')) access_denied('hr_loans');
        $req    = $this->Loans_model->get_deduction_request($id);
        $result = $this->Loans_model->approve_deduction($id);
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
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        if ($result['success']) set_alert('success', $result['message']);
        else                    set_alert('danger',  $result['message']);
        $back = $this->input->post('back') ?: ($req ? admin_url('hr_module/loans/view/' . $req->loan_id) : admin_url('hr_module/loans/deduction_requests'));
        redirect($back);
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
