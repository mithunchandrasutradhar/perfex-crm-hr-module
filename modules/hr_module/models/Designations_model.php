<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Designations_model extends App_Model
{
    private $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'hr_designations';
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
            log_activity('HR Designation Added [ID: ' . $id . ', Name: ' . $data['name'] . ']');
        }
        return $id;
    }

    public function update($data, $id)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('HR Designation Updated [ID: ' . $id . ']');
            return true;
        }
        return false;
    }

    public function delete($id)
    {
        $this->db->where('designation_id', $id);
        $count = $this->db->count_all_results(db_prefix() . 'hr_employees');
        if ($count > 0) {
            return ['success' => false, 'message' => 'Cannot delete designation with assigned employees'];
        }
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        if ($this->db->affected_rows() > 0) {
            log_activity('HR Designation Deleted [ID: ' . $id . ']');
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Record not found'];
    }

    public function total_employees($desig_id)
    {
        $this->db->where('designation_id', $desig_id);
        return $this->db->count_all_results(db_prefix() . 'hr_employees');
    }
}
