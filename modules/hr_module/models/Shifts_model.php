<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Shifts_model extends App_Model
{
    private $tbl_types;
    private $tbl_assignments;

    public function __construct()
    {
        parent::__construct();
        $this->tbl_types       = db_prefix() . 'hr_shift_types';
        $this->tbl_assignments = db_prefix() . 'hr_shift_assignments';
        $this->_ensure_tables();
    }

    // Lazily creates the shift tables on first use, so this works immediately
    // without requiring the module to be reactivated (mirrors install.php).
    private function _ensure_tables()
    {
        if (!$this->db->table_exists($this->tbl_types)) {
            $this->db->query('CREATE TABLE `' . $this->tbl_types . '` (
              `id` int(11) NOT NULL,
              `name` varchar(191) NOT NULL,
              `start_time` time NOT NULL,
              `end_time` time NOT NULL,
              `status` tinyint(1) NOT NULL DEFAULT 1,
              `created_at` datetime NOT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ';');
            $this->db->query('ALTER TABLE `' . $this->tbl_types . '` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');

            $now = date('Y-m-d H:i:s');
            $this->db->query("INSERT INTO `" . $this->tbl_types . "` (`name`, `start_time`, `end_time`, `status`, `created_at`) VALUES
              ('Morning Shift', '06:00:00', '14:00:00', 1, '$now'),
              ('Evening Shift', '14:00:00', '22:00:00', 1, '$now'),
              ('Night Shift', '22:00:00', '06:00:00', 1, '$now')");
        }
        if (!$this->db->table_exists($this->tbl_assignments)) {
            $this->db->query('CREATE TABLE `' . $this->tbl_assignments . '` (
              `id` int(11) NOT NULL,
              `employee_id` int(11) NOT NULL,
              `shift_type_id` int(11) NOT NULL,
              `from_date` date NOT NULL,
              `to_date` date NOT NULL,
              `status` enum(\'pending\',\'approved\',\'rejected\') NOT NULL DEFAULT \'pending\',
              `reason` text DEFAULT NULL,
              `rejection_reason` text DEFAULT NULL,
              `created_by` int(11) DEFAULT NULL,
              `approved_by` int(11) DEFAULT NULL,
              `approved_at` datetime DEFAULT NULL,
              `created_at` datetime NOT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `employee_id` (`employee_id`),
              KEY `shift_type_id` (`shift_type_id`),
              KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ';');
            $this->db->query('ALTER TABLE `' . $this->tbl_assignments . '` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
        }
    }

    // ── Shift Types ──────────────────────────────────────────────────────

    public function get_type($id = null)
    {
        if ($id) {
            return $this->db->where('id', $id)->get($this->tbl_types)->row();
        }
        return $this->db->order_by('start_time', 'ASC')->get($this->tbl_types)->result();
    }

    public function get_active_types()
    {
        return $this->db->where('status', 1)->order_by('start_time', 'ASC')->get($this->tbl_types)->result();
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
        return $this->db->where('id', $id)->update($this->tbl_types, $data);
    }

    public function delete_type($id)
    {
        if ($this->db->where('shift_type_id', $id)->count_all_results($this->tbl_assignments) > 0) {
            return ['success' => false, 'message' => 'This shift is already assigned to employees and cannot be deleted.'];
        }
        $this->db->where('id', $id)->delete($this->tbl_types);
        return ['success' => true];
    }

    // ── Shift Assignments ────────────────────────────────────────────────

    public function get($id)
    {
        return $this->db->select('a.*, st.name as shift_name, st.start_time, st.end_time,
                CONCAT(e.first_name," ",e.last_name) as employee_name, e.employee_code, e.staff_id as employee_staff_id,
                e.email as employee_email,
                d.name as department_name, ds.name as designation_name,
                CONCAT(cb.firstname," ",cb.lastname) as created_by_name,
                CONCAT(ab.firstname," ",ab.lastname) as approved_by_name', false)
            ->from($this->tbl_assignments . ' a')
            ->join($this->tbl_types . ' st', 'st.id = a.shift_type_id', 'left')
            ->join(db_prefix() . 'hr_employees e', 'e.id = a.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds', 'ds.id = e.designation_id', 'left')
            ->join(db_prefix() . 'staff cb', 'cb.staffid = a.created_by', 'left')
            ->join(db_prefix() . 'staff ab', 'ab.staffid = a.approved_by', 'left')
            ->where('a.id', $id)
            ->get()->row();
    }

    public function get_all($filters = [])
    {
        $this->db->select('a.*, st.name as shift_name,
                CONCAT(e.first_name," ",e.last_name) as employee_name, e.employee_code,
                d.name as department_name', false)
            ->from($this->tbl_assignments . ' a')
            ->join($this->tbl_types . ' st', 'st.id = a.shift_type_id', 'left')
            ->join(db_prefix() . 'hr_employees e', 'e.id = a.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left');

        if (!empty($filters['employee_id']))  $this->db->where('a.employee_id', $filters['employee_id']);
        if (!empty($filters['status']))       $this->db->where('a.status', $filters['status']);
        if (!empty($filters['shift_type_id'])) $this->db->where('a.shift_type_id', $filters['shift_type_id']);
        if (!empty($filters['department_id'])) $this->db->where('e.department_id', $filters['department_id']);

        $this->db->order_by('a.created_at', 'DESC');
        return $this->db->get()->result();
    }

    // Checks for an existing pending/approved assignment overlapping the given
    // range for this employee - one shift at a time per employee per day.
    private function _has_overlap($employee_id, $from, $to, $exclude_id = null)
    {
        $this->db->where('employee_id', $employee_id)
            ->where_in('status', ['pending', 'approved'])
            ->where('from_date <=', $to)
            ->where('to_date >=', $from);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results($this->tbl_assignments) > 0;
    }

    public function apply($data)
    {
        if ($data['to_date'] < $data['from_date']) {
            return ['success' => false, 'message' => 'The end date cannot be before the start date.'];
        }
        if ($this->_has_overlap($data['employee_id'], $data['from_date'], $data['to_date'])) {
            return ['success' => false, 'message' => 'This employee already has a shift request/assignment overlapping these dates.'];
        }
        $data['status']     = 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->tbl_assignments, $data);
        $id = $this->db->insert_id();
        if ($id) {
            log_activity('HR Shift Assignment Submitted [ID: ' . $id . ']');
            return ['success' => true, 'id' => $id];
        }
        return ['success' => false, 'message' => 'Could not save the shift assignment.'];
    }

    public function approve($id)
    {
        $assignment = $this->get($id);
        if (!$assignment || $assignment->status !== 'pending') {
            return ['success' => false, 'message' => 'Only a pending shift assignment can be approved.'];
        }
        $this->db->where('id', $id)->update($this->tbl_assignments, [
            'status'      => 'approved',
            'approved_by' => get_staff_user_id(),
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Shift Assignment Approved [ID: ' . $id . ']');
        return ['success' => true];
    }

    public function reject($id, $reason = '')
    {
        $assignment = $this->get($id);
        if (!$assignment || $assignment->status !== 'pending') {
            return ['success' => false, 'message' => 'Only a pending shift assignment can be rejected.'];
        }
        $this->db->where('id', $id)->update($this->tbl_assignments, [
            'status'           => 'rejected',
            'rejection_reason' => $reason ?: null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Shift Assignment Rejected [ID: ' . $id . ']');
        return ['success' => true];
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete($this->tbl_assignments);
        return $this->db->affected_rows() > 0;
    }

    // Groups every active employee by their shift on a specific date - any
    // employee with no approved shift assignment covering that date falls
    // into the default "Day Shift" bucket (key 0). Ordered by the shift
    // type's start_time, with the default bucket appended last.
    public function get_shift_roster_for_date($date)
    {
        $types = $this->get_active_types();

        $roster = [];
        foreach ($types as $t) {
            $roster[$t->id] = ['name' => $t->name, 'employees' => []];
        }
        $roster[0] = ['name' => _l('hr_shift_default_day'), 'employees' => []];

        $assigned = $this->db->select('employee_id, shift_type_id', false)
            ->from($this->tbl_assignments)
            ->where('status', 'approved')
            ->where('from_date <=', $date)
            ->where('to_date >=', $date)
            ->get()->result();

        $shift_type_by_employee = [];
        foreach ($assigned as $a) {
            $shift_type_by_employee[$a->employee_id] = (int) $a->shift_type_id;
        }

        $employees = $this->db->select('id, first_name, last_name, employee_code')
            ->where('status', 1)
            ->order_by('first_name', 'ASC')
            ->get(db_prefix() . 'hr_employees')->result();

        foreach ($employees as $e) {
            $type_id = $shift_type_by_employee[$e->id] ?? 0;
            if (!isset($roster[$type_id])) {
                $type_id = 0; // shift type since deleted/deactivated - fall back to default
            }
            $label = trim($e->first_name . ' ' . $e->last_name) . ($e->employee_code ? ' (' . $e->employee_code . ')' : '');
            $roster[$type_id]['employees'][] = $label;
        }

        return $roster;
    }

    // Returns the employee's approved shift assignment covering this date
    // (joined to its shift type's start/end time), or null if none - used to
    // make attendance status/hours calculation shift-aware.
    public function get_employee_shift_for_date($employee_id, $date)
    {
        return $this->db->select('a.id, a.shift_type_id, st.name as shift_name, st.start_time, st.end_time', false)
            ->from($this->tbl_assignments . ' a')
            ->join($this->tbl_types . ' st', 'st.id = a.shift_type_id', 'left')
            ->where('a.employee_id', $employee_id)
            ->where('a.status', 'approved')
            ->where('a.from_date <=', $date)
            ->where('a.to_date >=', $date)
            ->order_by('a.id', 'DESC')
            ->get()->row();
    }

    // ── Calendar / Payroll helpers ───────────────────────────────────────

    // Every approved shift day falling within [$from,$to] (inclusive), with the
    // employee's name and shift - used for the Official Calendar's shift roster.
    public function get_approved_shifts_in_range($from, $to)
    {
        return $this->db->select('a.employee_id, a.from_date, a.to_date, st.name as shift_name,
                CONCAT(e.first_name," ",e.last_name) as employee_name, e.employee_code', false)
            ->from($this->tbl_assignments . ' a')
            ->join($this->tbl_types . ' st', 'st.id = a.shift_type_id', 'left')
            ->join(db_prefix() . 'hr_employees e', 'e.id = a.employee_id', 'left')
            ->where('a.status', 'approved')
            ->where('a.from_date <=', $to)
            ->where('a.to_date >=', $from)
            ->get()->result();
    }

    // Summarizes an employee's approved shift days that fall inside [$from,$to]
    // (a payroll pay period) as "Night Shift (10), Morning Shift (5)" - clipping
    // each assignment's range to the period so day counts don't overrun it.
    public function get_employee_shift_summary($employee_id, $from, $to)
    {
        $rows = $this->db->select('st.name as shift_name, a.from_date, a.to_date', false)
            ->from($this->tbl_assignments . ' a')
            ->join($this->tbl_types . ' st', 'st.id = a.shift_type_id', 'left')
            ->where('a.employee_id', $employee_id)
            ->where('a.status', 'approved')
            ->where('a.from_date <=', $to)
            ->where('a.to_date >=', $from)
            ->get()->result();

        if (empty($rows)) {
            return '-';
        }

        $counts = [];
        foreach ($rows as $r) {
            $clip_from = max($r->from_date, $from);
            $clip_to   = min($r->to_date, $to);
            $days = (strtotime($clip_to) - strtotime($clip_from)) / 86400 + 1;
            if ($days < 1) continue;
            $name = $r->shift_name ?: 'Unknown Shift';
            $counts[$name] = ($counts[$name] ?? 0) + $days;
        }

        if (empty($counts)) {
            return '-';
        }
        $parts = [];
        foreach ($counts as $name => $days) {
            $parts[] = $name . ' (' . $days . ')';
        }
        return implode(', ', $parts);
    }
}
