<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * A role-assigned person (HR/manager) assigns a training program to selected employees
 * and picks an instructor (a real staff account, so the instructor can log in). After the
 * training, the instructor (or HR) marks each enrolled employee's attendance (present /
 * absent).
 */
class Training_model extends App_Model
{
    private $table       = 'hr_training';
    private $parts_table = 'hr_training_participants';

    // ─── Programs ─────────────────────────────────────────────────────────────

    public function get($id)
    {
        $row = $this->db
            ->select('t.*, CONCAT(s.firstname," ",s.lastname) as instructor_name', false)
            ->from(db_prefix() . $this->table . ' t')
            ->join(db_prefix() . 'staff s', 's.staffid = t.instructor_id', 'left')
            ->where('t.id', $id)
            ->get()->row();
        if ($row) {
            $row->enrolled_count = $this->db
                ->where('training_id', $id)->count_all_results(db_prefix() . $this->parts_table);
            $row->present_count = $this->db
                ->where(['training_id' => $id, 'attendance_status' => 'present'])
                ->count_all_results(db_prefix() . $this->parts_table);
        }
        return $row;
    }

    public function is_instructor($training_id, $staff_id)
    {
        if (!$staff_id) return false;
        return (bool) $this->db->where(['id' => $training_id, 'instructor_id' => $staff_id])
            ->get(db_prefix() . $this->table)->row();
    }

    // Whether this staff member is enrolled in (as employee) or assigned to (as
    // instructor) at least one training - lets them reach their own list/menu item
    // without needing module-wide view/view_own permission on their role.
    public function has_own_or_instructor($employee_id, $staff_id)
    {
        $conditions = [];
        if ($employee_id) {
            $conditions[] = 'EXISTS (SELECT 1 FROM ' . db_prefix() . $this->parts_table . ' p WHERE p.employee_id = ' . (int) $employee_id . ')';
        }
        if ($staff_id) {
            $conditions[] = 'instructor_id = ' . (int) $staff_id;
        }
        if (!$conditions) return false;

        return (bool) $this->db->query(
            'SELECT 1 FROM ' . db_prefix() . $this->table . ' WHERE ' . implode(' OR ', $conditions) . ' LIMIT 1'
        )->row();
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('t.*, CONCAT(s.firstname," ",s.lastname) as instructor_name,
                           (SELECT COUNT(*) FROM '.db_prefix().$this->parts_table.' p WHERE p.training_id=t.id) as enrolled_count', false)
            ->from(db_prefix() . $this->table . ' t')
            ->join(db_prefix() . 'staff s', 's.staffid = t.instructor_id', 'left');

        if (!empty($filters['status']))               $this->db->where('t.status', $filters['status']);
        if (!empty($filters['from_date']))            $this->db->where('t.start_date >=', $filters['from_date']);
        if (!empty($filters['to_date']))              $this->db->where('t.end_date <=', $filters['to_date']);

        // Self-service (no module-wide "view"): trainings they're enrolled in, or
        // trainings they've been picked as the instructor for.
        if (!empty($filters['own_or_instructor'])) {
            $own = $filters['own_or_instructor'];
            $this->db->group_start()
                ->where("EXISTS (SELECT 1 FROM " . db_prefix() . $this->parts_table . " p2 WHERE p2.training_id = t.id AND p2.employee_id = " . (int) $own['employee_id'] . ")")
                ->or_where('t.instructor_id', $own['staff_id'])
                ->group_end();
        } elseif (!empty($filters['participant_employee_id'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . $this->parts_table . ' p2 WHERE p2.training_id = t.id AND p2.employee_id = ' . (int) $filters['participant_employee_id'] . ')');
        }

        return $this->db->order_by('t.start_date DESC')->get()->result();
    }

    public function add($data)
    {
        $record = [
            'title'         => $data['title'],
            'instructor_id' => !empty($data['instructor_id']) ? (int) $data['instructor_id'] : null,
            'venue'         => $data['venue']        ?? null,
            'start_date'    => $data['start_date'],
            'end_date'      => $data['end_date'],
            'cost'          => (float) ($data['cost'] ?? 0),
            'capacity'      => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'description'   => $data['description'] ?? null,
            'status'        => $data['status'] ?? 'scheduled',
            'created_by'    => get_staff_user_id(),
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['attachment'])) $record['attachment'] = $data['attachment'];
        $this->db->insert(db_prefix() . $this->table, $record);
        $id = $this->db->insert_id();
        return $id ? ['success' => true, 'id' => $id, 'message' => _l('hr_training_added')]
                   : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function update($data, $id)
    {
        $update = [
            'title'         => $data['title'],
            'instructor_id' => !empty($data['instructor_id']) ? (int) $data['instructor_id'] : null,
            'venue'         => $data['venue']        ?? null,
            'start_date'    => $data['start_date'],
            'end_date'      => $data['end_date'],
            'cost'          => (float) ($data['cost'] ?? 0),
            'capacity'      => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'description'   => $data['description'] ?? null,
            'status'        => $data['status'] ?? 'scheduled',
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['attachment'])) $update['attachment'] = $data['attachment'];
        $this->db->where('id', $id)->update(db_prefix() . $this->table, $update);
        return ['success' => true, 'message' => _l('hr_training_updated')];
    }

    public function delete($id)
    {
        $this->db->where('training_id', $id)->delete(db_prefix() . $this->parts_table);
        $this->db->where('id', $id)->delete(db_prefix() . $this->table);
        return ['success' => true, 'message' => _l('hr_training_deleted')];
    }

    // ─── Participants ─────────────────────────────────────────────────────────

    public function get_participants($training_id)
    {
        return $this->db
            ->select('p.*, e.first_name, e.last_name, e.employee_code, e.email,
                      d.name as department_name, ds.name as designation_name')
            ->from(db_prefix() . $this->parts_table . ' p')
            ->join(db_prefix() . 'hr_employees e',    'e.id = p.employee_id',      'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds','ds.id = e.designation_id',  'left')
            ->where('p.training_id', $training_id)
            ->order_by('e.first_name')
            ->get()->result();
    }

    public function enroll($training_id, $employee_ids)
    {
        $training = $this->db->where('id', $training_id)->get(db_prefix() . $this->table)->row();
        if (!$training || $training->status === 'cancelled') {
            return ['success' => false, 'message' => 'Cannot enroll in a cancelled training.'];
        }

        $existing = $this->db->select('employee_id')->where('training_id', $training_id)
            ->get(db_prefix() . $this->parts_table)->result_array();
        $already  = array_column($existing, 'employee_id');

        // Capacity check
        if ($training->capacity) {
            $slots = $training->capacity - count($already);
            if ($slots <= 0) return ['success' => false, 'message' => 'Training is at full capacity.'];
            $employee_ids = array_slice($employee_ids, 0, $slots);
        }

        $added = 0;
        foreach ($employee_ids as $eid) {
            $eid = (int) $eid;
            if (in_array($eid, $already)) continue;
            $this->db->insert(db_prefix() . $this->parts_table, [
                'training_id' => $training_id,
                'employee_id' => $eid,
                'enrolled_at' => date('Y-m-d H:i:s'),
                'completed'   => 0,
                'attendance_status' => 'pending',
            ]);
            $added++;
        }
        return ['success' => true, 'message' => "$added participant(s) enrolled."];
    }

    public function remove_participant($training_id, $employee_id)
    {
        $this->db->where(['training_id' => $training_id, 'employee_id' => $employee_id])
            ->delete(db_prefix() . $this->parts_table);
        return ['success' => true];
    }

    // Instructor (or HR) marks an enrolled employee present/absent for this training.
    public function mark_attendance($training_id, $employee_id, $status, $date = null)
    {
        if (!in_array($status, ['present', 'absent'], true)) {
            return ['success' => false, 'message' => 'Invalid attendance status.'];
        }
        $this->db->where(['training_id' => $training_id, 'employee_id' => $employee_id])
            ->update(db_prefix() . $this->parts_table, [
                'attendance_status' => $status,
                'completed'         => $status === 'present' ? 1 : 0,
                'completion_date'   => $status === 'present' ? ($date ?: date('Y-m-d')) : null,
            ]);
        return ['success' => true, 'message' => $status === 'present' ? _l('hr_training_marked_present') : _l('hr_training_marked_absent')];
    }
}
