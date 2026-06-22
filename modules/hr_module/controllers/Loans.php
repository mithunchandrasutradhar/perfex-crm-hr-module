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
        if (staff_cant('view', 'hr_loans')) access_denied('hr_loans');
        if ($this->input->is_ajax_request() && !$this->input->post()) {
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

        if ($this->input->post()) {
            $data = [
                'employee_id'      => (int) $this->input->post('employee_id'),
                'amount'           => (float) $this->input->post('amount'),
                'reason'           => $this->input->post('reason', true),
                'repayment_months' => (int) $this->input->post('repayment_months'),
                'notes'            => $this->input->post('notes', true),
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

        $data['title']     = _l('hr_loan_add');
        $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/loans/apply', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_loans')) access_denied('hr_loans');
        $loan = $this->Loans_model->get($id);
        if (!$loan) show_404();
        $data['title']      = _l('hr_loan_view');
        $data['loan']       = $loan;
        $data['repayments'] = $this->Loans_model->get_repayments($id);
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
}
