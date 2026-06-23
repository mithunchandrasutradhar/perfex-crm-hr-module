<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Overtime_model extends App_Model
{
    private $table = 'hr_overtime';

    public function get($id)
    {
        return $this->db
            ->select('o.*, e.first_name, e.last_name, e.employee_code, e.basic_salary,
                      d.name as department_name, ds.name as designation_name,
                      CONCAT(s.firstname," ",s.lastname) as approved_by_name', false)
            ->from(db_prefix() . $this->table . ' o')
            ->join(db_prefix() . 'hr_employees e',    'e.id = o.employee_id',      'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds','ds.id = e.designation_id',  'left')
            ->join(db_prefix() . 'staff s',           's.staffid = o.approved_by', 'left')
            ->where('o.id', $id)
            ->get()->row();
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('o.id, o.overtime_date, o.hours, o.rate_multiplier, o.total_amount,
                           o.status, o.created_at,
                           e.first_name, e.last_name, e.employee_code,
                           d.name as department_name')
            ->from(db_prefix() . $this->table . ' o')
            ->join(db_prefix() . 'hr_employees e', 'e.id = o.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left');

        if (!empty($filters['employee_id']))   $this->db->where('o.employee_id', $filters['employee_id']);
        if (!empty($filters['department_id'])) $this->db->where('e.department_id', $filters['department_id']);
        if (!empty($filters['status']))        $this->db->where('o.status', $filters['status']);
        if (!empty($filters['from_date']))     $this->db->where('o.overtime_date >=', $filters['from_date']);
        if (!empty($filters['to_date']))       $this->db->where('o.overtime_date <=', $filters['to_date']);

        return $this->db->order_by('o.overtime_date DESC')->get()->result();
    }

    public function request($data)
    {
        $amount = $this->_calc_amount($data['employee_id'], $data['hours'], $data['rate_multiplier']);
        $record = [
            'employee_id'      => (int) $data['employee_id'],
            'overtime_date'    => $data['overtime_date'],
            'hours'            => (float) $data['hours'],
            'rate_multiplier'  => (float) ($data['rate_multiplier'] ?? 1.5),
            'total_amount'     => $amount,
            'reason'           => $data['reason'] ?? null,
            'status'           => 'pending',
            'created_by'       => get_staff_user_id(),
            'created_at'       => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . $this->table, $record);
        $id = $this->db->insert_id();
        return $id ? ['success' => true, 'id' => $id, 'message' => _l('hr_overtime_applied_msg')]
                   : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function update($data, $id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row || $row->status !== 'pending') {
            return ['success' => false, 'message' => 'Only pending requests can be edited.'];
        }
        $amount = $this->_calc_amount($data['employee_id'], $data['hours'], $data['rate_multiplier']);
        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'employee_id'     => (int) $data['employee_id'],
            'overtime_date'   => $data['overtime_date'],
            'hours'           => (float) $data['hours'],
            'rate_multiplier' => (float) ($data['rate_multiplier'] ?? 1.5),
            'total_amount'    => $amount,
            'reason'          => $data['reason'] ?? null,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => _l('hr_updated_successfully')];
    }

    public function approve($id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row || $row->status !== 'pending') {
            return ['success' => false, 'message' => 'Only pending requests can be approved.'];
        }
        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'status'      => 'approved',
            'approved_by' => get_staff_user_id(),
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => _l('hr_overtime_approved_msg')];
    }

    public function reject($id, $reason = '')
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row || $row->status !== 'pending') {
            return ['success' => false, 'message' => 'Only pending requests can be rejected.'];
        }
        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => _l('hr_overtime_rejected_msg')];
    }

    public function delete($id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if ($row && $row->status === 'approved') {
            return ['success' => false, 'message' => 'Approved overtime cannot be deleted.'];
        }
        $this->db->where('id', $id)->delete(db_prefix() . $this->table);
        return ['success' => true];
    }

    // hourly rate = basic / (26 working days * 8 hrs)
    private function _calc_amount($employee_id, $hours, $multiplier = 1.5)
    {
        $emp = $this->db->select('basic_salary')->where('id', $employee_id)
                ->get(db_prefix() . 'hr_employees')->row();
        if (!$emp || !$emp->basic_salary) return 0;
        $hourly = (float) $emp->basic_salary / (26 * 8);
        return round($hourly * (float) $hours * (float) $multiplier, 2);
    }
}
