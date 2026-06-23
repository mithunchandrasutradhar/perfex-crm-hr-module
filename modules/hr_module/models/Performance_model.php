<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Performance_model extends App_Model
{
    private $table = 'hr_performance_reviews';

    public function get($id)
    {
        return $this->db
            ->select('r.*, e.first_name, e.last_name, e.employee_code,
                      d.name as department_name, ds.name as designation_name,
                      CONCAT(s.firstname," ",s.lastname) as reviewer_name,
                      CONCAT(cb.firstname," ",cb.lastname) as created_by_name', false)
            ->from(db_prefix() . $this->table . ' r')
            ->join(db_prefix() . 'hr_employees e',    'e.id = r.employee_id',       'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds','ds.id = e.designation_id',   'left')
            ->join(db_prefix() . 'staff s',           's.staffid = r.reviewer_id',  'left')
            ->join(db_prefix() . 'staff cb',          'cb.staffid = r.created_by',  'left')
            ->where('r.id', $id)
            ->get()->row();
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('r.id, r.review_period_from, r.review_period_to,
                           r.final_score, r.rating, r.status, r.created_at,
                           e.first_name, e.last_name, e.employee_code,
                           d.name as department_name,
                           CONCAT(s.firstname," ",s.lastname) as reviewer_name', false)
            ->from(db_prefix() . $this->table . ' r')
            ->join(db_prefix() . 'hr_employees e', 'e.id = r.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'staff s', 's.staffid = r.reviewer_id', 'left');

        if (!empty($filters['employee_id']))   $this->db->where('r.employee_id', $filters['employee_id']);
        if (!empty($filters['department_id'])) $this->db->where('e.department_id', $filters['department_id']);
        if (!empty($filters['status']))        $this->db->where('r.status', $filters['status']);
        if (!empty($filters['year']))          $this->db->where('YEAR(r.review_period_from)', $filters['year']);

        return $this->db->order_by('r.created_at DESC')->get()->result();
    }

    public function add($data)
    {
        $record = [
            'employee_id'        => (int) $data['employee_id'],
            'reviewer_id'        => (int) $data['reviewer_id'],
            'review_period_from' => $data['review_period_from'],
            'review_period_to'   => $data['review_period_to'],
            'criteria'           => $data['criteria'] ?? null,
            'status'             => 'pending',
            'notes'              => $data['notes'] ?? null,
            'created_by'         => get_staff_user_id(),
            'created_at'         => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . $this->table, $record);
        $id = $this->db->insert_id();
        return $id ? ['success' => true, 'id' => $id, 'message' => _l('hr_performance_added')]
                   : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function update($data, $id)
    {
        $row = $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
        if (!$row) return ['success' => false, 'message' => 'Review not found.'];

        $update = ['updated_at' => date('Y-m-d H:i:s')];

        // Admin/HR can always update base fields
        if (isset($data['employee_id']))        $update['employee_id']        = (int) $data['employee_id'];
        if (isset($data['reviewer_id']))        $update['reviewer_id']        = (int) $data['reviewer_id'];
        if (isset($data['review_period_from'])) $update['review_period_from'] = $data['review_period_from'];
        if (isset($data['review_period_to']))   $update['review_period_to']   = $data['review_period_to'];
        if (isset($data['criteria']))           $update['criteria']           = $data['criteria'];
        if (isset($data['notes']))              $update['notes']              = $data['notes'];

        // Self assessment (employee/pending stage)
        if (!empty($data['self_assessment'])) {
            $update['self_assessment'] = $data['self_assessment'];
            if ($row->status === 'pending') $update['status'] = 'in_progress';
        }

        // Manager evaluation stage
        if (!empty($data['manager_review'])) {
            $update['manager_review'] = $data['manager_review'];
        }
        if (isset($data['final_score']) && $data['final_score'] !== '') {
            $score = (float) $data['final_score'];
            $update['final_score'] = $score;
            $update['rating']      = $this->_score_to_rating($score);
            $update['status']      = 'completed';
        }

        // Manual status override
        if (!empty($data['status'])) $update['status'] = $data['status'];

        $this->db->where('id', $id)->update(db_prefix() . $this->table, $update);
        return ['success' => true, 'message' => _l('hr_performance_updated')];
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete(db_prefix() . $this->table);
        return ['success' => true, 'message' => _l('hr_performance_deleted')];
    }

    private function _score_to_rating($score)
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Very Good';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Average';
        return 'Poor';
    }
}
