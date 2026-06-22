<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Departments_model extends App_Model
{
    private $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'hr_departments';
    }

    public function get($id = null)
    {
        if ($id) {
            $this->db->where('id', $id);
            return $this->db->get($this->table)->row();
        }
        $this->db->order_by('name', 'ASC');
        return $this->db->get($this->table)->result();
    }

    public function get_active()
    {
        $this->db->where('status', 1);
        $this->db->order_by('name', 'ASC');
        return $this->db->get($this->table)->result();
    }

    public function add($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        $id = $this->db->insert_id();
        if ($id) {
            log_activity('HR Department Added [ID: ' . $id . ', Name: ' . $data['name'] . ']');
        }
        return $id;
    }

    public function update($data, $id)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('HR Department Updated [ID: ' . $id . ']');
            return true;
        }
        return false;
    }

    public function delete($id)
    {
        // Check if any employees belong to this department
        $this->db->where('department_id', $id);
        $count = $this->db->count_all_results(db_prefix() . 'hr_employees');
        if ($count > 0) {
            return ['success' => false, 'message' => 'Cannot delete department with assigned employees'];
        }
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        if ($this->db->affected_rows() > 0) {
            log_activity('HR Department Deleted [ID: ' . $id . ']');
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Record not found'];
    }

    public function total_employees($dept_id)
    {
        $this->db->where('department_id', $dept_id);
        return $this->db->count_all_results(db_prefix() . 'hr_employees');
    }

    public function get_head_name($staff_id)
    {
        if (!$staff_id) return '-';
        $this->db->select('CONCAT(firstname, " ", lastname) as fullname');
        $this->db->where('staffid', $staff_id);
        $row = $this->db->get(db_prefix() . 'staff')->row();
        return $row ? $row->fullname : '-';
    }
}
