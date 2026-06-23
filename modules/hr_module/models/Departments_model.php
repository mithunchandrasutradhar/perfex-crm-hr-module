<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Read-only wrapper around Perfex's core tbldepartments table.
 * HR departments are no longer managed in a separate table — the CRM
 * support-ticket department list is shared across all modules.
 */
class Departments_model extends App_Model
{
    private $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'departments';
    }

    public function get($id = null)
    {
        if ($id) {
            $this->db->select('departmentid as id, name')->where('departmentid', $id);
            return $this->db->get($this->table)->row();
        }
        $this->db->select('departmentid as id, name')->order_by('name', 'ASC');
        return $this->db->get($this->table)->result();
    }

    // Returns all departments; alias departmentid→id for backward compat with form selects.
    public function get_active()
    {
        $this->db->select('departmentid as id, name')->order_by('name', 'ASC');
        return $this->db->get($this->table)->result();
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
