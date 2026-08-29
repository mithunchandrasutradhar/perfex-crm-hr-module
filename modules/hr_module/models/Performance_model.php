<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Performance works as Targets: a role-assigned person (HR/manager, anyone with
 * 'create'/'edit' on hr_performance) assigns an employee an overall Target, which can
 * contain several Sub-Targets - each with its own title, due date, evaluators, progress
 * (in progress / partially completed with a % and note / completed) and evaluator
 * feedback. The employee, every evaluator assigned to a given sub-target, and HR can all
 * change that sub-target's status.
 */
class Performance_model extends App_Model
{
    private $targets_table     = 'hr_performance_targets';
    private $sub_targets_table = 'hr_performance_sub_targets';
    private $evaluators_table  = 'hr_performance_sub_target_evaluators';
    private $feedback_table    = 'hr_performance_sub_target_feedback';

    // ─── Targets (parent) ────────────────────────────────────────────────────

    public function get_target($id)
    {
        return $this->db
            ->select('t.*, e.first_name, e.last_name, e.employee_code, e.department_id as employee_department_id,
                      d.name as department_name, ds.name as designation_name,
                      CONCAT(s.firstname," ",s.lastname) as assigned_by_name', false)
            ->from(db_prefix() . $this->targets_table . ' t')
            ->join(db_prefix() . 'hr_employees e',    'e.id = t.employee_id',       'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds','ds.id = e.designation_id',   'left')
            ->join(db_prefix() . 'staff s',           's.staffid = t.assigned_by',  'left')
            ->where('t.id', $id)
            ->get()->row();
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('t.id, t.employee_id, t.title, t.due_date, t.created_at,
                           e.first_name, e.last_name, e.employee_code,
                           d.name as department_name,
                           CONCAT(s.firstname," ",s.lastname) as assigned_by_name,
                           COUNT(st.id) as sub_target_count,
                           SUM(st.status = "completed") as completed_count,
                           SUM(st.status = "pending") as pending_count', false)
            ->from(db_prefix() . $this->targets_table . ' t')
            ->join(db_prefix() . 'hr_employees e', 'e.id = t.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'staff s', 's.staffid = t.assigned_by', 'left')
            ->join(db_prefix() . $this->sub_targets_table . ' st', 'st.target_id = t.id', 'left');

        // Self-service (no module-wide "view"): only targets assigned to them, or targets
        // containing a sub-target they've been named an evaluator on.
        if (!empty($filters['own_or_evaluator'])) {
            $own = $filters['own_or_evaluator'];
            $this->db->join(db_prefix() . $this->evaluators_table . ' ev', 'ev.sub_target_id = st.id', 'left');
            $this->db->group_start()
                ->where('t.employee_id', $own['employee_id'])
                ->or_where('ev.staff_id', $own['staff_id'])
                ->group_end();
        } elseif (!empty($filters['employee_id'])) {
            $this->db->where('t.employee_id', $filters['employee_id']);
        }

        if (!empty($filters['department_id'])) $this->db->where('e.department_id', $filters['department_id']);
        if (!empty($filters['status']))        $this->db->where('st.status', $filters['status']);
        if (!empty($filters['year']))          $this->db->where('YEAR(t.created_at)', $filters['year']);
        if (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('CONCAT(e.first_name," ",e.last_name)', $filters['search'])
                ->or_like('e.employee_code', $filters['search'])
                ->or_like('t.title', $filters['search'])
                ->or_like('d.name', $filters['search'])
                ->group_end();
        }

        return $this->db->group_by('t.id')->order_by('t.created_at DESC')->get()->result();
    }

    // Every target assigned to one employee, each with its sub-targets nested in -
    // used to build that employee's performance report.
    public function get_employee_targets($employee_id)
    {
        $targets = $this->db
            ->select('t.*, CONCAT(s.firstname," ",s.lastname) as assigned_by_name', false)
            ->from(db_prefix() . $this->targets_table . ' t')
            ->join(db_prefix() . 'staff s', 's.staffid = t.assigned_by', 'left')
            ->where('t.employee_id', $employee_id)
            ->order_by('t.created_at', 'DESC')
            ->get()->result();

        foreach ($targets as $t) {
            $t->sub_targets = $this->get_sub_targets($t->id);
            foreach ($t->sub_targets as $st) {
                $st->evaluators = $this->get_evaluators($st->id);
                $st->feedback   = $this->get_feedback($st->id);
            }
        }
        return $targets;
    }

    public function assign($data)
    {
        $sub_targets = array_values(array_filter($data['sub_targets'] ?? [], function ($st) {
            return trim($st['title'] ?? '') !== '';
        }));
        if (empty($sub_targets)) {
            return ['success' => false, 'message' => _l('hr_performance_no_sub_targets')];
        }

        $this->db->insert(db_prefix() . $this->targets_table, [
            'employee_id' => (int) $data['employee_id'],
            'assigned_by' => get_staff_user_id(),
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date'    => $data['due_date'] ?: null,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $target_id = $this->db->insert_id();
        if (!$target_id) return ['success' => false, 'message' => _l('hr_error_saving')];

        foreach ($sub_targets as $st) {
            $this->add_sub_target($target_id, $st);
        }

        log_activity('HR Performance Target Assigned [ID: ' . $target_id . ', Employee ID: ' . $data['employee_id'] . ']');
        return ['success' => true, 'id' => $target_id, 'message' => _l('hr_performance_target_assigned')];
    }

    public function update_target($data, $id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->targets_table)->row();
        if (!$row) return ['success' => false, 'message' => 'Target not found.'];

        $this->db->where('id', $id)->update(db_prefix() . $this->targets_table, [
            'employee_id' => (int) $data['employee_id'],
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date'    => $data['due_date'] ?: null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Performance Target Updated [ID: ' . $id . ']');
        return ['success' => true, 'message' => _l('hr_performance_target_updated')];
    }

    public function delete($id)
    {
        $sub_target_ids = array_column($this->get_sub_targets($id), 'id');
        foreach ($sub_target_ids as $stid) {
            $this->db->where('sub_target_id', $stid)->delete(db_prefix() . $this->evaluators_table);
            $this->db->where('sub_target_id', $stid)->delete(db_prefix() . $this->feedback_table);
        }
        $this->db->where('target_id', $id)->delete(db_prefix() . $this->sub_targets_table);
        $this->db->where('id', $id)->delete(db_prefix() . $this->targets_table);
        log_activity('HR Performance Target Deleted [ID: ' . $id . ']');
        return ['success' => true, 'message' => _l('hr_performance_target_deleted')];
    }

    // ─── Sub-Targets (child) ─────────────────────────────────────────────────

    public function get_sub_targets($target_id)
    {
        return $this->db->where('target_id', $target_id)->order_by('id', 'ASC')
            ->get(db_prefix() . $this->sub_targets_table)->result();
    }

    public function get_sub_target($id)
    {
        return $this->db->where('id', $id)->get(db_prefix() . $this->sub_targets_table)->row();
    }

    public function get_evaluators($sub_target_id)
    {
        return $this->db
            ->select('ev.id, ev.staff_id, CONCAT(s.firstname," ",s.lastname) as name', false)
            ->from(db_prefix() . $this->evaluators_table . ' ev')
            ->join(db_prefix() . 'staff s', 's.staffid = ev.staff_id', 'left')
            ->where('ev.sub_target_id', $sub_target_id)
            ->get()->result();
    }

    public function get_feedback($sub_target_id)
    {
        return $this->db
            ->select('f.*, CONCAT(s.firstname," ",s.lastname) as evaluator_name', false)
            ->from(db_prefix() . $this->feedback_table . ' f')
            ->join(db_prefix() . 'staff s', 's.staffid = f.evaluator_id', 'left')
            ->where('f.sub_target_id', $sub_target_id)
            ->order_by('f.created_at', 'ASC')
            ->get()->result();
    }

    public function is_evaluator($sub_target_id, $staff_id)
    {
        return (bool) $this->db->where('sub_target_id', $sub_target_id)->where('staff_id', $staff_id)
            ->get(db_prefix() . $this->evaluators_table)->row();
    }

    public function add_sub_target($target_id, $data)
    {
        if (trim($data['title'] ?? '') === '') {
            return ['success' => false, 'message' => _l('hr_performance_sub_target_title_required')];
        }
        $this->db->insert(db_prefix() . $this->sub_targets_table, [
            'target_id'   => $target_id,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date'    => $data['due_date'] ?: null,
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $sub_target_id = $this->db->insert_id();
        if (!$sub_target_id) return ['success' => false, 'message' => _l('hr_error_saving')];

        $this->_sync_evaluators($sub_target_id, $data['evaluator_ids'] ?? []);
        log_activity('HR Performance Sub-Target Added [ID: ' . $sub_target_id . ', Target ID: ' . $target_id . ']');
        return ['success' => true, 'id' => $sub_target_id, 'message' => _l('hr_performance_sub_target_added')];
    }

    public function update_sub_target($id, $data)
    {
        $row = $this->get_sub_target($id);
        if (!$row) return ['success' => false, 'message' => 'Sub-target not found.'];
        if (trim($data['title'] ?? '') === '') {
            return ['success' => false, 'message' => _l('hr_performance_sub_target_title_required')];
        }

        $this->db->where('id', $id)->update(db_prefix() . $this->sub_targets_table, [
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date'    => $data['due_date'] ?: null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        $this->_sync_evaluators($id, $data['evaluator_ids'] ?? []);
        log_activity('HR Performance Sub-Target Updated [ID: ' . $id . ']');
        return ['success' => true, 'message' => _l('hr_performance_sub_target_updated')];
    }

    // Only a still-pending sub-target (no progress recorded yet) can be removed, so a
    // employee's/evaluator's work is never silently lost.
    public function delete_sub_target($id)
    {
        $row = $this->get_sub_target($id);
        if (!$row) return ['success' => true];
        if ($row->status !== 'pending') {
            return ['success' => false, 'message' => _l('hr_performance_only_pending_deletable')];
        }
        $this->db->where('sub_target_id', $id)->delete(db_prefix() . $this->evaluators_table);
        $this->db->where('sub_target_id', $id)->delete(db_prefix() . $this->feedback_table);
        $this->db->where('id', $id)->delete(db_prefix() . $this->sub_targets_table);
        log_activity('HR Performance Sub-Target Deleted [ID: ' . $id . ']');
        return ['success' => true, 'message' => _l('hr_performance_sub_target_deleted')];
    }

    // Any of the three parties (employee, an assigned evaluator, or HR) can move a
    // sub-target through pending -> in_progress -> partially_completed (% + note) -> completed.
    public function update_status($id, $status, $percentage = null, $note = null)
    {
        $valid = ['pending', 'in_progress', 'partially_completed', 'completed'];
        if (!in_array($status, $valid, true)) {
            return ['success' => false, 'message' => 'Invalid status.'];
        }

        $update = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];

        if ($status === 'partially_completed') {
            $pct = (float) $percentage;
            if ($percentage === null || $percentage === '' || $pct < 0 || $pct > 100) {
                return ['success' => false, 'message' => _l('hr_performance_invalid_percentage')];
            }
            $update['completion_percentage'] = $pct;
        } elseif ($status === 'completed') {
            $update['completion_percentage'] = 100;
        } else {
            $update['completion_percentage'] = null;
        }

        if ($note !== null) $update['employee_note'] = $note;

        $this->db->where('id', $id)->update(db_prefix() . $this->sub_targets_table, $update);
        log_activity('HR Performance Sub-Target Status Updated [ID: ' . $id . ', Status: ' . $status . ']');
        return ['success' => true, 'message' => _l('hr_performance_status_updated')];
    }

    public function add_feedback($sub_target_id, $evaluator_id, $feedback, $rating = null)
    {
        if (trim((string) $feedback) === '') {
            return ['success' => false, 'message' => _l('hr_performance_feedback_required')];
        }
        $this->db->insert(db_prefix() . $this->feedback_table, [
            'sub_target_id' => $sub_target_id,
            'evaluator_id'  => $evaluator_id,
            'feedback'      => $feedback,
            'rating'        => $rating ?: null,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $feedback_id = $this->db->insert_id();
        if ($feedback_id) {
            log_activity('HR Performance Feedback Added [Sub-Target ID: ' . $sub_target_id . ', Evaluator ID: ' . $evaluator_id . ']');
        }
        return $feedback_id
            ? ['success' => true, 'message' => _l('hr_performance_feedback_added')]
            : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    private function _sync_evaluators($sub_target_id, $evaluator_ids)
    {
        $this->db->where('sub_target_id', $sub_target_id)->delete(db_prefix() . $this->evaluators_table);
        $evaluator_ids = array_values(array_unique(array_filter(array_map('intval', (array) $evaluator_ids))));
        if (!$evaluator_ids) return;

        $rows = [];
        $now  = date('Y-m-d H:i:s');
        foreach ($evaluator_ids as $sid) {
            $rows[] = ['sub_target_id' => $sub_target_id, 'staff_id' => $sid, 'created_at' => $now];
        }
        $this->db->insert_batch(db_prefix() . $this->evaluators_table, $rows);
    }
}
