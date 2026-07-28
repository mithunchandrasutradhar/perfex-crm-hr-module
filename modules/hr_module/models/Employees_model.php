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
        $this->db->select('e.*,
                COALESCE(s.firstname, e.first_name) as first_name,
                COALESCE(s.lastname, e.last_name)   as last_name,
                COALESCE(s.email, e.email)           as email,
                COALESCE(s.phonenumber, e.phone)     as phone,
                s.profile_image as staff_photo,
                s.active as staff_active,
                d.name as department_name, ds.name as designation_name,
                CONCAT(s.firstname," ",s.lastname) as staff_name', false)
            ->from($this->table . ' e')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds','ds.id = e.designation_id', 'left')
            ->join(db_prefix() . 'staff s',           's.staffid = e.staff_id',   'left');

        if ($id) {
            $this->db->where('e.id', $id);
            return $this->db->get()->row();
        }
        if (!empty($where)) {
            $this->db->where($where);
        }
        $this->db->order_by('s.firstname, e.first_name', 'ASC');
        return $this->db->get()->result();
    }

    public function get_all_for_table($filters = [])
    {
        $this->db->select('e.id, e.employee_code, e.joining_date, e.basic_salary, e.status, e.photo, e.staff_id,
                COALESCE(s.firstname, e.first_name) as first_name,
                COALESCE(s.lastname,  e.last_name)  as last_name,
                COALESCE(s.email,     e.email)       as email,
                s.profile_image as staff_photo,
                d.name as department_name, ds.name as designation_name', false)
            ->from($this->table . ' e')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds','ds.id = e.designation_id', 'left')
            ->join(db_prefix() . 'staff s',           's.staffid = e.staff_id',   'left');

        if (!empty($filters['department_id'])) {
            $this->db->where('e.department_id', $filters['department_id']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $this->db->where('e.status', $filters['status']);
        }
        $this->db->order_by('s.firstname, e.first_name', 'ASC');
        return $this->db->get()->result();
    }

    public function add($data)
    {
        // Always sync identity fields from the linked staff member
        if (!empty($data['staff_id'])) {
            $staff = $this->db->select('firstname, lastname, email, phonenumber')
                ->where('staffid', $data['staff_id'])
                ->get(db_prefix() . 'staff')->row();
            if ($staff) {
                $data['first_name'] = $staff->firstname;
                $data['last_name']  = $staff->lastname;
                $data['email']      = $staff->email;
                $data['phone']      = $staff->phonenumber ?? '';
            }
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = get_staff_user_id();
        $this->db->insert($this->table, $data);
        $id = $this->db->insert_id();
        if ($id) {
            log_activity('HR Employee Profile Added [ID: ' . $id . ', Code: ' . ($data['employee_code'] ?? '') . ']');
        }
        return $id;
    }

    public function update($data, $id)
    {
        // Determine staff_id to sync from (submitted or existing)
        $staff_id = !empty($data['staff_id']) ? $data['staff_id'] : null;
        if (!$staff_id) {
            $current  = $this->db->select('staff_id')->where('id', $id)->get($this->table)->row();
            $staff_id = $current ? $current->staff_id : null;
        }
        if ($staff_id) {
            $staff = $this->db->select('firstname, lastname, email, phonenumber')
                ->where('staffid', $staff_id)
                ->get(db_prefix() . 'staff')->row();
            if ($staff) {
                $data['first_name'] = $staff->firstname;
                $data['last_name']  = $staff->lastname;
                $data['email']      = $staff->email;
                $data['phone']      = $staff->phonenumber ?? '';
            }
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id)->update($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('HR Employee Profile Updated [ID: ' . $id . ']');
            return true;
        }
        return $this->db->where('id', $id)->count_all_results($this->table) > 0;
    }

    // Deactivates (never hard-deletes) an employee profile - attendance, leave,
    // payroll, loan, and other historical records all reference this row by id,
    // so removing it would orphan that history. Setting status = 0 keeps the
    // record (and the log) intact while dropping them out of active lists.
    public function delete($id)
    {
        $employee = $this->get($id);
        if (!$employee) return false;
        $this->db->where('id', $id)->update($this->table, ['status' => 0]);
        if ($this->db->affected_rows() > 0) {
            log_activity('HR Employee Deactivated [ID: ' . $id . ']');
            return true;
        }
        return false;
    }

    public function get_next_code($prefix = 'EMP')
    {
        $row  = $this->db->select_max('id')->get($this->table)->row();
        $next = $row && $row->id ? ((int) $row->id + 1) : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function code_exists($code, $exclude_id = null)
    {
        $this->db->where('employee_code', $code);
        if ($exclude_id) $this->db->where('id !=', $exclude_id);
        return $this->db->count_all_results($this->table) > 0;
    }

    public function get_all($filters = [])
    {
        $where = [];
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active')   $where['e.status'] = 1;
            elseif ($filters['status'] === 'inactive') $where['e.status'] = 0;
            else $where['e.status'] = (int) $filters['status'];
        }
        return $this->get(null, $where);
    }

    public function get_by_staff_id($staff_id)
    {
        return $this->db->where('staff_id', $staff_id)->get($this->table)->row();
    }

    // Staff ids of every active hr_module employee mapped to a real staff account
    // - the same audience send_leave_announcement()/send_policy_announcement()
    // already email (public case), just as staff ids instead of addresses, for
    // targeting a central (bell-icon) notification to the same people.
    public function get_active_staff_ids()
    {
        return array_map('intval', array_column(
            $this->db->select('staff_id')
                ->where('status', 1)
                ->where('staff_id IS NOT NULL')
                ->where('staff_id !=', 0)
                ->get($this->table)->result_array(),
            'staff_id'
        ));
    }

    // Same as get_active_staff_ids(), scoped to employees in any of the given
    // departments - for a private policy's central-notification audience.
    public function get_active_staff_ids_for_departments($department_ids)
    {
        $department_ids = array_values(array_filter(array_map('intval', (array) $department_ids)));
        if (empty($department_ids)) {
            return [];
        }
        return array_map('intval', array_column(
            $this->db->select('staff_id')
                ->where('status', 1)
                ->where('staff_id IS NOT NULL')
                ->where('staff_id !=', 0)
                ->where_in('department_id', $department_ids)
                ->get($this->table)->result_array(),
            'staff_id'
        ));
    }

    // Returns all active staff who do NOT yet have an HR profile
    public function get_unlinked_staff()
    {
        $linked = $this->db->select('staff_id')
            ->where('staff_id IS NOT NULL')
            ->get($this->table)->result_array();
        $linked_ids = array_column($linked, 'staff_id');

        $q = $this->db->select('staffid, firstname, lastname, email, phonenumber, profile_image')
            ->where('active', 1)
            ->order_by('firstname', 'ASC');
        if ($linked_ids) $q->where_not_in('staffid', $linked_ids);
        return $q->get(db_prefix() . 'staff')->result();
    }

    public function handle_photo_upload($old_photo = null)
    {
        $upload_path = FCPATH . 'uploads/hr_module/employees/';
        if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);
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
        if ($old_photo && file_exists($upload_path . $old_photo)) unlink($upload_path . $old_photo);
        return ['success' => true, 'filename' => $file['file_name']];
    }
}
