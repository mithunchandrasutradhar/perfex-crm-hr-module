<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payroll_model extends App_Model
{
    private $table         = 'hr_payroll';
    private $items_table   = 'hr_payroll_items';
    private $details_table = 'hr_payroll_details';

    // ─── Payroll Items (templates) ───────────────────────────────────────────

    public function get_items($only_active = false)
    {
        if ($only_active) $this->db->where('status', 1);
        return $this->db->order_by('type,name')->get(db_prefix() . $this->items_table)->result();
    }

    public function get_item($id)
    {
        return $this->db->where('id', $id)->get(db_prefix() . $this->items_table)->row();
    }

    public function add_item($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . $this->items_table, $data);
        $id = $this->db->insert_id();
        return $id ? ['success' => true, 'id' => $id, 'message' => _l('hr_payroll_item_added')]
                   : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function update_item($data, $id)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update(db_prefix() . $this->items_table, $data);
        return $this->db->affected_rows() >= 0
            ? ['success' => true, 'message' => _l('hr_payroll_item_updated')]
            : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function delete_item($id)
    {
        // Check if used in any payroll
        $used = $this->db->where('payroll_item_id', $id)->count_all_results(db_prefix() . $this->details_table);
        if ($used) {
            return ['success' => false, 'message' => 'Cannot delete — item is used in existing payroll records.'];
        }
        $this->db->where('id', $id)->delete(db_prefix() . $this->items_table);
        return ['success' => true, 'message' => _l('hr_payroll_item_deleted')];
    }

    // ─── Payroll Records ─────────────────────────────────────────────────────

    public function get($id)
    {
        return $this->db
            ->select('p.*, e.first_name, e.last_name, e.employee_code, e.basic_salary as emp_basic,
                      e.email, e.phone, e.joining_date, e.bank_name, e.bank_account,
                      d.name as department_name, ds.name as designation_name,
                      CONCAT(s.firstname," ",s.lastname) as approved_by_name', false)
            ->from(db_prefix() . $this->table . ' p')
            ->join(db_prefix() . 'hr_employees e', 'e.id = p.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds', 'ds.id = e.designation_id', 'left')
            ->join(db_prefix() . 'staff s', 's.staffid = p.approved_by', 'left')
            ->where('p.id', $id)
            ->get()->row();
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('p.id, p.employee_id, p.pay_month, p.pay_year, p.basic_salary, p.gross_salary, p.loan_deduction, p.net_salary,
                           p.overtime_amount, p.overtime_days,
                           p.status, p.payment_date, p.payment_method, p.created_at,
                           e.first_name, e.last_name, e.employee_code,
                           d.name as department_name')
            ->from(db_prefix() . $this->table . ' p')
            ->join(db_prefix() . 'hr_employees e', 'e.id = p.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left');

        if (!empty($filters['employee_id']))   $this->db->where('p.employee_id', $filters['employee_id']);
        if (!empty($filters['department_id'])) $this->db->where('e.department_id', $filters['department_id']);
        if (!empty($filters['pay_month']))     $this->db->where('p.pay_month', $filters['pay_month']);
        if (!empty($filters['pay_year']))      $this->db->where('p.pay_year', $filters['pay_year']);
        if (!empty($filters['status']))        $this->db->where('p.status', $filters['status']);

        // Draft payroll (not yet paid) surfaces first, so unpaid employees are seen first
        return $this->db
            ->order_by("(p.status = 'draft') DESC", '', false)
            ->order_by('p.pay_year DESC, p.pay_month DESC, e.first_name')
            ->get()->result();
    }

    public function get_details($payroll_id)
    {
        return $this->db->where('payroll_id', $payroll_id)
            ->order_by('item_type, item_name')
            ->get(db_prefix() . $this->details_table)->result();
    }

    public function already_generated($employee_id, $month, $year)
    {
        return $this->db->where(['employee_id' => $employee_id, 'pay_month' => $month, 'pay_year' => $year])
            ->count_all_results(db_prefix() . $this->table) > 0;
    }

    public function generate($employee_id, $month, $year, $overrides = [])
    {
        $this->load->model('hr_module/Employees_model');
        $emp = $this->Employees_model->get($employee_id);
        if (!$emp) return ['success' => false, 'message' => 'Employee not found.'];

        if ($this->already_generated($employee_id, $month, $year)) {
            return ['success' => false, 'message' => 'Payroll already generated for this employee for the selected period.'];
        }

        $basic = isset($overrides['basic_salary']) ? (float) $overrides['basic_salary'] : (float) $emp->basic_salary;
        $bonus = isset($overrides['bonus']) ? (float) $overrides['bonus'] : 0;

        // Get active payroll items
        $items = $this->get_items(true);
        $total_allowances = 0;
        $total_deductions = 0;
        $details = [];

        foreach ($items as $item) {
            $amount = $item->calculation_type === 'percentage'
                ? round($basic * $item->value / 100, 2)
                : (float) $item->value;
            if ($amount <= 0) continue;

            if ($item->type === 'allowance') $total_allowances += $amount;
            else                              $total_deductions += $amount;

            $details[] = [
                'payroll_item_id' => $item->id,
                'item_name'       => $item->name,
                'item_type'       => $item->type,
                'amount'          => $amount,
            ];
        }

        // Attendance-based deduction: absent days → per-day salary loss
        $this->load->model('hr_module/Attendance_model');
        $summary = $this->Attendance_model->get_summary($employee_id, $month, $year);
        $working_days  = $summary['working_days']  ?? (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $present_days  = $summary['present']        ?? 0;
        $absent_days   = $summary['absent']         ?? 0;

        // Loan deduction — every active loan is deducted by default (standard installment,
        // plus anything carried over), unless a request overrides the amount or skips the month.
        // This is a PLANNED amount only, shown on the draft payroll - it isn't actually applied
        // against the loan's outstanding balance until the payroll is marked paid.
        $deduction_requests = $this->_pending_loan_deductions($employee_id, $month, $year);
        $loan_deduction = array_sum(array_column($deduction_requests, 'amount'));

        // Approved overtime pay for this month
        $overtime_amount = $this->_approved_overtime_amount($employee_id, $month, $year);
        $overtime_days   = $this->_approved_overtime_days($employee_id, $month, $year);

        // Tax (flat rate from settings — 0 if not configured)
        $CI = &get_instance();
        $CI->load->model('hr_module/Hr_module_model');
        $tax_rate = (float) $CI->Hr_module_model->get_setting('hr_income_tax_rate', 0);
        $gross = $basic + $total_allowances + $overtime_amount + $bonus;
        $tax   = $tax_rate > 0 ? round($gross * $tax_rate / 100, 2) : 0;

        $net = $gross - $total_deductions - $tax - $loan_deduction;

        $payroll = [
            'employee_id'       => $employee_id,
            'pay_month'         => $month,
            'pay_year'          => $year,
            'basic_salary'      => $basic,
            'total_allowances'  => $total_allowances,
            'total_deductions'  => $total_deductions,
            'overtime_amount'   => $overtime_amount,
            'overtime_days'     => $overtime_days,
            'bonus'             => $bonus,
            'tax'               => $tax,
            'loan_deduction'    => $loan_deduction,
            'gross_salary'      => $gross,
            'net_salary'        => $net,
            'working_days'      => $working_days,
            'present_days'      => $present_days,
            'absent_days'       => $absent_days,
            'status'            => 'draft',
            'generated_by'      => get_staff_user_id(),
            'created_at'        => date('Y-m-d H:i:s'),
        ];
        if (!empty($overrides['notes'])) $payroll['notes'] = $overrides['notes'];

        $this->db->insert(db_prefix() . $this->table, $payroll);
        $pid = $this->db->insert_id();
        if (!$pid) return ['success' => false, 'message' => _l('hr_error_saving')];

        // Insert details
        foreach ($details as &$d) $d['payroll_id'] = $pid;
        if ($details) $this->db->insert_batch(db_prefix() . $this->details_table, $details);

        return ['success' => true, 'id' => $pid, 'message' => _l('hr_payroll_generated')];
    }

    public function mark_paid($id, $method, $payment_date)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row || $row->status !== 'draft') {
            return ['success' => false, 'message' => 'Only draft payroll can be marked as paid.'];
        }

        // The loan deduction is only actually applied to the loan's outstanding balance
        // now, at the moment of payment - not at generation. Recompute fresh in case any
        // deduction/skip request got approved after this payroll was generated.
        $deductions = $this->_pending_loan_deductions($row->employee_id, $row->pay_month, $row->pay_year);
        $loan_deduction = array_sum(array_column($deductions, 'amount'));
        foreach ($deductions as $req) {
            $this->_record_loan_repayment($req['loan_id'], $id, $req['amount'], $req['id'], $req['clears_carry']);
        }

        // Overtime approved any time up until payment (even after this payroll was
        // generated) still counts - recompute fresh, same as the loan deduction above.
        $overtime_amount = $this->_approved_overtime_amount($row->employee_id, $row->pay_month, $row->pay_year);
        $overtime_days   = $this->_approved_overtime_days($row->employee_id, $row->pay_month, $row->pay_year);
        $gross_salary    = (float) $row->basic_salary + (float) $row->total_allowances + $overtime_amount + (float) $row->bonus;

        $CI = &get_instance();
        $CI->load->model('hr_module/Hr_module_model');
        $tax_rate = (float) $CI->Hr_module_model->get_setting('hr_income_tax_rate', 0);
        $tax      = $tax_rate > 0 ? round($gross_salary * $tax_rate / 100, 2) : 0;

        $net_salary = $gross_salary - (float) $row->total_deductions - $tax - $loan_deduction;

        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'status'          => 'paid',
            'payment_method'  => $method,
            'payment_date'    => $payment_date,
            'overtime_amount' => $overtime_amount,
            'overtime_days'   => $overtime_days,
            'gross_salary'    => $gross_salary,
            'tax'             => $tax,
            'loan_deduction'  => $loan_deduction,
            'net_salary'      => $net_salary,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => _l('hr_payroll_paid')];
    }

    public function delete($id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if ($row && $row->status === 'paid') {
            return ['success' => false, 'message' => 'Paid payroll cannot be deleted.'];
        }
        // Deleting a draft payroll must undo any loan repayment it already applied,
        // otherwise the loan stays paid down for a payroll that no longer exists.
        $this->_reapply_payroll_loan_deduction($id, 0);
        $this->db->where('payroll_id', $id)->delete(db_prefix() . $this->details_table);
        $this->db->where('id', $id)->delete(db_prefix() . $this->table);
        return ['success' => true];
    }

    // Recomputes an already-generated (draft) payroll's PLANNED loan deduction/net salary
    // display, using the same rule generate() uses. Called after a loan-level deduction/skip
    // request gets approved, so a draft payroll's shown numbers don't go stale. Nothing is
    // actually applied to the loan's balance here - that only happens at mark_paid().
    public function sync_loan_deduction_for_period($employee_id, $month, $year)
    {
        $payroll = $this->db
            ->where('employee_id', $employee_id)
            ->where('pay_month',   $month)
            ->where('pay_year',    $year)
            ->where('status',      'draft')
            ->get(db_prefix() . $this->table)->row();
        if (!$payroll) return;

        $deductions = $this->_pending_loan_deductions($employee_id, $month, $year);
        $new_total  = array_sum(array_column($deductions, 'amount'));

        $new_net = (float) $payroll->gross_salary - (float) $payroll->total_deductions - (float) $payroll->tax - $new_total;
        $this->db->where('id', $payroll->id)->update(db_prefix() . $this->table, [
            'loan_deduction' => $new_total,
            'net_salary'     => $new_net,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    // Recomputes an already-generated (draft) payroll's overtime amount/day count and the
    // gross/tax/net figures that depend on it, using the same rule generate() uses. Called
    // after an overtime request gets approved, so a draft payroll's shown numbers keep
    // counting overtime approved right up until the payroll is actually paid.
    public function sync_overtime_for_period($employee_id, $month, $year)
    {
        $payroll = $this->db
            ->where('employee_id', $employee_id)
            ->where('pay_month',   $month)
            ->where('pay_year',    $year)
            ->where('status',      'draft')
            ->get(db_prefix() . $this->table)->row();
        if (!$payroll) return;

        $overtime_amount = $this->_approved_overtime_amount($employee_id, $month, $year);
        $overtime_days   = $this->_approved_overtime_days($employee_id, $month, $year);
        $gross_salary    = (float) $payroll->basic_salary + (float) $payroll->total_allowances + $overtime_amount + (float) $payroll->bonus;

        $CI = &get_instance();
        $CI->load->model('hr_module/Hr_module_model');
        $tax_rate = (float) $CI->Hr_module_model->get_setting('hr_income_tax_rate', 0);
        $tax      = $tax_rate > 0 ? round($gross_salary * $tax_rate / 100, 2) : 0;

        $net_salary = $gross_salary - (float) $payroll->total_deductions - $tax - (float) $payroll->loan_deduction;

        $this->db->where('id', $payroll->id)->update(db_prefix() . $this->table, [
            'overtime_amount' => $overtime_amount,
            'overtime_days'   => $overtime_days,
            'gross_salary'    => $gross_salary,
            'tax'             => $tax,
            'net_salary'      => $net_salary,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    // Re-distributes a payroll's total loan deduction across whichever loans it originally
    // deducted from (proportional to their original share), reversing the old amounts first.
    // $new_total = 0 fully undoes the deduction (used when a draft payroll is deleted).
    private function _reapply_payroll_loan_deduction($payroll_id, $new_total)
    {
        $repayments = $this->db->where('payroll_id', $payroll_id)->get(db_prefix() . 'hr_loan_repayments')->result();
        if (!$repayments) return;

        $old_total = array_sum(array_column($repayments, 'amount'));
        $count     = count($repayments);

        foreach ($repayments as $r) {
            $loan = $this->db->where('id', $r->loan_id)->get(db_prefix() . 'hr_loans')->row();
            if (!$loan) continue;

            // Reverse this loan's share of the old deduction first
            $outstanding = (float) $loan->outstanding + (float) $r->amount;
            $repaid      = (float) $loan->total_repaid - (float) $r->amount;

            $share      = $old_total > 0 ? ((float) $r->amount / $old_total) : (1 / $count);
            $new_amount = round(min($new_total * $share, $outstanding), 2);

            $outstanding = max(0, $outstanding - $new_amount);
            $repaid      = $repaid + $new_amount;

            $this->db->where('id', $loan->id)->update(db_prefix() . 'hr_loans', [
                'outstanding'  => $outstanding,
                'total_repaid' => $repaid,
                'status'       => $outstanding <= 0 ? 'closed' : 'active',
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            if ($new_amount > 0) {
                $this->db->where('id', $r->id)->update(db_prefix() . 'hr_loan_repayments', [
                    'amount' => $new_amount,
                    'notes'  => 'Auto-deducted from payroll (adjusted)',
                ]);
            } else {
                $this->db->where('id', $r->id)->delete(db_prefix() . 'hr_loan_repayments');
            }
        }
    }

    // ─── Internal helpers ────────────────────────────────────────────────────

    // Every active/approved loan with a balance is deducted by default at its standard
    // monthly_installment (plus anything carried over from a previously skipped month) -
    // an employee doesn't have to submit a request just for the normal installment to apply.
    // A request for this month, if one exists, either overrides the amount or skips the
    // month entirely (is_skip), depending on what was approved.
    private function _pending_loan_deductions($employee_id, $month, $year)
    {
        $loans = $this->db
            ->where('employee_id', $employee_id)
            ->where_in('status', ['approved', 'active'])
            ->where('outstanding >', 0)
            ->get(db_prefix() . 'hr_loans')->result();
        if (!$loans) return [];

        $requests_by_loan = [];
        if ($this->db->table_exists(db_prefix() . 'hr_loan_deduction_requests')) {
            $rows = $this->db
                ->where('employee_id', $employee_id)
                ->where('pay_month',   $month)
                ->where('pay_year',    $year)
                ->where('status',      'approved')
                ->where('payroll_id IS NULL')
                ->get(db_prefix() . 'hr_loan_deduction_requests')->result();
            foreach ($rows as $r) $requests_by_loan[(int) $r->loan_id] = $r;
        }

        $deductions = [];
        foreach ($loans as $loan) {
            $req = $requests_by_loan[(int) $loan->id] ?? null;

            if ($req && (int) $req->is_skip === 1) {
                continue; // employee explicitly asked to skip this month
            }

            if ($req) {
                $amount = round(min((float) $req->amount, (float) $loan->outstanding), 2);
                $clears_carry = false;
            } else {
                $amount = round(min((float) $loan->monthly_installment + (float) $loan->carry_forward_amount, (float) $loan->outstanding), 2);
                $clears_carry = (float) $loan->carry_forward_amount > 0;
            }

            if ($amount > 0) {
                $deductions[] = [
                    'id'           => $req ? (int) $req->id : null,
                    'loan_id'      => (int) $loan->id,
                    'amount'       => $amount,
                    'clears_carry' => $clears_carry,
                ];
            }
        }
        return $deductions;
    }

    private function _record_loan_repayment($loan_id, $payroll_id, $amount, $request_id = null, $clears_carry = false)
    {
        if (!$this->db->table_exists(db_prefix() . 'hr_loan_repayments')) return;
        $loan = $this->db->select('id, outstanding, total_repaid')
            ->where('id', $loan_id)
            ->get(db_prefix() . 'hr_loans')->row();
        if (!$loan) return;
        $amount          = min($amount, (float) $loan->outstanding);
        $new_outstanding = max(0, (float) $loan->outstanding - $amount);
        $new_repaid      = (float) $loan->total_repaid + $amount;
        $this->db->insert(db_prefix() . 'hr_loan_repayments', [
            'loan_id'        => $loan->id,
            'payroll_id'     => $payroll_id,
            'amount'         => $amount,
            'repayment_date' => date('Y-m-d'),
            'notes'          => 'Auto-deducted from payroll',
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        $loan_update = [
            'outstanding'  => $new_outstanding,
            'total_repaid' => $new_repaid,
            'status'       => $new_outstanding <= 0 ? 'closed' : 'active',
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        if ($clears_carry) $loan_update['carry_forward_amount'] = 0;
        $this->db->where('id', $loan->id)->update(db_prefix() . 'hr_loans', $loan_update);

        if ($request_id) {
            $this->load->model('hr_module/Loans_model');
            $this->Loans_model->mark_deduction_paid($request_id, $payroll_id);
        }
    }

    private function _approved_overtime_amount($employee_id, $month, $year)
    {
        if (!$this->db->table_exists(db_prefix() . 'hr_overtime')) return 0;
        $from = "$year-$month-01";
        $to   = date('Y-m-t', strtotime($from));
        $res  = $this->db->select_sum('total_amount', 'overtime_amount')
            ->where(['employee_id' => $employee_id, 'status' => 'approved'])
            ->where('overtime_date >=', $from)
            ->where('overtime_date <=', $to)
            ->get(db_prefix() . 'hr_overtime')->row();
        return $res ? (float) $res->overtime_amount : 0;
    }

    private function _approved_overtime_days($employee_id, $month, $year)
    {
        if (!$this->db->table_exists(db_prefix() . 'hr_overtime')) return 0;
        $from = "$year-$month-01";
        $to   = date('Y-m-t', strtotime($from));
        return (int) $this->db
            ->where(['employee_id' => $employee_id, 'status' => 'approved'])
            ->where('overtime_date >=', $from)
            ->where('overtime_date <=', $to)
            ->count_all_results(db_prefix() . 'hr_overtime');
    }
}
