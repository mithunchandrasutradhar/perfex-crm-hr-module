<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Employees_model extends App_Model
{
    private $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'hr_employees';
    }

    public function get($id = null, $where = [])
    {
        $this->db->select('e.*, d.name as department_name, ds.name as designation_name,
            CONCAT(s.firstname," ",s.lastname) as staff_name')
            ->from($this->table . ' e')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds', 'ds.id = e.designation_id', 'left')
            ->join(db_prefix() . 'staff s', 's.staffid = e.staff_id', 'left');

        if ($id) {
            $this->db->where('e.id', $id);
            return $this->db->get()->row();
        }
        if (!empty($where)) {
            $this->db->where($where);
        }
        $this->db->order_by('e.first_name', 'ASC');
        return $this->db->get()->result();
    }

    public function get_all_for_table($filters = [])
    {
        $this->db->select('e.id, e.employee_code, e.first_name, e.last_name, e.email, e.phone,
            e.joining_date, e.basic_salary, e.status, e.photo,
            d.name as department_name, ds.name as designation_name')
            ->from($this->table . ' e')
            ->join(db_prefix() . 'hr_departments d', 'd.id = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds', 'ds.id = e.designation_id', 'left');

        if (!empty($filters['department_id'])) {
            $this->db->where('e.department_id', $filters['department_id']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $this->db->where('e.status', $filters['status']);
        }
        $this->db->order_by('e.first_name', 'ASC');
        return $this->db->get()->result();
    }

    public function add($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = get_staff_user_id();
        $this->db->insert($this->table, $data);
        $id = $this->db->insert_id();
        if ($id) {
            log_activity('HR Employee Added [ID: ' . $id . ', Code: ' . $data['employee_code'] . ']');
        }
        return $id;
    }

    public function update($data, $id)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('HR Employee Updated [ID: ' . $id . ']');
            return true;
        }
        // Even 0 affected rows = success if record exists
        $this->db->where('id', $id);
        return $this->db->count_all_results($this->table) > 0;
    }

    public function delete($id)
    {
        $employee = $this->get($id);
        if (!$employee) {
            return false;
        }
        // Delete photo if exists
        if ($employee->photo && file_exists(FCPATH . 'uploads/hr_module/employees/' . $employee->photo)) {
            unlink(FCPATH . 'uploads/hr_module/employees/' . $employee->photo);
        }
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        if ($this->db->affected_rows() > 0) {
            log_activity('HR Employee Deleted [ID: ' . $id . ']');
            return true;
        }
        return false;
    }

    public function get_next_code($prefix = 'EMP')
    {
        $this->db->select_max('id');
        $row = $this->db->get($this->table)->row();
        $next = $row && $row->id ? ((int) $row->id + 1) : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function code_exists($code, $exclude_id = null)
    {
        $this->db->where('employee_code', $code);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results($this->table) > 0;
    }

    public function get_by_staff_id($staff_id)
    {
        $this->db->where('staff_id', $staff_id);
        return $this->db->get($this->table)->row();
    }

    public function handle_photo_upload($old_photo = null)
    {
        $upload_path = FCPATH . 'uploads/hr_module/employees/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }
        $this->load->library('upload', [
            'upload_path'   => $upload_path,
            'allowed_types' => 'jpg|jpeg|png|gif',
            'max_size'      => 2048,
            'encrypt_name'  => true,
        ]);
        if (!$this->upload->do_upload('photo')) {
            return ['success' => false, 'error' => $this->upload->display_errors()];
        }
        $file = $this->upload->data();
        // Remove old photo
        if ($old_photo && file_exists($upload_path . $old_photo)) {
            unlink($upload_path . $old_photo);
        }
        return ['success' => true, 'filename' => $file['file_name']];
    }
}
