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
        if ($id) {
            log_activity('HR Payroll Item Created [ID: ' . $id . ', Name: ' . ($data['name'] ?? '') . ']');
        }
        return $id ? ['success' => true, 'id' => $id, 'message' => _l('hr_payroll_item_added')]
                   : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function update_item($data, $id)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update(db_prefix() . $this->items_table, $data);
        $ok = $this->db->affected_rows() >= 0;
        if ($ok) {
            log_activity('HR Payroll Item Updated [ID: ' . $id . ']');
        }
        return $ok
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
        log_activity('HR Payroll Item Deleted [ID: ' . $id . ']');
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
                           p.overtime_amount, p.overtime_days, p.total_allowances, p.total_deductions, p.bonus,
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
        if (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('CONCAT(e.first_name," ",e.last_name)', $filters['search'])
                ->or_like('e.employee_code', $filters['search'])
                ->or_like('d.name', $filters['search'])
                ->group_end();
        }

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

    // Sum of this payroll's Evening/Night Shift Allowance detail rows (see
    // _shift_allowance_details()) - used to show the amount alongside the shift
    // summary on the payroll list, without recomputing it (which could drift
    // from what was actually applied if settings changed after generation).
    public function get_shift_allowance_total($payroll_id)
    {
        $res = $this->db->select_sum('amount', 'total')
            ->where('payroll_id', $payroll_id)
            ->like('item_name', 'Shift Allowance')
            ->get(db_prefix() . $this->details_table)->row();
        return $res ? (float) $res->total : 0;
    }

    // LIVE overtime days/amount for THIS employee's period, recomputed right now
    // from whatever the Weekend Overtime Rate / Holiday Overtime Rate / Overtime
    // Day Divisor settings currently are - used only for display on the payroll
    // list, so changing those settings is immediately reflected there without
    // any manual data fix. Deliberately does NOT read or write
    // hr_overtime.total_amount or hr_payroll.overtime_amount/gross_salary/
    // net_salary - those stay exactly as they were generated/approved, a
    // historical record of what was actually agreed at the time, untouched by
    // a later settings change.
    public function calculate_live_overtime($employee_id, $month, $year)
    {
        if (!$this->db->table_exists(db_prefix() . 'hr_overtime')) {
            return ['days' => 0, 'amount' => 0];
        }
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $rows = $this->db->select('day_type')
            ->where(['employee_id' => $employee_id, 'status' => 'approved'])
            ->where('overtime_date >=', $from)
            ->where('overtime_date <=', $to)
            ->get(db_prefix() . 'hr_overtime')->result();
        if (!$rows) {
            return ['days' => 0, 'amount' => 0];
        }

        $CI = &get_instance();
        if (!isset($CI->Hr_module_model)) {
            $CI->load->model('hr_module/Hr_module_model');
        }
        $weekend_rate = (float) $CI->Hr_module_model->get_setting('default_overtime_rate', 1.5);
        $holiday_rate = (float) $CI->Hr_module_model->get_setting('overtime_holiday_rate', 2.0);
        $divisor      = (float) $CI->Hr_module_model->get_setting('overtime_day_divisor', 26);
        if ($divisor <= 0) $divisor = 26;

        $gross      = $this->get_projected_gross_salary($employee_id, $month, $year);
        $daily_rate = $gross / $divisor;

        $amount = 0;
        foreach ($rows as $r) {
            $rate = $r->day_type === 'weekend' ? $weekend_rate : $holiday_rate;
            $amount += round($daily_rate * $rate, 2);
        }

        return ['days' => count($rows), 'amount' => $amount];
    }

    // LIVE evening/night shift allowance for THIS employee's period, recomputed
    // right now from whatever the Shift Allowance settings currently are - used
    // for display on the payroll list's Shift column, and (via
    // calculate_live_gross_net() below) folded into the list's live Gross/Net
    // Salary too. Deliberately does NOT read hr_payroll_details' stored
    // allowance rows (get_shift_allowance_total()) - those stay frozen as the
    // payroll record's own historical, actually-generated figures.
    public function calculate_live_shift_allowance($employee_id, $month, $year)
    {
        return $this->_shift_allowance_details($employee_id, $month, $year)['total'];
    }

    // LIVE Gross/Net Salary for the payroll list - re-derives what Gross/Net
    // would be if the overtime and shift-allowance portions used TODAY's
    // Settings values (calculate_live_overtime()/calculate_live_shift_allowance()),
    // while every other stored figure (basic salary, payroll-item allowances/
    // deductions, bonus, loan deduction) stays exactly as generated. Tax is
    // also re-derived from the current tax rate against this live gross, for
    // consistency. This does NOT write anything back to the hr_payroll row -
    // the actual generated/paid record is untouched; this is display-only.
    public function calculate_live_gross_net($payroll_row, $live_overtime_amount, $live_shift_allowance)
    {
        $frozen_shift_total    = $this->get_shift_allowance_total($payroll_row->id);
        $live_total_allowances = (float) $payroll_row->total_allowances - $frozen_shift_total + $live_shift_allowance;

        $gross = (float) $payroll_row->basic_salary + $live_total_allowances + $live_overtime_amount + (float) $payroll_row->bonus;

        $CI = &get_instance();
        if (!isset($CI->Hr_module_model)) {
            $CI->load->model('hr_module/Hr_module_model');
        }
        $tax_rate = (float) $CI->Hr_module_model->get_setting('hr_income_tax_rate', 0);
        $tax      = $tax_rate > 0 ? round($gross * $tax_rate / 100, 2) : 0;

        $net = $gross - (float) $payroll_row->total_deductions - $tax - (float) $payroll_row->loan_deduction;

        return ['gross' => $gross, 'net' => $net, 'total_allowances' => $live_total_allowances, 'tax' => $tax];
    }

    // What this employee's fixed monthly gross salary would be for this period -
    // basic salary + every currently-active payroll item allowance/deduction that
    // applies to them (fixed or % of basic) + evening/night shift allowance for
    // this period. Deliberately excludes overtime and bonus: overtime is computed
    // FROM this figure (Overduty_model uses it as the day-rate base for weekend/
    // holiday overtime), so including overtime here would be circular, and bonus
    // is a manual per-payroll override decided at generation time, not knowable
    // in advance. Uses the same allowance math as generate(), so overtime
    // requested mid-month is based on the same number payroll will show once
    // actually generated for that period.
    public function get_projected_gross_salary($employee_id, $month, $year, $basic_override = null)
    {
        $this->load->model('hr_module/Employees_model');
        $emp = $this->Employees_model->get($employee_id);
        if (!$emp) return 0;

        $basic = $basic_override !== null ? (float) $basic_override : (float) $emp->basic_salary;

        $total_allowances = 0;
        foreach ($this->get_items(true) as $item) {
            if ($item->type !== 'allowance') continue;
            $amount = $item->calculation_type === 'percentage'
                ? round($basic * $item->value / 100, 2)
                : (float) $item->value;
            if ($amount > 0) $total_allowances += $amount;
        }

        $shift_allowance    = $this->_shift_allowance_details($employee_id, $month, $year);
        $total_allowances  += $shift_allowance['total'];

        return $basic + $total_allowances;
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

        // Evening/night shift allowance - a fixed per-day amount (Settings > Shift
        // Types) for each approved shift day the employee worked this month that
        // falls in an evening/night shift type. Folded straight into total_allowances
        // and its own hr_payroll_details rows, same as any other allowance item.
        $shift_allowance = $this->_shift_allowance_details($employee_id, $month, $year);
        $total_allowances += $shift_allowance['total'];
        $details = array_merge($details, $shift_allowance['details']);

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

        log_activity('HR Payroll Generated [ID: ' . $pid . ', Employee ID: ' . $employee_id . ', Period: ' . $month . '/' . $year . ']');
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

        // This is the moment overtime and shift allowance get finalized for good -
        // using whatever the Settings page's Weekend/Holiday Overtime Rate,
        // Overtime Day Divisor, and Shift Allowance amounts are RIGHT NOW, same
        // live calculation the payroll list previews before payment (not the
        // older per-request hr_overtime.total_amount, which stays frozen at
        // whatever it was when THAT individual request was approved - using it
        // here would silently disagree with the live figure just shown on the
        // list). Once paid, none of this is recalculated again.
        $live_overtime   = $this->calculate_live_overtime($row->employee_id, $row->pay_month, $row->pay_year);
        $overtime_amount = $live_overtime['amount'];
        $overtime_days   = $live_overtime['days'];
        $shift_allowance = $this->calculate_live_shift_allowance($row->employee_id, $row->pay_month, $row->pay_year);
        // calculate_live_gross_net() reads loan_deduction off the row it's given -
        // use the just-recomputed figure above, not $row's original (possibly
        // stale) stored value.
        $row->loan_deduction = $loan_deduction;
        $live_totals         = $this->calculate_live_gross_net($row, $overtime_amount, $shift_allowance);

        // Replace the stored shift-allowance detail rows with the finalized ones,
        // so the itemized Earnings breakdown (payroll view/slip) matches what
        // just got locked in - same replacement sync_shift_allowance_for_period()
        // does for a still-draft payroll.
        $this->db->where('payroll_id', $id)->like('item_name', 'Shift Allowance')->delete(db_prefix() . $this->details_table);
        $shift_details = $this->_shift_allowance_details($row->employee_id, $row->pay_month, $row->pay_year)['details'];
        if ($shift_details) {
            foreach ($shift_details as &$d) $d['payroll_id'] = $id;
            $this->db->insert_batch(db_prefix() . $this->details_table, $shift_details);
        }

        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'status'           => 'paid',
            'payment_method'   => $method,
            'payment_date'     => $payment_date,
            'overtime_amount'  => $overtime_amount,
            'overtime_days'    => $overtime_days,
            'total_allowances' => $live_totals['total_allowances'],
            'gross_salary'     => $live_totals['gross'],
            'tax'              => $live_totals['tax'],
            'loan_deduction'   => $loan_deduction,
            'net_salary'       => $live_totals['net'],
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Payroll Marked Paid [ID: ' . $id . ', Employee ID: ' . $row->employee_id . ', Period: ' . $row->pay_month . '/' . $row->pay_year . ']');
        return ['success' => true, 'message' => _l('hr_payroll_paid')];
    }

    // Undoes mark_paid(): reverses whatever loan repayment it actually applied
    // (same reversal delete() already uses for a draft payroll, reused here so
    // the loan's outstanding/total_repaid go back to what they were before this
    // payroll was paid) and sends the record back to 'draft' - the payroll's own
    // overtime/gross/net columns are left as they were paid at, since a draft
    // row's list display recalculates those live anyway (calculate_live_overtime()
    // etc.) and ignores the stored figures.
    public function revert_to_draft($id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row || $row->status !== 'paid') {
            return ['success' => false, 'message' => 'Only a paid payroll can be reverted to draft.'];
        }

        $this->_reapply_payroll_loan_deduction($id, 0);

        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'status'         => 'draft',
            'payment_method' => null,
            'payment_date'   => null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Payroll Reverted To Draft [ID: ' . $id . ', Employee ID: ' . $row->employee_id . ', Period: ' . $row->pay_month . '/' . $row->pay_year . ']');
        return ['success' => true, 'message' => 'Payroll reverted to draft.'];
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
        log_activity('HR Payroll Deleted [ID: ' . $id . ']');
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

    // Recomputes an already-generated (draft) payroll's evening/night shift allowance
    // detail rows and the total_allowances/gross/tax/net figures that depend on it,
    // using the same rule generate() uses. Called after a shift assignment gets
    // approved, so a draft payroll's shown numbers keep counting shift days approved
    // right up until the payroll is actually paid - same pattern as
    // sync_overtime_for_period()/sync_loan_deduction_for_period() above.
    public function sync_shift_allowance_for_period($employee_id, $month, $year)
    {
        $payroll = $this->db
            ->where('employee_id', $employee_id)
            ->where('pay_month',   $month)
            ->where('pay_year',    $year)
            ->where('status',      'draft')
            ->get(db_prefix() . $this->table)->row();
        if (!$payroll) return;

        $old_shift_total = $this->get_shift_allowance_total($payroll->id);
        $new_shift        = $this->_shift_allowance_details($employee_id, $month, $year);

        // Replace the old shift-allowance detail rows with freshly computed ones -
        // template-item detail rows (payroll_item_id IS NOT NULL) are left untouched.
        $this->db->where('payroll_id', $payroll->id)
            ->like('item_name', 'Shift Allowance')
            ->delete(db_prefix() . $this->details_table);
        if ($new_shift['details']) {
            foreach ($new_shift['details'] as &$d) $d['payroll_id'] = $payroll->id;
            $this->db->insert_batch(db_prefix() . $this->details_table, $new_shift['details']);
        }

        $total_allowances = (float) $payroll->total_allowances - $old_shift_total + $new_shift['total'];
        $gross_salary     = (float) $payroll->basic_salary + $total_allowances + (float) $payroll->overtime_amount + (float) $payroll->bonus;

        $CI = &get_instance();
        $CI->load->model('hr_module/Hr_module_model');
        $tax_rate = (float) $CI->Hr_module_model->get_setting('hr_income_tax_rate', 0);
        $tax      = $tax_rate > 0 ? round($gross_salary * $tax_rate / 100, 2) : 0;

        $net_salary = $gross_salary - (float) $payroll->total_deductions - $tax - (float) $payroll->loan_deduction;

        $this->db->where('id', $payroll->id)->update(db_prefix() . $this->table, [
            'total_allowances' => $total_allowances,
            'gross_salary'     => $gross_salary,
            'tax'              => $tax,
            'net_salary'       => $net_salary,
            'updated_at'       => date('Y-m-d H:i:s'),
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
        $from = sprintf('%04d-%02d-01', $year, $month);
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
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        return (int) $this->db
            ->where(['employee_id' => $employee_id, 'status' => 'approved'])
            ->where('overtime_date >=', $from)
            ->where('overtime_date <=', $to)
            ->count_all_results(db_prefix() . 'hr_overtime');
    }

    // Sums the configured per-day Evening/Night shift allowance (Settings > Shift
    // Types) across every approved shift day this employee worked in the pay
    // period, one detail row per matched category. A shift type only counts as
    // "evening"/"night" by its name containing that word (case-insensitive) -
    // there's no dedicated category field on hr_shift_types, so this matches the
    // module's default seeded names ("Evening Shift", "Night Shift") and any
    // custom type an admin names similarly. Shift types matching neither word
    // (e.g. "Morning Shift") get no allowance.
    private function _shift_allowance_details($employee_id, $month, $year)
    {
        $CI = &get_instance();
        if (!isset($CI->Hr_module_model)) {
            $CI->load->model('hr_module/Hr_module_model');
        }
        $evening_rate = (float) $CI->Hr_module_model->get_setting('shift_allowance_evening_amount', 0);
        $night_rate   = (float) $CI->Hr_module_model->get_setting('shift_allowance_night_amount', 0);

        $details = [];
        $total   = 0;
        if ($evening_rate <= 0 && $night_rate <= 0) {
            return ['total' => $total, 'details' => $details];
        }

        if (!isset($CI->Shifts_model)) {
            $CI->load->model('hr_module/Shifts_model');
        }
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        $counts = $CI->Shifts_model->get_employee_shift_day_counts($employee_id, $from, $to);

        $evening_days = 0;
        $night_days   = 0;
        foreach ($counts as $shift_name => $days) {
            if (stripos($shift_name, 'evening') !== false) {
                $evening_days += $days;
            } elseif (stripos($shift_name, 'night') !== false) {
                $night_days += $days;
            }
        }

        if ($evening_days > 0 && $evening_rate > 0) {
            $amount = round($evening_days * $evening_rate, 2);
            $total += $amount;
            $details[] = [
                'payroll_item_id' => null,
                'item_name'       => 'Evening Shift Allowance (' . $evening_days . ' ' . ($evening_days == 1 ? 'day' : 'days') . ')',
                'item_type'       => 'allowance',
                'amount'          => $amount,
            ];
        }
        if ($night_days > 0 && $night_rate > 0) {
            $amount = round($night_days * $night_rate, 2);
            $total += $amount;
            $details[] = [
                'payroll_item_id' => null,
                'item_name'       => 'Night Shift Allowance (' . $night_days . ' ' . ($night_days == 1 ? 'day' : 'days') . ')',
                'item_type'       => 'allowance',
                'amount'          => $amount,
            ];
        }

        return ['total' => $total, 'details' => $details];
    }
}
