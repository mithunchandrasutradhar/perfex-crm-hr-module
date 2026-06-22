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
                      e.email, e.phone, e.joining_date, e.bank_name, e.bank_account_no,
                      d.name as department_name, ds.name as designation_name,
                      CONCAT(s.firstname," ",s.lastname) as approved_by_name')
            ->from(db_prefix() . $this->table . ' p')
            ->join(db_prefix() . 'hr_employees e', 'e.id = p.employee_id', 'left')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds', 'ds.id = e.designation_id', 'left')
            ->join(db_prefix() . 'staff s', 's.staffid = p.approved_by', 'left')
            ->where('p.id', $id)
            ->get()->row();
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('p.id, p.pay_month, p.pay_year, p.basic_salary, p.gross_salary, p.net_salary,
                           p.status, p.payment_date, p.payment_method, p.created_at,
                           e.first_name, e.last_name, e.employee_code,
                           d.name as department_name')
            ->from(db_prefix() . $this->table . ' p')
            ->join(db_prefix() . 'hr_employees e', 'e.id = p.employee_id', 'left')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left');

        if (!empty($filters['employee_id']))   $this->db->where('p.employee_id', $filters['employee_id']);
        if (!empty($filters['department_id'])) $this->db->where('e.department_id', $filters['department_id']);
        if (!empty($filters['pay_month']))     $this->db->where('p.pay_month', $filters['pay_month']);
        if (!empty($filters['pay_year']))      $this->db->where('p.pay_year', $filters['pay_year']);
        if (!empty($filters['status']))        $this->db->where('p.status', $filters['status']);

        return $this->db->order_by('p.pay_year DESC, p.pay_month DESC, e.first_name')->get()->result();
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
        $working_days  = $summary['working_days']  ?? cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $present_days  = $summary['present']        ?? 0;
        $absent_days   = $summary['absent']         ?? 0;

        // Loan deduction
        $loan_deduction = $this->_pending_loan_deduction($employee_id);

        // Approved overtime pay for this month
        $overtime_amount = $this->_approved_overtime_amount($employee_id, $month, $year);

        // Tax (flat rate from settings — 0 if not configured)
        $tax_rate = (float) get_setting('hr_income_tax_rate');
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

        // Deduct loan installment
        if ($loan_deduction > 0) $this->_record_loan_repayment($employee_id, $pid, $loan_deduction);

        return ['success' => true, 'id' => $pid, 'message' => _l('hr_payroll_generated')];
    }

    public function approve($id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row || $row->status !== 'draft') {
            return ['success' => false, 'message' => 'Only draft payroll can be approved.'];
        }
        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'status'      => 'approved',
            'approved_by' => get_staff_user_id(),
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => _l('hr_payroll_approved')];
    }

    public function mark_paid($id, $method, $payment_date)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row || $row->status !== 'approved') {
            return ['success' => false, 'message' => 'Only approved payroll can be marked as paid.'];
        }
        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'status'         => 'paid',
            'payment_method' => $method,
            'payment_date'   => $payment_date,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => _l('hr_payroll_paid')];
    }

    public function delete($id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if ($row && $row->status === 'paid') {
            return ['success' => false, 'message' => 'Paid payroll cannot be deleted.'];
        }
        $this->db->where('payroll_id', $id)->delete(db_prefix() . $this->details_table);
        $this->db->where('id', $id)->delete(db_prefix() . $this->table);
        return ['success' => true];
    }

    // ─── Internal helpers ────────────────────────────────────────────────────

    private function _pending_loan_deduction($employee_id)
    {
        if (!$this->db->table_exists(db_prefix() . 'hr_loans')) return 0;
        $loan = $this->db
            ->select('monthly_installment')
            ->where('employee_id', $employee_id)
            ->where_in('status', ['approved', 'active'])
            ->where('outstanding >', 0)
            ->order_by('id')
            ->limit(1)
            ->get(db_prefix() . 'hr_loans')->row();
        return $loan ? (float) $loan->monthly_installment : 0;
    }

    private function _record_loan_repayment($employee_id, $payroll_id, $amount)
    {
        if (!$this->db->table_exists(db_prefix() . 'hr_loan_repayments')) return;
        $loan = $this->db->select('id, outstanding, total_repaid')
            ->where('employee_id', $employee_id)
            ->where_in('status', ['approved', 'active'])
            ->where('outstanding >', 0)
            ->order_by('id')->limit(1)
            ->get(db_prefix() . 'hr_loans')->row();
        if (!$loan) return;
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
        $status = $new_outstanding <= 0 ? 'closed' : 'active';
        $this->db->where('id', $loan->id)->update(db_prefix() . 'hr_loans', [
            'outstanding'  => $new_outstanding,
            'total_repaid' => $new_repaid,
            'status'       => $status,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
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
}
