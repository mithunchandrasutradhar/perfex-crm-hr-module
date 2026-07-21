<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leave_model extends App_Model
{
    private $tbl_requests;
    private $tbl_request_days;
    private $tbl_types;
    private $tbl_balances;

    public function __construct()
    {
        parent::__construct();
        $this->tbl_requests      = db_prefix() . 'hr_leave_requests';
        $this->tbl_request_days  = db_prefix() . 'hr_leave_request_days';
        $this->tbl_types         = db_prefix() . 'hr_leave_types';
        $this->tbl_balances      = db_prefix() . 'hr_leave_balances';
        $this->_ensure_cancellation_columns();
    }

    // Lets an employee request cancellation of an already-approved leave (reviewed
    // by HR, rather than cancelled outright) - adds the columns on first use so
    // this works immediately without requiring the module to be reactivated.
    private function _ensure_cancellation_columns()
    {
        $col = $this->db->query("SHOW COLUMNS FROM `" . $this->tbl_requests . "` LIKE 'cancellation_status'")->num_rows();
        if ($col === 0) {
            $this->db->query("ALTER TABLE `" . $this->tbl_requests . "`
                ADD COLUMN `cancellation_status` VARCHAR(20) DEFAULT NULL AFTER `status`,
                ADD COLUMN `cancellation_reason` TEXT DEFAULT NULL,
                ADD COLUMN `cancellation_requested_at` DATETIME DEFAULT NULL,
                ADD COLUMN `cancellation_reviewed_by` INT(11) DEFAULT NULL,
                ADD COLUMN `cancellation_reviewed_at` DATETIME DEFAULT NULL");
        }
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
            CONCAT(e.first_name," ",e.last_name) as employee_name, e.employee_code, e.email as employee_email,
            d.name as department_name, ds.name as designation_name,
            CONCAT(sa.firstname," ",sa.lastname) as approved_by_name', false)
            ->from($this->tbl_requests . ' r')
            ->join(db_prefix() . 'hr_leave_types lt', 'lt.id = r.leave_type_id', 'left')
            ->join(db_prefix() . 'hr_employees e', 'e.id = r.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds', 'ds.id = e.designation_id', 'left')
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

    // $days is a list of ['date' => 'Y-m-d', 'type' => 'full'|'half_before_lunch'|'half_after_lunch'|'hourly',
    //                      'hour_start' => 'HH:MM'|null, 'hour_end' => 'HH:MM'|null]
    public function apply($data, $days)
    {
        if (empty($days)) {
            return ['success' => false, 'message' => _l('hr_val_no_leave_days')];
        }

        $type = $this->get_type($data['leave_type_id']);
        if (!$type) {
            return ['success' => false, 'message' => _l('hr_error_not_found')];
        }

        $dates = array_column($days, 'date');
        if (count($dates) !== count(array_unique($dates))) {
            return ['success' => false, 'message' => _l('hr_val_duplicate_leave_dates')];
        }

        // Sandwich rule: if leave is requested on both sides of a weekend/holiday
        // (e.g. Thursday + Saturday with Friday off), the day(s) in between are
        // automatically included as leave too.
        $days  = $this->_add_bridge_days($days);
        $dates = array_column($days, 'date');

        // Check for overlapping approved/pending leaves on any of the same specific dates
        if ($this->_has_overlap_days($data['employee_id'], $dates)) {
            return ['success' => false, 'message' => _l('hr_val_overlapping_leave')];
        }

        // Compute each day's value server-side - never trust a client-submitted total
        $prepared_days = [];
        $total_days    = 0;
        foreach ($days as $day) {
            $value = $this->_calculate_day_value($type, $day);
            if ($value === null) {
                return ['success' => false, 'message' => _l('hr_val_invalid_leave_day') . ' (' . $day['date'] . ')'];
            }
            $prepared_days[] = [
                'leave_date' => $day['date'],
                'day_type'   => $day['type'],
                'hour_start' => $day['hour_start'] ?: null,
                'hour_end'   => $day['hour_end'] ?: null,
                'day_value'  => $value,
                'note'       => $day['note'] ?? null,
            ];
            $total_days += $value;
        }

        sort($dates);
        $data['from_date']  = $dates[0];
        $data['to_date']    = end($dates);
        $data['total_days'] = $total_days;
        $data['is_half_day'] = 0;

        // Check balance - create the year's balance row on first use if it doesn't exist yet
        // (e.g. a new employee, or a leave type added after the year's balances were allocated)
        $balance = $this->_get_or_create_balance($data['employee_id'], $data['leave_type_id'], date('Y', strtotime($data['from_date'])));
        $remaining = $balance->allocated_days + $balance->carry_forward_days - $balance->used_days;

        if ($type->days_per_year > 0 && $total_days > $remaining) {
            return ['success' => false, 'message' => _l('hr_val_insufficient_leave') . ' (Remaining: ' . $remaining . ' days)'];
        }

        $data['status']     = 'pending';
        $data['created_by'] = get_staff_user_id();
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->tbl_requests, $data);
        $id = $this->db->insert_id();
        if ($id) {
            $now = date('Y-m-d H:i:s');
            foreach ($prepared_days as &$pd) {
                $pd['leave_request_id'] = $id;
                $pd['employee_id']      = $data['employee_id'];
                $pd['created_at']       = $now;
            }
            unset($pd);
            $this->db->insert_batch($this->tbl_request_days, $prepared_days);
            hooks()->do_action('hr_leave_applied', $this->get_request($id));
        }
        return ['success' => true, 'id' => $id];
    }

    public function get_request_days($leave_request_id)
    {
        $this->db->where('leave_request_id', $leave_request_id)->order_by('leave_date', 'ASC');
        return $this->db->get($this->tbl_request_days)->result();
    }

    // Every approved leave day falling within [$from,$to] (inclusive), with the
    // employee's name - used to show "who's on leave" on the holiday calendar.
    public function get_approved_leave_days_in_range($from, $to)
    {
        return $this->db->select('d.leave_date, d.day_type, d.employee_id,
                CONCAT(e.first_name," ",e.last_name) as employee_name, e.employee_code', false)
            ->from($this->tbl_request_days . ' d')
            ->join($this->tbl_requests . ' r', 'r.id = d.leave_request_id')
            ->join(db_prefix() . 'hr_employees e', 'e.id = d.employee_id', 'left')
            ->where('r.status', 'approved')
            ->where('d.leave_date >=', $from)
            ->where('d.leave_date <=', $to)
            ->order_by('d.leave_date', 'ASC')
            ->get()->result();
    }

    // Returns [leave_request_id => ['full', 'half_before_lunch', ...]] for the given
    // request IDs in one query, so a list page can show each request's day-type
    // composition without an N+1 query per row.
    public function get_day_types_for_requests($leave_request_ids)
    {
        $rows = $this->db->select('leave_request_id, day_type')
            ->where_in('leave_request_id', $leave_request_ids)
            ->order_by('leave_date', 'ASC')
            ->get($this->tbl_request_days)->result();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->leave_request_id][] = $row->day_type;
        }
        return $map;
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

    // $allow_approved is only ever passed true by approve_cancellation() below, once HR
    // has reviewed and approved the request - a direct call (e.g. the employee-facing
    // cancel action) must never cancel an already-approved leave outright; it has to go
    // through request_cancellation() instead.
    public function cancel($id, $reason = '', $allow_approved = false)
    {
        $request = $this->get_request($id);
        if (!$request) return ['success' => false, 'message' => 'Not found'];

        if ($request->status === 'approved' && !$allow_approved) {
            return ['success' => false, 'message' => 'This leave is already approved - please submit a cancellation request for HR to review.'];
        }

        $was_approved = $request->status === 'approved';
        $update = [
            'status'     => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($reason !== '') {
            $update['cancellation_reason'] = $reason;
        }
        $this->db->where('id', $id)->update($this->tbl_requests, $update);
        // Restore balance if was approved
        if ($was_approved) {
            $year = date('Y', strtotime($request->from_date));
            $this->_restore_balance($request->employee_id, $request->leave_type_id, $year, $request->total_days);
        }
        return ['success' => true];
    }

    // Employee requests cancellation of an already-approved leave - doesn't cancel it
    // immediately, since balance was already deducted and colleagues may already have
    // been notified via the approval announcement. HR must review via
    // approve_cancellation()/reject_cancellation() below.
    public function request_cancellation($id, $reason = '')
    {
        $request = $this->get_request($id);
        if (!$request || $request->status !== 'approved') {
            return ['success' => false, 'message' => 'Only an approved leave request can have its cancellation requested.'];
        }
        if ($request->cancellation_status === 'pending') {
            return ['success' => false, 'message' => 'A cancellation request is already pending for this leave.'];
        }
        $this->db->where('id', $id)->update($this->tbl_requests, [
            'cancellation_status'       => 'pending',
            'cancellation_reason'       => $reason ?: null,
            'cancellation_requested_at' => date('Y-m-d H:i:s'),
            'cancellation_reviewed_by'  => null,
            'cancellation_reviewed_at'  => null,
            'updated_at'                => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true];
    }

    // Approves a pending cancellation request - actually cancels the leave (reusing
    // cancel()'s existing balance-restore logic) and records who reviewed it.
    public function approve_cancellation($id)
    {
        $request = $this->get_request($id);
        if (!$request || $request->cancellation_status !== 'pending') {
            return ['success' => false, 'message' => 'No pending cancellation request found.'];
        }
        $this->cancel($id, '', true);
        $this->db->where('id', $id)->update($this->tbl_requests, [
            'cancellation_status'      => 'approved',
            'cancellation_reviewed_by' => get_staff_user_id(),
            'cancellation_reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true];
    }

    // Rejects a pending cancellation request - the leave stays approved as-is.
    public function reject_cancellation($id)
    {
        $request = $this->get_request($id);
        if (!$request || $request->cancellation_status !== 'pending') {
            return ['success' => false, 'message' => 'No pending cancellation request found.'];
        }
        $this->db->where('id', $id)->update($this->tbl_requests, [
            'cancellation_status'      => 'rejected',
            'cancellation_reviewed_by' => get_staff_user_id(),
            'cancellation_reviewed_at' => date('Y-m-d H:i:s'),
            'updated_at'                => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true];
    }

    public function delete_request($id)
    {
        $request = $this->get_request($id);
        if (!$request) return false;
        $this->db->where('leave_request_id', $id)->delete($this->tbl_request_days);
        $this->db->where('id', $id)->delete($this->tbl_requests);
        return $this->db->affected_rows() > 0;
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

    // Checks whether any of the given specific dates already has a pending/approved
    // leave day for this employee - date-by-date, since a request's days no longer
    // have to be contiguous.
    private function _has_overlap_days($employee_id, $dates)
    {
        $this->db->select('d.id')
            ->from($this->tbl_request_days . ' d')
            ->join($this->tbl_requests . ' r', 'r.id = d.leave_request_id')
            ->where('d.employee_id', $employee_id)
            ->where_in('d.leave_date', $dates)
            ->where_in('r.status', ['pending', 'approved']);
        return $this->db->get()->num_rows() > 0;
    }

    // Returns the day's value in days (1, 0.5, or hours/hours_per_day for hourly),
    // or null if the day entry is invalid.
    private function _calculate_day_value($type, $day)
    {
        switch ($day['type']) {
            case 'full':
            case 'bridge':
                return 1.0;

            case 'half_before_lunch':
            case 'half_after_lunch':
                if (!$type->allow_half_day) return null;
                return 0.5;

            case 'hourly':
                if (empty($day['hour_start']) || empty($day['hour_end'])) return null;
                $start = strtotime($day['hour_start']);
                $end   = strtotime($day['hour_end']);
                if ($start === false || $end === false || $end <= $start) return null;
                $hours = ($end - $start) / 3600;
                $hours_per_day = $type->hours_per_day > 0 ? (float) $type->hours_per_day : 8.0;
                return round($hours / $hours_per_day, 2);

            default:
                return null;
        }
    }

    // Applies the leave module's holiday-calendar rules to the requested days:
    //  1. Labels any requested day (full, half, or hourly) that itself falls on a
    //     weekly-off day or holiday - purely informational, doesn't change its value.
    //  2. Sandwich rule: for each pair of consecutive requested FULL-day dates
    //     (half-day and hourly days never trigger this), if every day strictly between
    //     them is a weekly-off day or a holiday, those day(s) are auto-added as leave
    //     too (type 'bridge', full day each) - e.g. leave on Thu + Sat with Fri as
    //     weekly off means Fri counts too, for 3 days total.
    private function _add_bridge_days($days)
    {
        $sorted = $days;
        usort($sorted, function ($a, $b) { return strcmp($a['date'], $b['date']); });

        $CI = &get_instance();
        if (!isset($CI->Holidays_model)) {
            $CI->load->model('hr_module/Holidays_model');
        }
        $weekly_off  = $CI->Holidays_model->get_weekly_off_days();
        $holiday_map = $CI->Holidays_model->get_holiday_names_in_range(
            $sorted[0]['date'], end($sorted)['date']
        );

        foreach ($days as &$day) {
            $note = $this->_holiday_note_for_date($day['date'], $weekly_off, $holiday_map);
            if ($note) $day['note'] = $note;
        }
        unset($day);

        if (count($sorted) < 2) return $days;

        $bridge_days = [];
        for ($i = 0; $i < count($sorted) - 1; $i++) {
            if ($sorted[$i]['type'] !== 'full' || $sorted[$i + 1]['type'] !== 'full') continue;

            $gap_start = strtotime($sorted[$i]['date']) + 86400;
            $gap_end   = strtotime($sorted[$i + 1]['date']) - 86400;
            if ($gap_start > $gap_end) continue; // adjacent dates, no gap between them

            $candidate = [];
            for ($ts = $gap_start; $ts <= $gap_end; $ts += 86400) {
                $date = date('Y-m-d', $ts);
                $note = $this->_holiday_note_for_date($date, $weekly_off, $holiday_map);
                if (!$note) {
                    $candidate = null;
                    break;
                }
                $candidate[] = [
                    'date'       => $date,
                    'type'       => 'bridge',
                    'hour_start' => null,
                    'hour_end'   => null,
                    'note'       => $note,
                ];
            }

            if ($candidate !== null) {
                $bridge_days = array_merge($bridge_days, $candidate);
            }
        }

        return array_merge($days, $bridge_days);
    }

    // Returns why a date is non-working (holiday name, or "Weekly Off"), or null if
    // it's a normal working day.
    private function _holiday_note_for_date($date, $weekly_off, $holiday_map)
    {
        if (isset($holiday_map[$date])) return $holiday_map[$date];
        if (in_array((int) date('w', strtotime($date)), $weekly_off)) return _l('hr_leave_weekly_off');
        return null;
    }

    private function _deduct_balance($emp_id, $type_id, $year, $days)
    {
        $bal = $this->_get_or_create_balance($emp_id, $type_id, $year);
        $this->db->where('id', $bal->id)
            ->update($this->tbl_balances, [
                'used_days'  => $bal->used_days + $days,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
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

    // Approving a request must always end up tracked in hr_leave_balances, even if
    // allocate_balances() was never run for this employee/type/year (e.g. a brand new
    // employee, or a leave type added after the year's balances were allocated).
    private function _get_or_create_balance($emp_id, $type_id, $year)
    {
        $bal = $this->get_balance($emp_id, $type_id, $year);
        if ($bal) return $bal;

        $type = $this->get_type($type_id);
        $this->db->insert($this->tbl_balances, [
            'employee_id'         => $emp_id,
            'leave_type_id'       => $type_id,
            'year'                => $year,
            'allocated_days'      => $type ? $type->days_per_year : 0,
            'used_days'           => 0,
            'carry_forward_days'  => 0,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
        return $this->get_balance($emp_id, $type_id, $year);
    }
}
