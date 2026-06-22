<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Reports_model extends App_Model
{
    // ── Attendance ────────────────────────────────────────────────────────────
    public function attendance($f = [])
    {
        $this->db->select('a.*, e.first_name, e.last_name, e.employee_code, d.name as department_name')
            ->from(db_prefix() . 'hr_attendance a')
            ->join(db_prefix() . 'hr_employees e', 'e.id = a.employee_id', 'left')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left');
        if (!empty($f['employee_id']))   $this->db->where('a.employee_id', $f['employee_id']);
        if (!empty($f['department_id'])) $this->db->where('e.department_id', $f['department_id']);
        if (!empty($f['status']))        $this->db->where('a.status', $f['status']);
        if (!empty($f['month']))         $this->db->where('MONTH(a.attendance_date)', $f['month']);
        if (!empty($f['year']))          $this->db->where('YEAR(a.attendance_date)', $f['year']);
        return $this->db->order_by('a.attendance_date DESC')->get()->result();
    }

    public function attendance_summary($f = [])
    {
        $rows = $this->attendance($f);
        $s = ['present'=>0,'absent'=>0,'late'=>0,'half_day'=>0,'total_hours'=>0];
        foreach ($rows as $r) {
            $key = $r->status ?? 'present';
            if (isset($s[$key])) $s[$key]++;
            if ($r->in_time && $r->out_time) {
                $s['total_hours'] += (strtotime($r->out_time) - strtotime($r->in_time)) / 3600;
            }
        }
        return $s;
    }

    // ── Leave ─────────────────────────────────────────────────────────────────
    public function leave($f = [])
    {
        $this->db->select('l.*, e.first_name, e.last_name, e.employee_code, d.name as department_name, lt.name as leave_type_name')
            ->from(db_prefix() . 'hr_leave_requests l')
            ->join(db_prefix() . 'hr_employees e',    'e.id = l.employee_id', 'left')
            ->join(db_prefix() . 'hr_departments d',  'd.id = e.department_id', 'left')
            ->join(db_prefix() . 'hr_leave_types lt', 'lt.id = l.leave_type_id', 'left');
        if (!empty($f['employee_id']))   $this->db->where('l.employee_id', $f['employee_id']);
        if (!empty($f['department_id'])) $this->db->where('e.department_id', $f['department_id']);
        if (!empty($f['status']))        $this->db->where('l.status', $f['status']);
        if (!empty($f['leave_type_id'])) $this->db->where('l.leave_type_id', $f['leave_type_id']);
        if (!empty($f['from_date']))     $this->db->where('l.from_date >=', $f['from_date']);
        if (!empty($f['to_date']))       $this->db->where('l.to_date <=', $f['to_date']);
        return $this->db->order_by('l.from_date DESC')->get()->result();
    }

    // ── Payroll ───────────────────────────────────────────────────────────────
    public function payroll($f = [])
    {
        $this->db->select('p.*, e.first_name, e.last_name, e.employee_code, d.name as department_name')
            ->from(db_prefix() . 'hr_payroll p')
            ->join(db_prefix() . 'hr_employees e', 'e.id = p.employee_id', 'left')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left');
        if (!empty($f['department_id'])) $this->db->where('e.department_id', $f['department_id']);
        if (!empty($f['status']))        $this->db->where('p.status', $f['status']);
        if (!empty($f['month']))         $this->db->where('p.pay_month', $f['month']);
        if (!empty($f['year']))          $this->db->where('p.pay_year', $f['year']);
        return $this->db->order_by('p.pay_year DESC, p.pay_month DESC')->get()->result();
    }

    // ── Loans ─────────────────────────────────────────────────────────────────
    public function loans($f = [])
    {
        $this->db->select('l.*, e.first_name, e.last_name, e.employee_code, d.name as department_name')
            ->from(db_prefix() . 'hr_loans l')
            ->join(db_prefix() . 'hr_employees e', 'e.id = l.employee_id', 'left')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left');
        if (!empty($f['department_id'])) $this->db->where('e.department_id', $f['department_id']);
        if (!empty($f['status']))        $this->db->where('l.status', $f['status']);
        return $this->db->order_by('l.created_at DESC')->get()->result();
    }

    // ── Overtime ──────────────────────────────────────────────────────────────
    public function overtime($f = [])
    {
        $this->db->select('o.*, e.first_name, e.last_name, e.employee_code, d.name as department_name')
            ->from(db_prefix() . 'hr_overtime o')
            ->join(db_prefix() . 'hr_employees e', 'e.id = o.employee_id', 'left')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left');
        if (!empty($f['department_id'])) $this->db->where('e.department_id', $f['department_id']);
        if (!empty($f['status']))        $this->db->where('o.status', $f['status']);
        if (!empty($f['from_date']))     $this->db->where('o.overtime_date >=', $f['from_date']);
        if (!empty($f['to_date']))       $this->db->where('o.overtime_date <=', $f['to_date']);
        return $this->db->order_by('o.overtime_date DESC')->get()->result();
    }

    // ── Performance ───────────────────────────────────────────────────────────
    public function performance($f = [])
    {
        $this->db->select('p.*, e.first_name, e.last_name, e.employee_code, d.name as department_name,
                           CONCAT(s.firstname," ",s.lastname) as reviewer_name')
            ->from(db_prefix() . 'hr_performance_reviews p')
            ->join(db_prefix() . 'hr_employees e',  'e.id = p.employee_id', 'left')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left')
            ->join(db_prefix() . 'staff s',          's.staffid = p.reviewer_id', 'left');
        if (!empty($f['department_id'])) $this->db->where('e.department_id', $f['department_id']);
        if (!empty($f['year']))          $this->db->where('YEAR(p.review_period_start)', $f['year']);
        if (!empty($f['rating']))        $this->db->where('p.rating', $f['rating']);
        if (!empty($f['status']))        $this->db->where('p.status', $f['status']);
        return $this->db->order_by('p.created_at DESC')->get()->result();
    }

    // ── Training ──────────────────────────────────────────────────────────────
    public function training($f = [])
    {
        $this->db->select('t.*,
            (SELECT COUNT(*) FROM ' . db_prefix() . 'hr_training_participants tp WHERE tp.training_id = t.id) as enrolled,
            (SELECT COUNT(*) FROM ' . db_prefix() . 'hr_training_participants tp WHERE tp.training_id = t.id AND tp.status = "completed") as completed')
            ->from(db_prefix() . 'hr_training_programs t');
        if (!empty($f['status']))    $this->db->where('t.status', $f['status']);
        if (!empty($f['year']))      $this->db->where('YEAR(t.start_date)', $f['year']);
        return $this->db->order_by('t.start_date DESC')->get()->result();
    }

    // ── Headcount ─────────────────────────────────────────────────────────────
    public function headcount($f = [])
    {
        $this->db->select('d.name as department_name, d.id as department_id,
            COUNT(e.id) as total,
            SUM(CASE WHEN e.status = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN e.status = 0 THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN e.employment_type = "permanent" THEN 1 ELSE 0 END) as permanent,
            SUM(CASE WHEN e.employment_type = "contract" THEN 1 ELSE 0 END) as contract,
            SUM(CASE WHEN e.employment_type = "parttime" THEN 1 ELSE 0 END) as parttime,
            SUM(CASE WHEN e.gender = "male" THEN 1 ELSE 0 END) as male,
            SUM(CASE WHEN e.gender = "female" THEN 1 ELSE 0 END) as female')
            ->from(db_prefix() . 'hr_employees e')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left')
            ->group_by('e.department_id');
        return $this->db->get()->result();
    }

    // ── Department Summary ────────────────────────────────────────────────────
    public function department($f = [])
    {
        $dept_id = $f['department_id'] ?? null;
        $year    = $f['year'] ?? date('Y');

        $this->db->select('e.*, d.name as department_name, ds.name as designation_name')
            ->from(db_prefix() . 'hr_employees e')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds', 'ds.id = e.designation_id', 'left')
            ->where('e.status', 1);
        if ($dept_id) $this->db->where('e.department_id', $dept_id);
        $employees = $this->db->order_by('e.first_name')->get()->result();

        // Leave taken this year
        $leave = $this->db->select('l.employee_id, SUM(l.days_requested) as total_days')
            ->from(db_prefix() . 'hr_leave_requests l')
            ->join(db_prefix() . 'hr_employees e', 'e.id = l.employee_id', 'left')
            ->where('l.status', 'approved')
            ->where('YEAR(l.from_date)', $year);
        if ($dept_id) $leave->where('e.department_id', $dept_id);
        $leave_data = $leave->group_by('l.employee_id')->get()->result_array();
        $leave_map = [];
        foreach ($leave_data as $row) $leave_map[$row['employee_id']] = $row['total_days'];

        // Payroll for this year
        $payroll = $this->db->select('p.employee_id, SUM(p.net_salary) as total_net')
            ->from(db_prefix() . 'hr_payroll p')
            ->join(db_prefix() . 'hr_employees e', 'e.id = p.employee_id', 'left')
            ->where('p.pay_year', $year);
        if ($dept_id) $payroll->where('e.department_id', $dept_id);
        $payroll_data = $payroll->group_by('p.employee_id')->get()->result_array();
        $payroll_map = [];
        foreach ($payroll_data as $row) $payroll_map[$row['employee_id']] = $row['total_net'];

        return ['employees' => $employees, 'leave_map' => $leave_map, 'payroll_map' => $payroll_map];
    }

    // ── Salary ────────────────────────────────────────────────────────────────
    public function salary($f = [])
    {
        $this->db->select('e.first_name, e.last_name, e.employee_code, e.basic_salary, e.employment_type,
                           d.name as department_name, ds.name as designation_name')
            ->from(db_prefix() . 'hr_employees e')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds', 'ds.id = e.designation_id', 'left')
            ->where('e.status', 1);
        if (!empty($f['department_id'])) $this->db->where('e.department_id', $f['department_id']);
        return $this->db->order_by('e.basic_salary DESC')->get()->result();
    }

    public function salary_summary_by_dept($f = [])
    {
        $this->db->select('d.name as department_name,
            COUNT(e.id) as headcount,
            AVG(e.basic_salary) as avg_salary,
            MIN(e.basic_salary) as min_salary,
            MAX(e.basic_salary) as max_salary,
            SUM(e.basic_salary) as total_salary')
            ->from(db_prefix() . 'hr_employees e')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left')
            ->where('e.status', 1)
            ->group_by('e.department_id');
        return $this->db->get()->result();
    }

    // ── Turnover ──────────────────────────────────────────────────────────────
    public function turnover($f = [])
    {
        $year = $f['year'] ?? date('Y');
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $joined = $this->db->select('COUNT(*) as cnt')
                ->from(db_prefix() . 'hr_employees')
                ->where('YEAR(joining_date)', $year)
                ->where('MONTH(joining_date)', $m)
                ->get()->row()->cnt;

            $left = $this->db->select('COUNT(*) as cnt')
                ->from(db_prefix() . 'hr_employees')
                ->where('status', 0)
                ->where('YEAR(updated_at)', $year)
                ->where('MONTH(updated_at)', $m)
                ->get()->row()->cnt;

            $months[] = ['month' => $m, 'joined' => (int)$joined, 'left' => (int)$left];
        }
        $total_active = $this->db->where('status', 1)->count_all_results(db_prefix() . 'hr_employees');
        $total_left   = $this->db->where('status', 0)->where('YEAR(updated_at)', $year)->count_all_results(db_prefix() . 'hr_employees');
        $total_joined = $this->db->where('YEAR(joining_date)', $year)->count_all_results(db_prefix() . 'hr_employees');
        return [
            'months'       => $months,
            'total_active' => $total_active,
            'total_left'   => $total_left,
            'total_joined' => $total_joined,
            'turnover_rate'=> $total_active > 0 ? round(($total_left / ($total_active + $total_left)) * 100, 1) : 0,
        ];
    }
}
