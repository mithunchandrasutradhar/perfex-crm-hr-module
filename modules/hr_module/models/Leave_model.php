<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leave_model extends App_Model
{
    private $tbl_requests;
    private $tbl_types;
    private $tbl_balances;

    public function __construct()
    {
        parent::__construct();
        $this->tbl_requests = db_prefix() . 'hr_leave_requests';
        $this->tbl_types    = db_prefix() . 'hr_leave_types';
        $this->tbl_balances = db_prefix() . 'hr_leave_balances';
    }

    // ── Leave Types ──────────────────────────────────────────────────────

    public function get_type($id = null)
    {
        if ($id) {
            $this->db->where('id', $id);
            return $this->db->get($this->tbl_types)->row();
        }
        $this->db->order_by('name', 'ASC');
        return $this->db->get($this->tbl_types)->result();
    }

    public function get_active_types()
    {
        $this->db->where('status', 1)->order_by('name', 'ASC');
        return $this->db->get($this->tbl_types)->result();
    }

    public function add_type($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->tbl_types, $data);
        return $this->db->insert_id();
    }

    public function update_type($data, $id)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update($this->tbl_types, $data);
        return true;
    }

    public function delete_type($id)
    {
        $this->db->where('leave_type_id', $id);
        if ($this->db->count_all_results($this->tbl_requests) > 0) {
            return ['success' => false, 'message' => 'Leave type has existing requests and cannot be deleted.'];
        }
        $this->db->where('id', $id)->delete($this->tbl_types);
        return ['success' => true];
    }

    // ── Leave Requests ───────────────────────────────────────────────────

    public function get_request($id = null, $filters = [])
    {
        $this->db->select('r.*, lt.name as leave_type_name,
            CONCAT(e.first_name," ",e.last_name) as employee_name, e.employee_code,
            CONCAT(sa.firstname," ",sa.lastname) as approved_by_name', false)
            ->from($this->tbl_requests . ' r')
            ->join(db_prefix() . 'hr_leave_types lt', 'lt.id = r.leave_type_id', 'left')
            ->join(db_prefix() . 'hr_employees e', 'e.id = r.employee_id', 'left')
            ->join(db_prefix() . 'staff sa', 'sa.staffid = r.approved_by', 'left');

        if ($id) {
            $this->db->where('r.id', $id);
            return $this->db->get()->row();
        }
        if (!empty($filters['employee_id']))  $this->db->where('r.employee_id', $filters['employee_id']);
        if (!empty($filters['status']))       $this->db->where('r.status', $filters['status']);
        if (!empty($filters['leave_type_id'])) $this->db->where('r.leave_type_id', $filters['leave_type_id']);
        if (!empty($filters['year']))         $this->db->where('YEAR(r.from_date)', $filters['year']);

        $this->db->order_by('r.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function apply($data)
    {
        // Check for overlapping approved/pending leaves
        if ($this->_has_overlap($data['employee_id'], $data['from_date'], $data['to_date'])) {
            return ['success' => false, 'message' => _l('hr_val_overlapping_leave')];
        }

        // Check balance
        $type    = $this->get_type($data['leave_type_id']);
        $balance = $this->get_balance($data['employee_id'], $data['leave_type_id'], date('Y', strtotime($data['from_date'])));
        $remaining = ($balance ? ($balance->allocated_days + $balance->carry_forward_days - $balance->used_days) : 0);

        if ($type && $type->days_per_year > 0 && $data['total_days'] > $remaining) {
            return ['success' => false, 'message' => _l('hr_val_insufficient_leave') . ' (Remaining: ' . $remaining . ' days)'];
        }

        $data['status']     = 'pending';
        $data['created_by'] = get_staff_user_id();
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->tbl_requests, $data);
        $id = $this->db->insert_id();
        if ($id) {
            hooks()->do_action('hr_leave_applied', $this->get_request($id));
        }
        return ['success' => true, 'id' => $id];
    }

    public function approve($id, $notes = '')
    {
        $request = $this->get_request($id);
        if (!$request || $request->status !== 'pending') {
            return ['success' => false, 'message' => 'Invalid request'];
        }
        $this->db->where('id', $id)->update($this->tbl_requests, [
            'status'      => 'approved',
            'approved_by' => get_staff_user_id(),
            'approved_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $notes,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        // Deduct from balance
        $year = date('Y', strtotime($request->from_date));
        $this->_deduct_balance($request->employee_id, $request->leave_type_id, $year, $request->total_days);
        hooks()->do_action('hr_leave_approved', $request);
        return ['success' => true];
    }

    public function reject($id, $reason = '')
    {
        $request = $this->get_request($id);
        if (!$request || $request->status !== 'pending') {
            return ['success' => false, 'message' => 'Invalid request'];
        }
        $this->db->where('id', $id)->update($this->tbl_requests, [
            'status'           => 'rejected',
            'approved_by'      => get_staff_user_id(),
            'approved_at'      => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
        hooks()->do_action('hr_leave_rejected', $request);
        return ['success' => true];
    }

    public function cancel($id)
    {
        $request = $this->get_request($id);
        if (!$request) return ['success' => false, 'message' => 'Not found'];

        $was_approved = $request->status === 'approved';
        $this->db->where('id', $id)->update($this->tbl_requests, [
            'status'     => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        // Restore balance if was approved
        if ($was_approved) {
            $year = date('Y', strtotime($request->from_date));
            $this->_restore_balance($request->employee_id, $request->leave_type_id, $year, $request->total_days);
        }
        return ['success' => true];
    }

    public function delete_request($id)
    {
        $request = $this->get_request($id);
        if (!$request) return false;
        $this->db->where('id', $id)->delete($this->tbl_requests);
        return $this->db->affected_rows() > 0;
    }

    public function calculate_days($from, $to, $is_half_day = false)
    {
        if ($is_half_day) return 0.5;
        $CI = &get_instance();
        if (!isset($CI->Holidays_model)) {
            $CI->load->model('hr_module/Holidays_model');
        }
        $days = $CI->Holidays_model->count_working_days($from, $to);
        return max(0, (float) $days);
    }

    // ── Leave Balances ───────────────────────────────────────────────────

    public function get_balance($employee_id, $leave_type_id, $year = null)
    {
        if (!$year) $year = date('Y');
        $this->db->where('employee_id', $employee_id)
            ->where('leave_type_id', $leave_type_id)
            ->where('year', $year);
        return $this->db->get($this->tbl_balances)->row();
    }

    public function get_employee_balances($employee_id, $year = null)
    {
        if (!$year) $year = date('Y');
        $this->db->select('b.*, lt.name as leave_type_name, lt.days_per_year, lt.carry_forward')
            ->from($this->tbl_balances . ' b')
            ->join($this->tbl_types . ' lt', 'lt.id = b.leave_type_id', 'left')
            ->where('b.employee_id', $employee_id)
            ->where('b.year', $year)
            ->order_by('lt.name', 'ASC');
        return $this->db->get()->result();
    }

    public function get_all_balances($year = null, $dept_id = null)
    {
        if (!$year) $year = date('Y');
        $this->db->select('b.*, lt.name as leave_type_name,
            CONCAT(e.first_name," ",e.last_name) as employee_name, e.employee_code,
            d.name as department_name', false)
            ->from($this->tbl_balances . ' b')
            ->join($this->tbl_types . ' lt', 'lt.id = b.leave_type_id', 'left')
            ->join(db_prefix() . 'hr_employees e', 'e.id = b.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->where('b.year', $year);
        if ($dept_id) $this->db->where('e.department_id', $dept_id);
        $this->db->order_by('employee_name, lt.name', 'ASC');
        return $this->db->get()->result();
    }

    public function allocate_balances($year = null)
    {
        if (!$year) $year = date('Y');
        $employees = $this->db->where('status', 1)->get(db_prefix() . 'hr_employees')->result();
        $types     = $this->get_active_types();
        $now       = date('Y-m-d H:i:s');
        $count     = 0;
        foreach ($employees as $emp) {
            foreach ($types as $type) {
                $existing = $this->get_balance($emp->id, $type->id, $year);
                if (!$existing) {
                    // Carry forward from previous year
                    $carry = 0;
                    if ($type->carry_forward) {
                        $prev = $this->get_balance($emp->id, $type->id, $year - 1);
                        if ($prev) {
                            $leftover = $prev->allocated_days + $prev->carry_forward_days - $prev->used_days;
                            $carry    = max(0, min($leftover, $type->max_carry_forward_days));
                        }
                    }
                    $this->db->insert($this->tbl_balances, [
                        'employee_id'       => $emp->id,
                        'leave_type_id'     => $type->id,
                        'year'              => $year,
                        'allocated_days'    => $type->days_per_year,
                        'used_days'         => 0,
                        'carry_forward_days'=> $carry,
                        'created_at'        => $now,
                    ]);
                    $count++;
                }
            }
        }
        return $count;
    }

    // ── Private helpers ──────────────────────────────────────────────────

    private function _has_overlap($employee_id, $from, $to)
    {
        $this->db->where('employee_id', $employee_id)
            ->where_in('status', ['pending', 'approved'])
            ->where('from_date <=', $to)
            ->where('to_date >=', $from);
        return $this->db->count_all_results($this->tbl_requests) > 0;
    }

    private function _deduct_balance($emp_id, $type_id, $year, $days)
    {
        $bal = $this->get_balance($emp_id, $type_id, $year);
        if ($bal) {
            $this->db->where('id', $bal->id)
                ->update($this->tbl_balances, [
                    'used_days'  => $bal->used_days + $days,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    private function _restore_balance($emp_id, $type_id, $year, $days)
    {
        $bal = $this->get_balance($emp_id, $type_id, $year);
        if ($bal) {
            $this->db->where('id', $bal->id)
                ->update($this->tbl_balances, [
                    'used_days'  => max(0, $bal->used_days - $days),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }
}
