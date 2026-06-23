<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Loans_model extends App_Model
{
    private $table         = 'hr_loans';
    private $repay_table   = 'hr_loan_repayments';
    private $deduct_table  = 'hr_loan_deduction_requests';

    public function __construct()
    {
        parent::__construct();
        $this->_ensure_deduction_table();
    }

    private function _ensure_deduction_table()
    {
        if ($this->db->table_exists(db_prefix() . $this->deduct_table)) return;
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . $this->deduct_table . "` (
            `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `loan_id`     INT UNSIGNED NOT NULL,
            `employee_id` INT UNSIGNED NOT NULL,
            `pay_month`   TINYINT UNSIGNED NOT NULL,
            `pay_year`    SMALLINT UNSIGNED NOT NULL,
            `amount`      DECIMAL(15,2) NOT NULL,
            `status`      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            `notes`       TEXT,
            `reviewed_by` INT UNSIGNED DEFAULT NULL,
            `reviewed_at` DATETIME DEFAULT NULL,
            `created_at`  DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_loan_month_year` (`loan_id`, `pay_month`, `pay_year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    public function get($id)
    {
        return $this->db
            ->select('l.*, e.first_name, e.last_name, e.employee_code, e.department_id,
                      d.name as department_name, ds.name as designation_name,
                      CONCAT(s.firstname," ",s.lastname) as approved_by_name', false)
            ->from(db_prefix() . $this->table . ' l')
            ->join(db_prefix() . 'hr_employees e',    'e.id = l.employee_id',      'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds','ds.id = e.designation_id',  'left')
            ->join(db_prefix() . 'staff s',           's.staffid = l.approved_by', 'left')
            ->where('l.id', $id)
            ->get()->row();
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('l.id, l.amount, l.repayment_months, l.monthly_installment,
                           l.total_repaid, l.outstanding, l.status, l.disbursement_date, l.created_at,
                           e.first_name, e.last_name, e.employee_code, d.name as department_name')
            ->from(db_prefix() . $this->table . ' l')
            ->join(db_prefix() . 'hr_employees e', 'e.id = l.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left');

        if (!empty($filters['employee_id']))   $this->db->where('l.employee_id', $filters['employee_id']);
        if (!empty($filters['department_id'])) $this->db->where('e.department_id', $filters['department_id']);
        if (!empty($filters['status']))        $this->db->where('l.status', $filters['status']);

        return $this->db->order_by('l.created_at DESC')->get()->result();
    }

    public function get_repayments($loan_id)
    {
        return $this->db
            ->select('r.*, p.pay_month, p.pay_year')
            ->from(db_prefix() . $this->repay_table . ' r')
            ->join(db_prefix() . 'hr_payroll p', 'p.id = r.payroll_id', 'left')
            ->where('r.loan_id', $loan_id)
            ->order_by('r.repayment_date DESC')
            ->get()->result();
    }

    public function apply($data)
    {
        $amount  = (float) $data['amount'];
        $custom  = isset($data['monthly_installment']) ? (float) $data['monthly_installment'] : 0;

        if ($custom > 0 && $custom <= $amount) {
            $install = round($custom, 2);
            $months  = (int) ceil($amount / $install);
        } else {
            $months  = max(1, (int) $data['repayment_months']);
            $install = round($amount / $months, 2);
        }

        $record = [
            'employee_id'         => (int) $data['employee_id'],
            'amount'              => $amount,
            'reason'              => $data['reason'] ?? null,
            'repayment_months'    => $months,
            'monthly_installment' => $install,
            'total_repaid'        => 0,
            'outstanding'         => $amount,
            'status'              => 'pending',
            'notes'               => $data['notes'] ?? null,
            'created_by'          => get_staff_user_id(),
            'created_at'          => date('Y-m-d H:i:s'),
        ];

        if (!empty($data['attachment'])) $record['attachment'] = $data['attachment'];

        $this->db->insert(db_prefix() . $this->table, $record);
        $id = $this->db->insert_id();
        return $id ? ['success' => true, 'id' => $id, 'message' => _l('hr_loan_applied_msg')]
                   : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function approve($id, $disbursement_date = null)
    {
        $loan = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$loan || $loan->status !== 'pending') {
            return ['success' => false, 'message' => 'Only pending loans can be approved.'];
        }
        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'status'            => 'approved',
            'approved_by'       => get_staff_user_id(),
            'approved_at'       => date('Y-m-d H:i:s'),
            'disbursement_date' => $disbursement_date ?: date('Y-m-d'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => _l('hr_loan_approved_msg')];
    }

    public function reject($id, $reason = '')
    {
        $loan = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$loan || $loan->status !== 'pending') {
            return ['success' => false, 'message' => 'Only pending loans can be rejected.'];
        }
        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => _l('hr_loan_rejected_msg')];
    }

    public function add_manual_repayment($loan_id, $amount, $date, $notes = '')
    {
        $loan = $this->db->where('id', $loan_id)->get(db_prefix() . $this->table)->row();
        if (!$loan || !in_array($loan->status, ['approved', 'active'])) {
            return ['success' => false, 'message' => 'Loan is not in a repayable state.'];
        }
        $amount      = min((float) $amount, (float) $loan->outstanding);
        $new_out     = max(0, (float) $loan->outstanding - $amount);
        $new_repaid  = (float) $loan->total_repaid + $amount;

        $this->db->insert(db_prefix() . $this->repay_table, [
            'loan_id'        => $loan_id,
            'amount'         => $amount,
            'repayment_date' => $date ?: date('Y-m-d'),
            'notes'          => $notes,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $status = $new_out <= 0 ? 'closed' : 'active';
        $this->db->where('id', $loan_id)->update(db_prefix() . $this->table, [
            'outstanding'  => $new_out,
            'total_repaid' => $new_repaid,
            'status'       => $status,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => 'Repayment recorded.'];
    }

    public function delete($id)
    {
        $loan = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if ($loan && in_array($loan->status, ['active', 'closed'])) {
            return ['success' => false, 'message' => 'Active or closed loans cannot be deleted.'];
        }
        $this->db->where('loan_id', $id)->delete(db_prefix() . $this->repay_table);
        $this->db->where('loan_id', $id)->delete(db_prefix() . $this->deduct_table);
        $this->db->where('id', $id)->delete(db_prefix() . $this->table);
        return ['success' => true];
    }

    // ─── Deduction Requests ──────────────────────────────────────────────────

    public function submit_deduction_request($loan_id, $pay_month, $pay_year, $amount, $notes = '')
    {
        $loan = $this->db->where('id', $loan_id)->get(db_prefix() . $this->table)->row();
        if (!$loan || !in_array($loan->status, ['approved', 'active'])) {
            return ['success' => false, 'message' => 'Loan is not active.'];
        }
        $amount = round(min((float) $amount, (float) $loan->outstanding), 2);
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Amount must be greater than zero.'];
        }

        $existing = $this->db->where([
            'loan_id'   => $loan_id,
            'pay_month' => (int) $pay_month,
            'pay_year'  => (int) $pay_year,
        ])->get(db_prefix() . $this->deduct_table)->row();

        if ($existing) {
            if ($existing->status === 'approved') {
                return ['success' => false, 'message' => 'A deduction for this month is already approved.'];
            }
            $this->db->where('id', $existing->id)->update(db_prefix() . $this->deduct_table, [
                'amount'      => $amount,
                'notes'       => $notes ?: null,
                'status'      => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
            return ['success' => true, 'message' => 'Deduction request updated.'];
        }

        $this->db->insert(db_prefix() . $this->deduct_table, [
            'loan_id'     => (int) $loan_id,
            'employee_id' => (int) $loan->employee_id,
            'pay_month'   => (int) $pay_month,
            'pay_year'    => (int) $pay_year,
            'amount'      => $amount,
            'status'      => 'pending',
            'notes'       => $notes ?: null,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id()
            ? ['success' => true, 'message' => 'Deduction request submitted.']
            : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function get_deduction_requests($filters = [])
    {
        $this->db->select('r.*, l.amount as loan_amount, l.monthly_installment, l.outstanding,
                           e.first_name, e.last_name, e.employee_code,
                           CONCAT(s.firstname," ",s.lastname) as reviewed_by_name', false)
            ->from(db_prefix() . $this->deduct_table . ' r')
            ->join(db_prefix() . $this->table . ' l',        'l.id = r.loan_id',      'left')
            ->join(db_prefix() . 'hr_employees e',           'e.id = r.employee_id',  'left')
            ->join(db_prefix() . 'staff s',                  's.staffid = r.reviewed_by', 'left');
        if (!empty($filters['status']))      $this->db->where('r.status',      $filters['status']);
        if (!empty($filters['loan_id']))     $this->db->where('r.loan_id',     $filters['loan_id']);
        if (!empty($filters['employee_id'])) $this->db->where('r.employee_id', $filters['employee_id']);
        if (!empty($filters['pay_month']))   $this->db->where('r.pay_month',   $filters['pay_month']);
        if (!empty($filters['pay_year']))    $this->db->where('r.pay_year',    $filters['pay_year']);
        return $this->db->order_by('r.pay_year DESC, r.pay_month DESC, r.created_at DESC')->get()->result();
    }

    public function approve_deduction($id)
    {
        $req = $this->db->where('id', $id)->get(db_prefix() . $this->deduct_table)->row();
        if (!$req || $req->status !== 'pending') {
            return ['success' => false, 'message' => 'Only pending requests can be approved.'];
        }
        $this->db->where('id', $id)->update(db_prefix() . $this->deduct_table, [
            'status'      => 'approved',
            'reviewed_by' => get_staff_user_id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => 'Deduction request approved.'];
    }

    public function reject_deduction($id)
    {
        $req = $this->db->where('id', $id)->get(db_prefix() . $this->deduct_table)->row();
        if (!$req || $req->status !== 'pending') {
            return ['success' => false, 'message' => 'Only pending requests can be rejected.'];
        }
        $this->db->where('id', $id)->update(db_prefix() . $this->deduct_table, [
            'status'      => 'rejected',
            'reviewed_by' => get_staff_user_id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => 'Deduction request rejected.'];
    }

    public function get_deduction_request($id)
    {
        return $this->db->where('id', $id)->get(db_prefix() . $this->deduct_table)->row();
    }
}
