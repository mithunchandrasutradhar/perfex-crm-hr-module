<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Helpdesk extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Helpdesk_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Departments_model');
        $this->load->model('hr_module/Email_templates_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_helpdesk') && staff_cant('view_own', 'hr_helpdesk')) access_denied('hr_helpdesk');
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'helpdesk/table'));
            return;
        }
        // The department filter only makes sense for someone who can see more
        // than their own tickets - table.php already forces the list back to
        // just the caller's own records otherwise.
        $can_view_all = is_admin() || staff_can('view', 'hr_helpdesk');
        $data['title']            = _l('hr_helpdesk_list');
        $data['show_dept_filter'] = $can_view_all;
        $data['departments']      = $can_view_all ? $this->Departments_model->get_active() : [];
        $data['employees']        = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/helpdesk/index', $data);
    }

    public function submit()
    {
        if (staff_cant('create', 'hr_helpdesk')) access_denied('hr_helpdesk');
        $own_only   = !staff_can('view', 'hr_helpdesk') && staff_can('view_own', 'hr_helpdesk');
        $own_emp_id = $own_only ? hr_get_own_employee_id() : 0;

        if ($this->input->post()) {
            $posted_emp_id = (int) $this->input->post('employee_id');
            $data = [
                'employee_id'  => $own_only ? $own_emp_id : $posted_emp_id,
                'is_anonymous' => (bool) $this->input->post('is_anonymous'),
                'subject'      => $this->input->post('subject', true),
                'category'     => $this->input->post('category', true),
                'priority'     => $this->input->post('priority'),
                'message'      => $this->input->post('message', true),
            ];
            $this->_handle_attachment($data, 'attachment');
            $result = $this->Helpdesk_model->submit($data);
            if ($result['success']) {
                if ($this->Hr_module_model->notifications_enabled('notify_helpdesk')) {
                    if ($data['is_anonymous']) {
                        $employee_name = 'Anonymous';
                    } else {
                        $this->load->model('hr_module/Employees_model');
                        $emp = $this->Employees_model->get($data['employee_id']);
                        $employee_name = $emp ? $emp->first_name . ' ' . $emp->last_name . ' (' . $emp->employee_code . ')' : 'Unknown';
                    }
                    $placeholders = [
                        '{employee_name}' => $employee_name,
                        '{subject}'       => $data['subject'],
                        '{category}'      => $data['category'] ?: '-',
                        '{priority}'      => ucfirst($data['priority'] ?: '-'),
                        '{message}'       => mb_strimwidth($data['message'] ?: '', 0, 300, '...'),
                    ];
                    $tpl  = $this->Email_templates_model->render('helpdesk_ticket_submitted', $placeholders);
                    $link = admin_url('hr_module/helpdesk/view/' . $result['id']);
                    $this->Hr_module_model->send_notification_email($tpl->subject, $tpl->body, $link);
                    $this->Hr_module_model->notify_by_permission(
                        'edit', 'hr_helpdesk',
                        'not_hr_helpdesk_submitted',
                        'hr_module/helpdesk/view/' . $result['id'],
                        [$data['subject']]
                    );
                }
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/helpdesk/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/helpdesk/submit'));
        }
        $data['title']      = _l('hr_helpdesk_add');
        $data['own_only']   = $own_only;
        $data['own_emp_id'] = $own_emp_id;
        if ($own_only) {
            $this->load->model('hr_module/Employees_model');
            $emp = $this->Employees_model->get($own_emp_id);
            $data['employees'] = $own_emp_id && $emp
                ? [$own_emp_id => $emp->first_name . ' ' . $emp->last_name]
                : [];
        } else {
            $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        }
        $this->load->view('hr_module/helpdesk/submit', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_helpdesk') && staff_cant('view_own', 'hr_helpdesk')) access_denied('hr_helpdesk');
        $ticket = $this->Helpdesk_model->get($id);
        if (!$ticket) show_404();
        if (!staff_can('view', 'hr_helpdesk') && staff_can('view_own', 'hr_helpdesk')) {
            if ($ticket->is_anonymous || (int) $ticket->employee_id !== hr_get_own_employee_id()) {
                access_denied('hr_helpdesk');
            }
        }
        $data['title']   = _l('hr_helpdesk_view');
        $data['ticket']  = $ticket;
        $data['replies'] = $this->Helpdesk_model->get_replies($id);
        $data['staff']   = $this->_get_staff_dropdown();
        $this->load->view('hr_module/helpdesk/view', $data);
    }

    // Same view() authorization as above, proxied so ticket/reply/internal-note
    // attachments aren't directly-fetchable static files. $type is 'ticket',
    // 'note', or 'reply' (with $ref_id as the reply id for 'reply').
    public function download($id, $type, $ref_id = null)
    {
        if (staff_cant('view', 'hr_helpdesk') && staff_cant('view_own', 'hr_helpdesk')) access_denied('hr_helpdesk');
        $ticket = $this->Helpdesk_model->get($id);
        if (!$ticket) show_404();
        if (!staff_can('view', 'hr_helpdesk') && staff_can('view_own', 'hr_helpdesk')) {
            if ($ticket->is_anonymous || (int) $ticket->employee_id !== hr_get_own_employee_id()) {
                access_denied('hr_helpdesk');
            }
        }

        if ($type === 'ticket') {
            $filename = $ticket->attachment;
        } elseif ($type === 'note') {
            $filename = $ticket->internal_note_attachment;
        } elseif ($type === 'reply' && $ref_id) {
            $reply = $this->db->where(['id' => (int) $ref_id, 'ticket_id' => $id])
                ->get(db_prefix() . 'hr_helpdesk_replies')->row();
            $filename = $reply ? $reply->attachment : null;
        } else {
            $filename = null;
        }
        if (empty($filename)) show_404();

        $this->load->helper('download');
        force_download(FCPATH . 'uploads/hr_module/helpdesk/' . basename($filename), null);
    }

    public function reply($id)
    {
        if (staff_cant('edit', 'hr_helpdesk')) access_denied('hr_helpdesk');
        if (!$this->input->post()) redirect(admin_url('hr_module/helpdesk/view/' . $id));
        $message = $this->input->post('message', true);
        if (!trim($message)) {
            set_alert('danger', 'Reply message cannot be empty.');
            redirect(admin_url('hr_module/helpdesk/view/' . $id));
        }
        $attachment = null;
        $data = [];
        $this->_handle_attachment($data, 'attachment');
        if (!empty($data['attachment'])) $attachment = $data['attachment'];
        $this->Helpdesk_model->reply($id, $message, get_staff_user_id(), $attachment);

        // Handle assign + status update
        if ($this->input->post('assigned_to') !== null) {
            $this->Helpdesk_model->assign($id, (int) $this->input->post('assigned_to'));
        }
        if ($this->input->post('status')) {
            $this->Helpdesk_model->set_status($id, $this->input->post('status'));
        }
        set_alert('success', 'Reply posted.');
        redirect(admin_url('hr_module/helpdesk/view/' . $id));
    }

    // Anonymous tickets have no one to reply back to, so instead of a reply
    // thread, staff keep a single internal note - status/assign/attachment
    // still work exactly like they do on the normal reply form.
    public function save_note($id)
    {
        if (staff_cant('edit', 'hr_helpdesk')) access_denied('hr_helpdesk');
        if (!$this->input->post()) redirect(admin_url('hr_module/helpdesk/view/' . $id));

        $note = $this->input->post('note', true);
        $data = [];
        $this->_handle_attachment($data, 'attachment');
        $this->Helpdesk_model->save_note($id, $note, $data['attachment'] ?? null);

        if ($this->input->post('assigned_to') !== null) {
            $this->Helpdesk_model->assign($id, (int) $this->input->post('assigned_to'));
        }
        if ($this->input->post('status')) {
            $this->Helpdesk_model->set_status($id, $this->input->post('status'));
        }
        set_alert('success', _l('hr_helpdesk_note_saved'));
        redirect(admin_url('hr_module/helpdesk/view/' . $id));
    }

    public function close($id)
    {
        if (staff_cant('edit', 'hr_helpdesk')) access_denied('hr_helpdesk');
        $ticket = $this->Helpdesk_model->get($id);
        if (!$ticket) show_404();
        $new_status = $ticket->status === 'closed' ? 'open' : 'closed';
        $this->Helpdesk_model->set_status($id, $new_status);
        $msg = $new_status === 'closed' ? 'Ticket closed.' : 'Ticket reopened.';
        set_alert('success', $msg);
        redirect(admin_url('hr_module/helpdesk/view/' . $id));
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_helpdesk')) access_denied('hr_helpdesk');
        $this->Helpdesk_model->delete($id);
        set_alert('success', _l('hr_deleted_successfully'));
        redirect(admin_url('hr_module/helpdesk'));
    }

    private function _handle_attachment(&$data, $field)
    {
        if (empty($_FILES[$field]['name'])) return;
        $path = FCPATH . 'uploads/hr_module/helpdesk/';
        if (!is_dir($path)) mkdir($path, 0755, true);
        hr_lock_upload_dir($path);
        $this->load->library('upload', [
            'upload_path'   => $path,
            'allowed_types' => 'pdf|doc|docx|jpg|jpeg|png|txt',
            'max_size'      => 5120,
            'encrypt_name'  => true,
        ]);
        if ($this->upload->do_upload($field)) {
            $data['attachment'] = $this->upload->data('file_name');
        }
    }

    private function _get_staff_dropdown()
    {
        $rows = $this->db->select('staffid, CONCAT(firstname," ",lastname) as name')
            ->where('active', 1)->get(db_prefix() . 'staff')->result();
        $out = ['' => '-- Unassigned --'];
        foreach ($rows as $r) $out[$r->staffid] = $r->name;
        return $out;
    }
}
