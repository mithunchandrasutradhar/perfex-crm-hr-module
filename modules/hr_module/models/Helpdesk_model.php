<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Helpdesk_model extends App_Model
{
    private $table        = 'hr_helpdesk';
    private $reply_table  = 'hr_helpdesk_replies';

    public function get($id)
    {
        return $this->db
            ->select('t.*, e.first_name, e.last_name, e.employee_code,
                      d.name as department_name,
                      CONCAT(s.firstname," ",s.lastname) as assigned_name', false)
            ->from(db_prefix() . $this->table . ' t')
            ->join(db_prefix() . 'hr_employees e',   'e.id = t.employee_id',      'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'staff s',          's.staffid = t.assigned_to', 'left')
            ->where('t.id', $id)
            ->get()->row();
    }

    public function get_for_table($filters = [])
    {
        $this->db->select('t.id, t.subject, t.category, t.priority, t.status, t.is_anonymous, t.created_at, t.updated_at,
                           e.first_name, e.last_name, e.employee_code, d.name as department_name,
                           CONCAT(s.firstname," ",s.lastname) as assigned_name,
                           (SELECT COUNT(*) FROM '.db_prefix().$this->reply_table.' r WHERE r.ticket_id=t.id) as reply_count', false)
            ->from(db_prefix() . $this->table . ' t')
            ->join(db_prefix() . 'hr_employees e',   'e.id = t.employee_id',      'left')
            ->join(db_prefix() . 'departments d', 'd.departmentid = e.department_id', 'left')
            ->join(db_prefix() . 'staff s',          's.staffid = t.assigned_to', 'left');

        if (!empty($filters['employee_id']))   $this->db->where('t.employee_id', $filters['employee_id']);
        if (!empty($filters['department_id'])) $this->db->where('e.department_id', $filters['department_id']);
        if (!empty($filters['status']))        $this->db->where('t.status', $filters['status']);
        if (!empty($filters['priority']))      $this->db->where('t.priority', $filters['priority']);
        // Deliberately not searching by employee name here - some tickets are
        // is_anonymous, and matching on the submitter's name would let a search
        // reveal who filed an anonymous ticket even though the display column
        // hides it.
        if (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('t.subject', $filters['search'])
                ->or_like('t.category', $filters['search'])
                ->group_end();
        }

        return $this->db->order_by('t.created_at DESC')->get()->result();
    }

    public function get_replies($ticket_id)
    {
        return $this->db
            ->select('r.*, CONCAT(s.firstname," ",s.lastname) as staff_name, s.profile_image', false)
            ->from(db_prefix() . $this->reply_table . ' r')
            ->join(db_prefix() . 'staff s', 's.staffid = r.staff_id', 'left')
            ->where('r.ticket_id', $ticket_id)
            ->order_by('r.created_at ASC')
            ->get()->result();
    }

    public function submit($data)
    {
        $is_anonymous = !empty($data['is_anonymous']);
        $record = [
            'employee_id'  => $is_anonymous ? null : ((int) $data['employee_id'] ?: null),
            'is_anonymous' => $is_anonymous ? 1 : 0,
            'subject'      => $data['subject'],
            'category'     => $data['category']   ?? null,
            'priority'     => $data['priority']   ?? 'medium',
            'message'      => $data['message'],
            'status'       => 'open',
            'created_at'   => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['attachment'])) $record['attachment'] = $data['attachment'];
        $this->db->insert(db_prefix() . $this->table, $record);
        $id = $this->db->insert_id();
        if ($id) {
            log_activity('HR Helpdesk Ticket Submitted [ID: ' . $id . ']');
        }
        return $id ? ['success' => true, 'id' => $id, 'message' => _l('hr_helpdesk_added')]
                   : ['success' => false, 'message' => _l('hr_error_saving')];
    }

    public function reply($ticket_id, $message, $staff_id, $attachment = null)
    {
        $this->db->insert(db_prefix() . $this->reply_table, [
            'ticket_id'  => $ticket_id,
            'staff_id'   => $staff_id,
            'message'    => $message,
            'attachment' => $attachment,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        // Auto-move to in_progress if still open
        $ticket = $this->db->select('status')->where('id', $ticket_id)
            ->get(db_prefix() . $this->table)->row();
        if ($ticket && $ticket->status === 'open') {
            $this->db->where('id', $ticket_id)->update(db_prefix() . $this->table, [
                'status'     => 'in_progress',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        log_activity('HR Helpdesk Ticket Reply Posted [ID: ' . $ticket_id . ']');
        return ['success' => true, 'message' => 'Reply posted.'];
    }

    // Anonymous tickets have no one to reply back to, so instead of a growing
    // reply thread, staff just keep a single internal note on the ticket itself.
    public function save_note($ticket_id, $note, $attachment = null)
    {
        $update = [
            'internal_note' => $note,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        if ($attachment) $update['internal_note_attachment'] = $attachment;

        $ticket = $this->db->select('status')->where('id', $ticket_id)
            ->get(db_prefix() . $this->table)->row();
        if ($ticket && $ticket->status === 'open') $update['status'] = 'in_progress';

        $this->db->where('id', $ticket_id)->update(db_prefix() . $this->table, $update);
        log_activity('HR Helpdesk Ticket Internal Note Saved [ID: ' . $ticket_id . ']');
        return ['success' => true, 'message' => _l('hr_helpdesk_note_saved')];
    }

    public function assign($ticket_id, $staff_id)
    {
        $this->db->where('id', $ticket_id)->update(db_prefix() . $this->table, [
            'assigned_to' => $staff_id ?: null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Helpdesk Ticket Assigned [ID: ' . $ticket_id . ', Staff ID: ' . $staff_id . ']');
        return ['success' => true];
    }

    public function set_status($ticket_id, $status)
    {
        $this->db->where('id', $ticket_id)->update(db_prefix() . $this->table, [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Helpdesk Ticket Status Changed [ID: ' . $ticket_id . ', Status: ' . $status . ']');
        return ['success' => true];
    }

    public function delete($ticket_id)
    {
        $this->db->where('ticket_id', $ticket_id)->delete(db_prefix() . $this->reply_table);
        $this->db->where('id', $ticket_id)->delete(db_prefix() . $this->table);
        log_activity('HR Helpdesk Ticket Deleted [ID: ' . $ticket_id . ']');
        return ['success' => true];
    }
}
