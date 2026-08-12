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
    private $table            = 'hr_training';
    private $parts_table      = 'hr_training_participants';
    private $attendance_table = 'hr_training_attendance';
    private $sessions_table   = 'hr_training_sessions';

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
            // Attendance % is measured per DAY, not per participant, so a partially
            // attended multi-day training still shows an accurate overall rate.
            $row->total_day_marks   = $this->db
                ->where('training_id', $id)->count_all_results(db_prefix() . $this->attendance_table);
            $row->present_day_marks = $this->db
                ->where(['training_id' => $id, 'status' => 'present'])
                ->count_all_results(db_prefix() . $this->attendance_table);
        }
        return $row;
    }

    public function is_instructor($training_id, $staff_id)
    {
        if (!$staff_id) return false;
        return (bool) $this->db->where(['id' => $training_id, 'instructor_id' => $staff_id])
            ->get(db_prefix() . $this->table)->row();
    }

    public function is_participant($training_id, $employee_id)
    {
        if (!$employee_id) return false;
        return (bool) $this->db->where(['training_id' => $training_id, 'employee_id' => $employee_id])
            ->get(db_prefix() . $this->parts_table)->row();
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
        // Note: the raw EXISTS(...) string needs escape=false, otherwise CI's
        // identifier-protection mangles the subquery alias (p2 -> `tblp2`, since
        // db_prefix() is "tbl") and the query fails.
        if (!empty($filters['own_or_instructor'])) {
            $own = $filters['own_or_instructor'];
            $this->db->group_start()
                ->where("EXISTS (SELECT 1 FROM " . db_prefix() . $this->parts_table . " p2 WHERE p2.training_id = t.id AND p2.employee_id = " . (int) $own['employee_id'] . ")", null, false)
                ->or_where('t.instructor_id', $own['staff_id'])
                ->group_end();
        } elseif (!empty($filters['participant_employee_id'])) {
            $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . $this->parts_table . ' p2 WHERE p2.training_id = t.id AND p2.employee_id = ' . (int) $filters['participant_employee_id'] . ')', null, false);
        }

        return $this->db->order_by('t.start_date DESC')->get()->result();
    }

    public function add($data)
    {
        $today = date('Y-m-d');
        $record = [
            'title'         => $data['title'],
            'instructor_id' => !empty($data['instructor_id']) ? (int) $data['instructor_id'] : null,
            'venue'         => $data['venue']        ?? null,
            // Placeholder - save_sessions() below recomputes this from the actual
            // day-by-day entries (start_date/end_date can't be NULL).
            'start_date'    => $today,
            'end_date'      => $today,
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
        if ($id && !empty($data['sessions'])) $this->save_sessions($id, $data['sessions']);
        return $id ? ['success' => true, 'id' => $id, 'message' => _l('hr_training_added')]
                   : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function update($data, $id)
    {
        $update = [
            'title'         => $data['title'],
            'instructor_id' => !empty($data['instructor_id']) ? (int) $data['instructor_id'] : null,
            'venue'         => $data['venue']        ?? null,
            'cost'          => (float) ($data['cost'] ?? 0),
            'capacity'      => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'description'   => $data['description'] ?? null,
            'status'        => $data['status'] ?? 'scheduled',
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['attachment'])) $update['attachment'] = $data['attachment'];
        $this->db->where('id', $id)->update(db_prefix() . $this->table, $update);

        if (isset($data['sessions'])) {
            $this->save_sessions($id, $data['sessions']);
        }

        return ['success' => true, 'message' => _l('hr_training_updated')];
    }

    // Instructor (or HR) marks the whole training session as completed, optionally
    // leaving a closing note (summary/feedback) about how it went.
    public function mark_complete($id, $note = null)
    {
        $update = [
            'status'     => 'completed',
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($note !== null && $note !== '') $update['completion_note'] = $note;
        $this->db->where('id', $id)->update(db_prefix() . $this->table, $update);
        return ['success' => true, 'message' => _l('hr_training_marked_complete')];
    }

    public function delete($id)
    {
        $this->db->where('training_id', $id)->delete(db_prefix() . $this->parts_table);
        $this->db->where('training_id', $id)->delete(db_prefix() . $this->attendance_table);
        $this->db->where('training_id', $id)->delete(db_prefix() . $this->sessions_table);
        $this->db->where('id', $id)->delete(db_prefix() . $this->table);
        return ['success' => true, 'message' => _l('hr_training_deleted')];
    }

    // ─── Sessions (day-by-day schedule) ─────────────────────────────────────────

    public function get_sessions($training_id)
    {
        return $this->db->where('training_id', $training_id)
            ->order_by('session_date', 'ASC')
            ->get(db_prefix() . $this->sessions_table)->result();
    }

    // Replaces the full session list for a training, recomputes the derived
    // start_date/end_date range, and extends daily attendance rows for everyone
    // already enrolled to cover any newly added session dates. Removing a session
    // date never deletes its existing attendance marks - it just stops appearing
    // in the day-by-day grid.
    public function save_sessions($training_id, $sessions)
    {
        $this->db->where('training_id', $training_id)->delete(db_prefix() . $this->sessions_table);

        $dates = [];
        foreach ($sessions as $s) {
            if (empty($s['session_date'])) continue;
            $dates[] = $s['session_date'];
            $this->db->insert(db_prefix() . $this->sessions_table, [
                'training_id'  => $training_id,
                'session_date' => $s['session_date'],
                'start_time'   => $s['start_time'] ?: null,
                'end_time'     => $s['end_time'] ?: null,
            ]);
        }
        if (!$dates) return;
        sort($dates);
        $this->db->where('id', $training_id)->update(db_prefix() . $this->table, [
            'start_date' => reset($dates),
            'end_date'   => end($dates),
        ]);

        $employee_ids = array_column(
            $this->db->select('employee_id')->where('training_id', $training_id)
                ->get(db_prefix() . $this->parts_table)->result_array(),
            'employee_id'
        );
        foreach ($employee_ids as $eid) {
            $this->_generate_attendance_rows($training_id, $eid, $dates);
        }
    }

    // ─── Daily attendance ───────────────────────────────────────────────────────

    // employee_id => [date => status] for every marked/generated day of this training.
    public function get_attendance_grid($training_id)
    {
        $rows = $this->db->where('training_id', $training_id)
            ->get(db_prefix() . $this->attendance_table)->result();
        $grid = [];
        foreach ($rows as $r) {
            $grid[$r->employee_id][$r->attendance_date] = $r->status;
        }
        return $grid;
    }

    private function _generate_attendance_rows($training_id, $employee_id, array $dates)
    {
        foreach ($dates as $day) {
            $this->db->query(
                'INSERT IGNORE INTO ' . db_prefix() . $this->attendance_table
                . ' (training_id, employee_id, attendance_date, status) VALUES (?, ?, ?, \'pending\')',
                [$training_id, $employee_id, $day]
            );
        }
    }

    // Instructor (or HR) confirms one enrolled employee's attendance for a single day.
    public function mark_daily_attendance($training_id, $employee_id, $date, $status)
    {
        if (!in_array($status, ['present', 'absent'], true)) {
            return ['success' => false, 'message' => 'Invalid attendance status.'];
        }
        $this->db->query(
            'INSERT INTO ' . db_prefix() . $this->attendance_table
            . ' (training_id, employee_id, attendance_date, status, marked_by, marked_at) VALUES (?, ?, ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by), marked_at = VALUES(marked_at)',
            [$training_id, $employee_id, $date, $status, get_staff_user_id(), date('Y-m-d H:i:s')]
        );
        $this->_recompute_overall_status($training_id, $employee_id);
        return ['success' => true, 'message' => $status === 'present' ? _l('hr_training_marked_present') : _l('hr_training_marked_absent')];
    }

    // Overall attendance_status = pending while any day is still unconfirmed;
    // once every day is confirmed it's present (all days attended), absent (missed
    // every day), or partial (attended some days but missed others) - a multi-day
    // training shouldn't show someone as flatly "Absent" for missing a single day.
    private function _recompute_overall_status($training_id, $employee_id)
    {
        $statuses = array_column(
            $this->db->select('status')->where(['training_id' => $training_id, 'employee_id' => $employee_id])
                ->get(db_prefix() . $this->attendance_table)->result_array(),
            'status'
        );

        if (!$statuses || in_array('pending', $statuses, true)) {
            $overall = 'pending';
        } elseif (!in_array('absent', $statuses, true)) {
            $overall = 'present';
        } elseif (!in_array('present', $statuses, true)) {
            $overall = 'absent';
        } else {
            $overall = 'partial';
        }

        $this->db->where(['training_id' => $training_id, 'employee_id' => $employee_id])
            ->update(db_prefix() . $this->parts_table, [
                'attendance_status' => $overall,
                'completed'         => $overall === 'present' ? 1 : 0,
                'completion_date'   => $overall === 'present' ? date('Y-m-d') : null,
            ]);
    }

    // ─── Participants ─────────────────────────────────────────────────────────

    public function get_participants($training_id)
    {
        return $this->db
            ->select('p.*, e.first_name, e.last_name, e.employee_code, e.email,
                      d.name as department_name, ds.name as designation_name,
                      (SELECT COUNT(*) FROM '.db_prefix().$this->attendance_table.' a
                        WHERE a.training_id = p.training_id AND a.employee_id = p.employee_id) as total_days,
                      (SELECT COUNT(*) FROM '.db_prefix().$this->attendance_table.' a
                        WHERE a.training_id = p.training_id AND a.employee_id = p.employee_id AND a.status = "present") as present_days', false)
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

        $session_dates = array_column($this->get_sessions($training_id), 'session_date');

        $added = 0;
        $enrolled_ids = [];
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
            $this->_generate_attendance_rows($training_id, $eid, $session_dates);
            $added++;
            $enrolled_ids[] = $eid;
        }
        return ['success' => true, 'message' => "$added participant(s) enrolled.", 'enrolled_ids' => $enrolled_ids];
    }

    public function remove_participant($training_id, $employee_id)
    {
        $this->db->where(['training_id' => $training_id, 'employee_id' => $employee_id])
            ->delete(db_prefix() . $this->parts_table);
        $this->db->where(['training_id' => $training_id, 'employee_id' => $employee_id])
            ->delete(db_prefix() . $this->attendance_table);
        return ['success' => true];
    }

    // Instructor/HR's private note about how this specific employee did.
    public function save_employee_note($training_id, $employee_id, $note)
    {
        $this->db->where(['training_id' => $training_id, 'employee_id' => $employee_id])
            ->update(db_prefix() . $this->parts_table, ['notes' => $note]);
        return ['success' => true, 'message' => _l('hr_training_note_saved')];
    }

    // The employee's own feedback about the instructor/training session.
    public function save_employee_feedback($training_id, $employee_id, $feedback)
    {
        $this->db->where(['training_id' => $training_id, 'employee_id' => $employee_id])
            ->update(db_prefix() . $this->parts_table, ['employee_feedback' => $feedback]);
        return ['success' => true, 'message' => _l('hr_training_feedback_saved')];
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
