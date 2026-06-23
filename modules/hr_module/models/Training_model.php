<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Training_model extends App_Model
{
    private $table        = 'hr_training';
    private $parts_table  = 'hr_training_participants';

    // ─── Programs ─────────────────────────────────────────────────────────────

    public function get($id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if ($row) {
            $row->enrolled_count = $this->db
                ->where('training_id', $id)->count_all_results(db_prefix() . $this->parts_table);
            $row->completed_count = $this->db
                ->where(['training_id' => $id, 'completed' => 1])
                ->count_all_results(db_prefix() . $this->parts_table);
        }
        return $row;
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('t.*, (SELECT COUNT(*) FROM '.db_prefix().$this->parts_table.' p WHERE p.training_id=t.id) as enrolled_count')
            ->from(db_prefix() . $this->table . ' t');

        if (!empty($filters['status']))     $this->db->where('t.status', $filters['status']);
        if (!empty($filters['from_date']))  $this->db->where('t.start_date >=', $filters['from_date']);
        if (!empty($filters['to_date']))    $this->db->where('t.end_date <=', $filters['to_date']);

        return $this->db->order_by('t.start_date DESC')->get()->result();
    }

    public function add($data)
    {
        $record = [
            'title'       => $data['title'],
            'trainer'     => $data['trainer']     ?? null,
            'venue'       => $data['venue']        ?? null,
            'start_date'  => $data['start_date'],
            'end_date'    => $data['end_date'],
            'cost'        => (float) ($data['cost'] ?? 0),
            'capacity'    => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'scheduled',
            'created_by'  => get_staff_user_id(),
            'created_at'  => date('Y-m-d H:i:s'),
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
            'title'       => $data['title'],
            'trainer'     => $data['trainer']     ?? null,
            'venue'       => $data['venue']        ?? null,
            'start_date'  => $data['start_date'],
            'end_date'    => $data['end_date'],
            'cost'        => (float) ($data['cost'] ?? 0),
            'capacity'    => !empty($data['capacity']) ? (int) $data['capacity'] : null,
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'scheduled',
            'updated_at'  => date('Y-m-d H:i:s'),
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

    public function mark_completed($training_id, $employee_id, $date = null)
    {
        $this->db->where(['training_id' => $training_id, 'employee_id' => $employee_id])
            ->update(db_prefix() . $this->parts_table, [
                'completed'       => 1,
                'completion_date' => $date ?: date('Y-m-d'),
            ]);
        return ['success' => true, 'message' => 'Marked as completed.'];
    }
}
