<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * An overtime "request" can cover several dates in one submission (an employee logging
 * multiple overtime days in a month). All rows created from the same submission share a
 * batch_id and are always mutated together (approve/reject/delete/edit act on the whole
 * batch), so the list can show and act on it as a single request even though each date
 * is still stored as its own row for Payroll/Reports to sum/count individually.
 */
class Overtime_model extends App_Model
{
    private $table = 'hr_overtime';

    public function get($id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row) return null;

        return $this->db
            ->select('MIN(o.id) as id, o.batch_id, o.employee_id, SUM(o.total_amount) as total_amount,
                      MIN(o.reason) as reason, MIN(o.status) as status,
                      MIN(o.approved_by) as approved_by, MIN(o.approved_at) as approved_at,
                      MIN(o.rejection_reason) as rejection_reason, MIN(o.created_at) as created_at,
                      COUNT(*) as day_count, MIN(o.overtime_date) as first_date, MAX(o.overtime_date) as last_date,
                      GROUP_CONCAT(DISTINCT o.day_type) as day_types,
                      e.first_name, e.last_name, e.employee_code, e.basic_salary,
                      d.name as department_name, ds.name as designation_name,
                      CONCAT(s.firstname," ",s.lastname) as approved_by_name', false)
            ->from(db_prefix() . $this->table . ' o')
            ->join(db_prefix() . 'hr_employees e',    'e.id = o.employee_id',      'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds','ds.id = e.designation_id',  'left')
            ->join(db_prefix() . 'staff s',           's.staffid = o.approved_by', 'left')
            ->where('o.batch_id', $row->batch_id)
            ->group_by('o.batch_id')
            ->get()->row();
    }

    // Individual dates within one request/batch, for the view/edit pages.
    public function get_dates($id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row) return [];
        return $this->db->where('batch_id', $row->batch_id)
            ->order_by('overtime_date', 'ASC')
            ->get(db_prefix() . $this->table)->result();
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('MIN(o.id) as id, o.employee_id, SUM(o.total_amount) as total_amount,
                           MIN(o.status) as status, MIN(o.created_at) as created_at,
                           COUNT(*) as day_count, MIN(o.overtime_date) as first_date, MAX(o.overtime_date) as last_date,
                           GROUP_CONCAT(DISTINCT o.day_type) as day_types,
                           e.first_name, e.last_name, e.employee_code,
                           d.name as department_name', false)
            ->from(db_prefix() . $this->table . ' o')
            ->join(db_prefix() . 'hr_employees e', 'e.id = o.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left');

        if (!empty($filters['employee_id']))   $this->db->where('o.employee_id', $filters['employee_id']);
        if (!empty($filters['department_id'])) $this->db->where('e.department_id', $filters['department_id']);
        if (!empty($filters['status']))        $this->db->where('o.status', $filters['status']);
        if (!empty($filters['from_date']))     $this->db->where('o.overtime_date >=', $filters['from_date']);
        if (!empty($filters['to_date']))       $this->db->where('o.overtime_date <=', $filters['to_date']);

        return $this->db->group_by('o.batch_id')->order_by('last_date DESC')->get()->result();
    }

    /**
     * Resolve what kind of eligible day a date is (weekend / government holiday /
     * company holiday), or null if the date doesn't qualify for overtime at all.
     */
    public function resolve_day_type($date)
    {
        $CI = &get_instance();
        if (!isset($CI->Holidays_model)) {
            $CI->load->model('hr_module/Holidays_model');
        }

        $holiday = $CI->Holidays_model->get_holiday_on_date($date);
        if ($holiday) {
            return [
                'day_type'     => $holiday->type === 'company' ? 'company_holiday' : 'government_holiday',
                'holiday_name' => $holiday->name,
            ];
        }

        $weekly_off = $CI->Holidays_model->get_weekly_off_days();
        $dow        = (int) date('w', strtotime($date));
        if (in_array($dow, $weekly_off)) {
            return ['day_type' => 'weekend', 'holiday_name' => null];
        }

        return null;
    }

    public function preview($employee_id, $date)
    {
        $resolved = $this->resolve_day_type($date);
        if (!$resolved) {
            return ['eligible' => false, 'message' => _l('hr_overtime_not_eligible_date')];
        }
        $multiplier = $this->_rate_multiplier($resolved['day_type']);
        $amount     = $this->_calc_amount($employee_id, $multiplier);
        return [
            'eligible'     => true,
            'day_type'     => $resolved['day_type'],
            'holiday_name' => $resolved['holiday_name'],
            'multiplier'   => $multiplier,
            'amount'       => $amount,
        ];
    }

    // Creates one request covering every date given - all or nothing: if any date is
    // ineligible or already requested, nothing is inserted and the specific problem
    // date/reason is returned so the whole batch can be corrected and resubmitted.
    public function request($data)
    {
        $employee_id = (int) $data['employee_id'];
        $dates       = array_values(array_unique($data['dates'] ?? []));
        if (empty($dates)) {
            return ['success' => false, 'message' => _l('hr_overtime_no_dates_selected')];
        }

        $prepared = [];
        foreach ($dates as $date) {
            $resolved = $this->resolve_day_type($date);
            if (!$resolved) {
                return ['success' => false, 'message' => date('d M Y', strtotime($date)) . ': ' . _l('hr_overtime_not_eligible_date')];
            }
            if ($this->_has_existing_request($employee_id, $date)) {
                return ['success' => false, 'message' => date('d M Y', strtotime($date)) . ': ' . _l('hr_overtime_duplicate_date')];
            }
            $multiplier = $this->_rate_multiplier($resolved['day_type']);
            $prepared[] = [
                'overtime_date'   => $date,
                'day_type'        => $resolved['day_type'],
                'holiday_name'    => $resolved['holiday_name'],
                'rate_multiplier' => $multiplier,
                'total_amount'    => $this->_calc_amount($employee_id, $multiplier),
            ];
        }

        $batch_id  = $this->_generate_batch_id();
        $now       = date('Y-m-d H:i:s');
        $createdBy = get_staff_user_id();
        $rows      = [];
        foreach ($prepared as $p) {
            $rows[] = array_merge($p, [
                'employee_id' => $employee_id,
                'batch_id'    => $batch_id,
                'reason'      => $data['reason'] ?? null,
                'status'      => 'pending',
                'created_by'  => $createdBy,
                'created_at'  => $now,
            ]);
        }
        $this->db->insert_batch(db_prefix() . $this->table, $rows);
        $id = $this->db->insert_id();
        return $id ? ['success' => true, 'id' => $id, 'message' => count($rows) === 1 ? _l('hr_overtime_applied_msg') : _l('hr_overtime_batch_submitted', count($rows))]
                   : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    // Replaces a pending request's dates wholesale (same all-or-nothing validation as
    // request()). The batch_id, created_by and created_at are preserved; the id used to
    // look this batch up may no longer exist afterwards, so the new representative id is
    // returned for the caller to redirect to.
    public function update($data, $id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row || $row->status !== 'pending') {
            return ['success' => false, 'message' => 'Only pending requests can be edited.'];
        }

        $employee_id = (int) $data['employee_id'];
        $dates       = array_values(array_unique($data['dates'] ?? []));
        if (empty($dates)) {
            return ['success' => false, 'message' => _l('hr_overtime_no_dates_selected')];
        }

        $prepared = [];
        foreach ($dates as $date) {
            $resolved = $this->resolve_day_type($date);
            if (!$resolved) {
                return ['success' => false, 'message' => date('d M Y', strtotime($date)) . ': ' . _l('hr_overtime_not_eligible_date')];
            }
            if ($this->_has_existing_request($employee_id, $date, $row->batch_id)) {
                return ['success' => false, 'message' => date('d M Y', strtotime($date)) . ': ' . _l('hr_overtime_duplicate_date')];
            }
            $multiplier = $this->_rate_multiplier($resolved['day_type']);
            $prepared[] = [
                'overtime_date'   => $date,
                'day_type'        => $resolved['day_type'],
                'holiday_name'    => $resolved['holiday_name'],
                'rate_multiplier' => $multiplier,
                'total_amount'    => $this->_calc_amount($employee_id, $multiplier),
            ];
        }

        $this->db->where('batch_id', $row->batch_id)->delete(db_prefix() . $this->table);

        $rows = [];
        foreach ($prepared as $p) {
            $rows[] = array_merge($p, [
                'employee_id' => $employee_id,
                'batch_id'    => $row->batch_id,
                'reason'      => $data['reason'] ?? null,
                'status'      => 'pending',
                'created_by'  => $row->created_by,
                'created_at'  => $row->created_at,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }
        $this->db->insert_batch(db_prefix() . $this->table, $rows);
        $new_id = $this->db->insert_id();
        return $new_id ? ['success' => true, 'id' => $new_id, 'message' => _l('hr_updated_successfully')]
                       : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function approve($id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row || $row->status !== 'pending') {
            return ['success' => false, 'message' => 'Only pending requests can be approved.'];
        }
        $this->db->where('batch_id', $row->batch_id)->update(db_prefix() . $this->table, [
            'status'      => 'approved',
            'approved_by' => get_staff_user_id(),
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // If payroll for any month/year this batch's dates fall in was already generated
        // (draft), reflect the just-approved overtime in it immediately rather than
        // leaving it stale until someone notices and regenerates.
        $dates = $this->db->select('overtime_date')->where('batch_id', $row->batch_id)
            ->get(db_prefix() . $this->table)->result();
        $periods = [];
        foreach ($dates as $d) {
            $ts  = strtotime($d->overtime_date);
            $key = date('n-Y', $ts);
            $periods[$key] = [(int) date('n', $ts), (int) date('Y', $ts)];
        }
        $this->load->model('hr_module/Payroll_model');
        foreach ($periods as $period) {
            $this->Payroll_model->sync_overtime_for_period($row->employee_id, $period[0], $period[1]);
        }

        return ['success' => true, 'message' => _l('hr_overtime_approved_msg')];
    }

    public function reject($id, $reason = '')
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row || $row->status !== 'pending') {
            return ['success' => false, 'message' => 'Only pending requests can be rejected.'];
        }
        $this->db->where('batch_id', $row->batch_id)->update(db_prefix() . $this->table, [
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => _l('hr_overtime_rejected_msg')];
    }

    public function delete($id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row) return ['success' => true];
        if ($row->status === 'approved') {
            return ['success' => false, 'message' => 'Approved overtime cannot be deleted.'];
        }
        $this->db->where('batch_id', $row->batch_id)->delete(db_prefix() . $this->table);
        return ['success' => true];
    }

    private function _generate_batch_id()
    {
        return bin2hex(random_bytes(16));
    }

    private function _has_existing_request($employee_id, $date, $exclude_batch_id = null)
    {
        $this->db->where('employee_id', $employee_id)
            ->where('overtime_date', $date)
            ->where_in('status', ['pending', 'approved']);
        if ($exclude_batch_id) {
            $this->db->where('batch_id !=', $exclude_batch_id);
        }
        return (bool) $this->db->get(db_prefix() . $this->table)->row();
    }

    private function _rate_multiplier($day_type)
    {
        $CI = &get_instance();
        if (!isset($CI->Hr_module_model)) {
            $CI->load->model('hr_module/Hr_module_model');
        }
        if ($day_type === 'weekend') {
            return (float) $CI->Hr_module_model->get_setting('default_overtime_rate', 1.5);
        }
        return (float) $CI->Hr_module_model->get_setting('overtime_holiday_rate', 2.0);
    }

    // day rate = basic salary / 26 working days per month
    private function _calc_amount($employee_id, $multiplier)
    {
        $emp = $this->db->select('basic_salary')->where('id', $employee_id)
                ->get(db_prefix() . 'hr_employees')->row();
        if (!$emp || !$emp->basic_salary) return 0;
        $daily_rate = (float) $emp->basic_salary / 26;
        return round($daily_rate * (float) $multiplier, 2);
    }
}
