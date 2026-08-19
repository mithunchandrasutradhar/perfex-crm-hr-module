<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Hr_contracts_model extends App_Model
{
    private $table = 'hr_contracts';

    public function get($id)
    {
        return $this->db
            ->select('c.*, e.first_name, e.last_name, e.employee_code,
                      d.name as department_name, ds.name as designation_name,
                      CONCAT(s.firstname," ",s.lastname) as created_by_name', false)
            ->from(db_prefix() . $this->table . ' c')
            ->join(db_prefix() . 'hr_employees e',    'e.id = c.employee_id',      'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'hr_designations ds','ds.id = e.designation_id',  'left')
            ->join(db_prefix() . 'staff s',           's.staffid = c.created_by',  'left')
            ->where('c.id', $id)
            ->get()->row();
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('c.id, c.title, c.contract_type, c.start_date, c.end_date,
                           c.value, c.status, c.signed, c.signed_date, c.created_at,
                           e.first_name, e.last_name, e.employee_code, d.name as department_name')
            ->from(db_prefix() . $this->table . ' c')
            ->join(db_prefix() . 'hr_employees e', 'e.id = c.employee_id', 'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left');

        if (!empty($filters['employee_id']))   $this->db->where('c.employee_id', $filters['employee_id']);
        if (!empty($filters['department_id'])) $this->db->where('e.department_id', $filters['department_id']);
        if (!empty($filters['status']))        $this->db->where('c.status', $filters['status']);
        if (!empty($filters['contract_type'])) $this->db->where('c.contract_type', $filters['contract_type']);
        if (!empty($filters['expiring_soon'])) {
            // contracts expiring within 30 days
            $this->db->where('c.end_date IS NOT NULL');
            $this->db->where('c.end_date >=', date('Y-m-d'));
            $this->db->where('c.end_date <=', date('Y-m-d', strtotime('+30 days')));
            $this->db->where('c.status', 'active');
        }

        return $this->db->order_by('c.created_at DESC')->get()->result();
    }

    public function add($data)
    {
        $record = [
            'employee_id'   => (int) $data['employee_id'],
            'title'         => $data['title'],
            'contract_type' => $data['contract_type'] ?? 'permanent',
            'start_date'    => $data['start_date'],
            'end_date'      => !empty($data['end_date']) ? $data['end_date'] : null,
            'value'         => !empty($data['value']) ? (float) $data['value'] : null,
            'content'       => $data['content'] ?? null,
            'status'        => $data['status'] ?? 'active',
            'signed'        => isset($data['signed']) && $data['signed'] ? 1 : 0,
            'signed_date'   => !empty($data['signed_date']) ? $data['signed_date'] : null,
            'notes'         => $data['notes'] ?? null,
            'created_by'    => get_staff_user_id(),
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['attachment'])) $record['attachment'] = $data['attachment'];
        $this->db->insert(db_prefix() . $this->table, $record);
        $id = $this->db->insert_id();
        if ($id) {
            log_activity('HR Contract Created [ID: ' . $id . ', Employee ID: ' . $record['employee_id'] . ']');
        }
        return $id ? ['success' => true, 'id' => $id, 'message' => _l('hr_contract_added')]
                   : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function update($data, $id)
    {
        $update = [
            'employee_id'   => (int) $data['employee_id'],
            'title'         => $data['title'],
            'contract_type' => $data['contract_type'] ?? 'permanent',
            'start_date'    => $data['start_date'],
            'end_date'      => !empty($data['end_date']) ? $data['end_date'] : null,
            'value'         => !empty($data['value']) ? (float) $data['value'] : null,
            'content'       => $data['content'] ?? null,
            'status'        => $data['status'] ?? 'active',
            'signed'        => isset($data['signed']) && $data['signed'] ? 1 : 0,
            'signed_date'   => !empty($data['signed_date']) ? $data['signed_date'] : null,
            'notes'         => $data['notes'] ?? null,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['attachment'])) $update['attachment'] = $data['attachment'];
        $this->db->where('id', $id)->update(db_prefix() . $this->table, $update);
        log_activity('HR Contract Updated [ID: ' . $id . ']');
        return ['success' => true, 'message' => _l('hr_contract_updated')];
    }

    public function mark_signed($id, $date = null)
    {
        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'signed'      => 1,
            'signed_date' => $date ?: date('Y-m-d'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Contract Marked Signed [ID: ' . $id . ']');
        return ['success' => true, 'message' => 'Contract marked as signed.'];
    }

    public function set_status($id, $status)
    {
        $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Contract Status Changed [ID: ' . $id . ', Status: ' . $status . ']');
        return ['success' => true];
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete(db_prefix() . $this->table);
        log_activity('HR Contract Deleted [ID: ' . $id . ']');
        return ['success' => true, 'message' => _l('hr_contract_deleted')];
    }

    // Called by cron — auto-expire contracts past end_date
    public function auto_expire()
    {
        $this->db->where('status', 'active')
            ->where('end_date IS NOT NULL')
            ->where('end_date <', date('Y-m-d'))
            ->update(db_prefix() . $this->table, [
                'status'     => 'expired',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        $expired = $this->db->affected_rows();
        if ($expired > 0) {
            log_activity('HR Contracts Auto-Expired [Count: ' . $expired . ']');
        }
    }

    public function get_expiring_soon($days = 30)
    {
        return $this->db
            ->select('c.id, c.title, c.end_date, e.first_name, e.last_name, e.email')
            ->from(db_prefix() . $this->table . ' c')
            ->join(db_prefix() . 'hr_employees e', 'e.id = c.employee_id', 'left')
            ->where('c.status', 'active')
            ->where('c.end_date IS NOT NULL')
            ->where('c.end_date >=', date('Y-m-d'))
            ->where('c.end_date <=', date('Y-m-d', strtotime("+$days days")))
            ->get()->result();
    }
}
