<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Branches_model extends App_Model
{
    private $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'hr_branches';
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
            log_activity('HR Branch Added [ID: ' . $id . ', Name: ' . $data['name'] . ']');
        }
        return $id;
    }

    public function update($data, $id)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);
        log_activity('HR Branch Updated [ID: ' . $id . ']');
        return true;
    }

    public function delete($id)
    {
        $this->db->where('branch_id', $id);
        $count = $this->db->count_all_results(db_prefix() . 'hr_employees');
        if ($count > 0) {
            return ['success' => false, 'message' => 'Cannot delete branch with assigned employees'];
        }
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        if ($this->db->affected_rows() > 0) {
            log_activity('HR Branch Deleted [ID: ' . $id . ']');
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Record not found'];
    }

    public function total_employees($branch_id)
    {
        $this->db->where('branch_id', $branch_id);
        return $this->db->count_all_results(db_prefix() . 'hr_employees');
    }
}
