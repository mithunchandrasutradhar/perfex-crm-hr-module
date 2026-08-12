<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Policies extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Policies_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Employees_model');
        $this->load->model('hr_module/Departments_model');
        $this->load->model('hr_module/Email_templates_model');
    }

    // Global managers (is_admin, or granted the global 'view' capability) may manage
    // any department's policy plus public ones; a department-scoped manager (granted
    // only 'view_own' + create/edit) may only manage their own department's policy.
    private function _is_global_manager()
    {
        return is_admin() || staff_can('view', 'hr_policies');
    }

    private function _own_department_id()
    {
        $emp_id = hr_get_own_employee_id();
        if (!$emp_id) return null;
        $emp = $this->Employees_model->get($emp_id);
        return $emp ? $emp->department_id : null;
    }

    // $department_ids is the policy's full set of target departments (a policy
    // can now target more than one).
    private function _can_manage_departments($department_ids)
    {
        if ($this->_is_global_manager()) return true;
        if (staff_cant('create', 'hr_policies') && staff_cant('edit', 'hr_policies')) return false;
        $own_dept = $this->_own_department_id();
        return $own_dept && in_array((int) $own_dept, array_map('intval', (array) $department_ids), true);
    }

    private function _can_view_policy($policy)
    {
        if ($this->_is_global_manager()) return true;
        if ($policy->status !== 'published') return false;
        if ($policy->type === 'public') return true;
        $own_dept = $this->_own_department_id();
        return $own_dept && in_array((int) $own_dept, $policy->department_id_list, true);
    }

    // Approving/rejecting policies is restricted to whichever staff members are
    // chosen in Settings (policy_approver_ids) - not every admin, per explicit
    // requirement. Falls back to any is_admin() while that setting is left
    // unconfigured, so the feature isn't locked out entirely before it's set up.
    private function _is_policy_approver()
    {
        $approvers = $this->Hr_module_model->get_policy_approvers();
        if (empty($approvers)) {
            return is_admin();
        }
        $my_id = (int) get_staff_user_id();
        foreach ($approvers as $approver) {
            if ((int) $approver->staffid === $my_id) return true;
        }
        return false;
    }

    public function index()
    {
        if (staff_cant('view', 'hr_policies') && staff_cant('view_own', 'hr_policies') && !is_admin()) {
            access_denied('hr_policies');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'policies/table'));
            return;
        }

        $data['title']           = 'Policies';
        $data['is_global']       = $this->_is_global_manager();
        $data['own_department']  = $this->_own_department_id();
        $data['can_manage_own']  = $data['is_global'] || staff_can('create', 'hr_policies') || staff_can('edit', 'hr_policies');
        $data['departments']     = $this->Departments_model->get_active();

        if ($data['is_global']) {
            $data['pending']  = $this->Policies_model->get_pending();
            $data['pending_revisions'] = $this->Policies_model->get_pending_revisions();
        } else {
            $data['pending']  = [];
            $data['pending_revisions'] = [];
        }

        $this->load->view('hr_module/policies/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_policies') && !is_admin()) {
            access_denied('hr_policies');
        }
        $is_global = $this->_is_global_manager();
        $own_dept  = $this->_own_department_id();

        if ($this->input->post()) {
            $type = $is_global && $this->input->post('type') === 'public' ? 'public' : 'private';
            $department_ids = [];
            if ($type === 'private') {
                $department_ids = $is_global
                    ? array_values(array_filter(array_map('intval', (array) $this->input->post('department_ids'))))
                    : ($own_dept ? [(int) $own_dept] : []);
            }

            if ($type === 'private' && empty($department_ids)) {
                set_alert('danger', 'At least one department is required for a private policy.');
                redirect(admin_url('hr_module/policies/add'));
            }
            if (!$this->_can_manage_departments($department_ids)) {
                access_denied('hr_policies');
            }

            $content_data = $this->_handle_policy_content(admin_url('hr_module/policies/add'));
            $data = array_merge([
                'title'          => $this->input->post('title', true),
                'type'           => $type,
                'department_ids' => $department_ids ? implode(',', $department_ids) : null,
                'created_by'     => get_staff_user_id(),
            ], $content_data);

            $id = $this->Policies_model->add($data);
            if ($id) {
                if ($this->Hr_module_model->notifications_enabled('notify_policy')) {
                    $this->_notify_admin_submitted($id);
                }
                set_alert('success', 'Policy submitted and awaiting admin approval.');
                redirect(admin_url('hr_module/policies/view/' . $id));
            }
            set_alert('danger', 'Could not save the policy.');
            redirect(admin_url('hr_module/policies/add'));
        }

        $data['title']      = 'Add Policy';
        $data['is_global']  = $is_global;
        $data['own_department'] = $own_dept;
        $data['departments']    = $this->Departments_model->get_active();
        $this->load->view('hr_module/policies/form', $data);
    }

    public function edit($id)
    {
        $policy = $this->Policies_model->get($id);
        if (!$policy) show_404();
        if (staff_cant('edit', 'hr_policies') && !is_admin()) {
            access_denied('hr_policies');
        }
        if (!$this->_can_manage_departments($policy->department_id_list)) {
            access_denied('hr_policies');
        }

        $pending_revision = $this->Policies_model->get_pending_revision($id);
        if ($pending_revision) {
            set_alert('warning', 'An update to this policy is already awaiting admin review.');
            redirect(admin_url('hr_module/policies/view/' . $id));
        }

        $is_global = $this->_is_global_manager();

        if ($this->input->post()) {
            $type = $is_global && $this->input->post('type') === 'public' ? 'public' : 'private';
            $department_ids = [];
            if ($type === 'private') {
                $department_ids = $is_global
                    ? array_values(array_filter(array_map('intval', (array) $this->input->post('department_ids'))))
                    : $policy->department_id_list;
            }

            if ($type === 'private' && empty($department_ids)) {
                set_alert('danger', 'At least one department is required for a private policy.');
                redirect(admin_url('hr_module/policies/edit/' . $id));
            }
            if (!$this->_can_manage_departments($department_ids)) {
                access_denied('hr_policies');
            }

            $content_data = $this->_handle_policy_content(admin_url('hr_module/policies/edit/' . $id), $policy->attachment);
            $data = array_merge([
                'title'          => $this->input->post('title', true),
                'type'           => $type,
                'department_ids' => $department_ids ? implode(',', $department_ids) : null,
            ], $content_data);

            $result = $this->Policies_model->submit_revision($id, $data);
            if ($result['success']) {
                if ($this->Hr_module_model->notifications_enabled('notify_policy')) {
                    $this->_notify_admin_revision($id, $result['id']);
                }
                set_alert('success', 'Update submitted and awaiting admin approval. The current version stays visible until then.');
                redirect(admin_url('hr_module/policies/view/' . $id));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/policies/view/' . $id));
        }

        $data['title']       = 'Edit Policy';
        $data['policy']      = $policy;
        $data['is_global']   = $is_global;
        $data['own_department'] = $this->_own_department_id();
        $data['departments']    = $this->Departments_model->get_active();
        $this->load->view('hr_module/policies/form', $data);
    }

    public function view($id)
    {
        $policy = $this->Policies_model->get($id);
        if (!$policy) show_404();
        if (!$this->_can_view_policy($policy)) {
            access_denied('hr_policies');
        }

        $data['title']  = htmlspecialchars($policy->title);
        $data['policy'] = $policy;
        $data['can_manage']  = $this->_can_manage_departments($policy->department_id_list);
        $data['is_admin_reviewer'] = $this->_is_policy_approver();
        $data['pending_revision'] = $this->Policies_model->get_pending_revision($id);
        $this->load->view('hr_module/policies/view', $data);
    }

    // Same _can_view_policy() authorization as view() above, proxied so
    // attachments aren't directly-fetchable static files. $filename must be
    // one of the policy's OWN listed attachments (or its pending revision's) -
    // not just any file in the shared policies/ upload folder.
    public function download($id, $filename)
    {
        $policy = $this->Policies_model->get($id);
        if (!$policy) show_404();
        if (!$this->_can_view_policy($policy)) access_denied('hr_policies');

        $filename = basename($filename);
        $attachments = $this->Policies_model->decode_attachments($policy->attachment);
        $revision = $this->Policies_model->get_pending_revision($id);
        if ($revision && ($this->_can_manage_departments($policy->department_id_list) || $this->_is_policy_approver())) {
            $attachments = array_merge($attachments, $this->Policies_model->decode_attachments($revision->attachment));
        }

        $found = false;
        foreach ($attachments as $a) {
            if ($a['file'] === $filename) { $found = true; break; }
        }
        if (!$found) show_404();

        $this->load->helper('download');
        force_download(FCPATH . 'uploads/hr_module/policies/' . $filename, null);
    }

    // Approving/rejecting a submission is restricted to whichever single staff member
    // is configured as the policy approver in Settings - not every admin, per explicit
    // product requirement. See _is_policy_approver() above.
    public function approve($id)
    {
        if (!$this->_is_policy_approver()) access_denied('hr_policies');
        $result = $this->Policies_model->approve($id);
        if ($result['success']) {
            if ($this->Hr_module_model->notifications_enabled('notify_policy')) {
                $this->_broadcast_published($id);
            }
            set_alert('success', 'Policy approved and published.');
        } else {
            set_alert('danger', $result['message']);
        }
        redirect(admin_url('hr_module/policies/view/' . $id));
    }

    public function reject($id)
    {
        if (!$this->_is_policy_approver()) access_denied('hr_policies');
        $reason = $this->input->post('reason', true);
        $result = $this->Policies_model->reject($id, $reason);
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? 'Policy rejected.' : $result['message']);
        redirect(admin_url('hr_module/policies/view/' . $id));
    }

    public function approve_revision($id)
    {
        if (!$this->_is_policy_approver()) access_denied('hr_policies');
        $result = $this->Policies_model->approve_revision($id);
        if ($result['success']) {
            if ($this->Hr_module_model->notifications_enabled('notify_policy')) {
                $this->_broadcast_published($result['policy_id'], true);
            }
            set_alert('success', 'Update approved and published.');
            redirect(admin_url('hr_module/policies/view/' . $result['policy_id']));
        }
        set_alert('danger', $result['message']);
        redirect(admin_url('hr_module/policies'));
    }

    public function reject_revision($id)
    {
        if (!$this->_is_policy_approver()) access_denied('hr_policies');
        $reason = $this->input->post('reason', true);
        $result = $this->Policies_model->reject_revision($id, $reason);
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? 'Update rejected.' : $result['message']);
        redirect(admin_url('hr_module/policies/view/' . ($result['policy_id'] ?? '')));
    }

    public function delete($id)
    {
        $policy = $this->Policies_model->get($id);
        if (!$policy) show_404();
        if (staff_cant('delete', 'hr_policies') && !is_admin()) {
            access_denied('hr_policies');
        }
        if (!$this->_can_manage_departments($policy->department_id_list)) {
            access_denied('hr_policies');
        }
        $this->Policies_model->delete($id);
        set_alert('success', 'Policy deleted.');
        redirect(admin_url('hr_module/policies'));
    }

    // Sends a policy-review notification (email + central bell-icon notification)
    // to the configured policy approver(s) specifically - not the general HR
    // notification inbox, since only they can act on it. Falls back to the
    // general inbox while policy_approver_ids is left unconfigured, matching
    // _is_policy_approver()'s is_admin() fallback above. $path is the relative
    // route (e.g. 'hr_module/policies/view/5'), used for both the email link and
    // the notification's link.
    private function _notify_approver($subject, $message, $path, $notif_key, $notif_data = [])
    {
        $approvers = $this->Hr_module_model->get_policy_approvers();
        $emails    = array_filter(array_column($approvers, 'email'));
        if (!empty($emails)) {
            foreach ($emails as $email) {
                $this->Hr_module_model->send_employee_email($email, $subject, $message, admin_url($path));
            }
        } else {
            $this->Hr_module_model->send_notification_email($subject, $message, admin_url($path));
        }
        $this->Hr_module_model->notify_staff_list(array_column($approvers, 'staffid'), $notif_key, $path, $notif_data);
    }

    // Reads the posted text content and/or multiple PDF uploads - both are optional
    // individually, but at least one file/content must end up present. Redirects
    // back with an alert (halting execution, since redirect() exits) on any
    // failure. $existing_attachment_json is the policy's current `attachment`
    // JSON (edit only) - any of those files not listed in remove_attachments[]
    // are kept alongside whatever new files are uploaded now.
    private function _handle_policy_content($redirect_url, $existing_attachment_json = null)
    {
        $content = $this->input->post('content', true);

        $existing = $this->Policies_model->decode_attachments($existing_attachment_json);
        $remove   = (array) $this->input->post('remove_attachments');
        if (!empty($remove)) {
            $upload_path = FCPATH . 'uploads/hr_module/policies/';
            $kept = [];
            foreach ($existing as $a) {
                if (in_array($a['file'], $remove, true)) {
                    $path = $upload_path . basename($a['file']);
                    if (is_file($path)) @unlink($path);
                } else {
                    $kept[] = $a;
                }
            }
            $existing = $kept;
        }

        $uploaded = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            $upload_path = FCPATH . 'uploads/hr_module/policies/';
            if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);
            hr_lock_upload_dir($upload_path);
            $count = count($_FILES['attachments']['name']);
            for ($i = 0; $i < $count; $i++) {
                if (empty($_FILES['attachments']['name'][$i])) continue;
                $_FILES['policy_attachment_' . $i] = [
                    'name'     => $_FILES['attachments']['name'][$i],
                    'type'     => $_FILES['attachments']['type'][$i],
                    'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                    'error'    => $_FILES['attachments']['error'][$i],
                    'size'     => $_FILES['attachments']['size'][$i],
                ];
                $this->load->library('upload', [
                    'upload_path'   => $upload_path,
                    'allowed_types' => 'pdf',
                    'max_size'      => 8192,
                    'encrypt_name'  => true,
                ]);
                if (!$this->upload->do_upload('policy_attachment_' . $i)) {
                    set_alert('danger', $this->upload->display_errors());
                    redirect($redirect_url);
                }
                $uploaded[] = [
                    'file' => $this->upload->data('file_name'),
                    'name' => $_FILES['attachments']['name'][$i],
                ];
            }
        }

        $attachments = array_merge($existing, $uploaded);

        $has_text = trim(strip_tags($content ?: '')) !== '';
        $has_pdf  = !empty($attachments);
        if (!$has_text && !$has_pdf) {
            set_alert('danger', 'Please provide text content, a PDF file, or both.');
            redirect($redirect_url);
        }

        return [
            'content'      => $content,
            'attachment'   => $has_pdf ? json_encode($attachments) : null,
            // Kept for older rows/back-compat only - display no longer branches on this.
            'content_type' => $has_pdf && !$has_text ? 'pdf' : 'text',
        ];
    }

    // Summarizes a policy/revision's content as "PDF (n files)", "Text", "PDF + Text",
    // or "-" - shared by every policy email so recipients see what's actually in it.
    private function _content_summary($obj)
    {
        $atts = $this->Policies_model->decode_attachments($obj->attachment);
        $summary = [];
        if (!empty($atts)) {
            $summary[] = 'PDF' . (count($atts) > 1 ? ' (' . count($atts) . ' files)' : '');
        }
        if ($obj->content && trim(strip_tags($obj->content)) !== '') {
            $summary[] = 'Text';
        }
        return $summary ? implode(' + ', $summary) : '-';
    }

    // Notifies the policy approver that a new policy is awaiting approval.
    private function _notify_admin_submitted($id)
    {
        $policy = $this->Policies_model->get($id);
        if (!$policy) return;

        $placeholders = [
            '{title}'        => $policy->title,
            '{visibility}'   => $policy->type === 'public' ? 'Public (all employees)' : 'Private - ' . ($policy->department_names ?: '-'),
            '{content}'      => $this->_content_summary($policy),
            '{submitted_by}' => $policy->created_by_name ?: '-',
        ];
        $tpl = $this->Email_templates_model->render('policy_submitted_for_approval', $placeholders);
        $this->_notify_approver(
            $tpl->subject,
            $tpl->body,
            'hr_module/policies/view/' . $id,
            'not_hr_policy_submitted',
            [$policy->title]
        );
    }

    // Notifies the policy approver that an update to an existing policy is
    // awaiting approval.
    private function _notify_admin_revision($policy_id, $revision_id)
    {
        $policy   = $this->Policies_model->get($policy_id);
        $revision = $this->Policies_model->get_revision($revision_id);
        if (!$policy || !$revision) return;

        $placeholders = [
            '{title}'        => $policy->title,
            '{visibility}'   => $revision->type === 'public' ? 'Public (all employees)' : 'Private - ' . ($revision->department_names ?: '-'),
            '{content}'      => $this->_content_summary($revision),
            '{submitted_by}' => $revision->submitted_by_name ?: '-',
        ];
        $tpl = $this->Email_templates_model->render('policy_revision_submitted', $placeholders);
        $this->_notify_approver(
            $tpl->subject,
            $tpl->body,
            'hr_module/policies/view/' . $policy_id,
            'not_hr_policy_update_submitted',
            [$policy->title]
        );
    }

    // Broadcasts a newly-published policy (or an approved update to one) to its
    // audience: every employee if public, or just that department's employees.
    private function _broadcast_published($id, $is_update = false)
    {
        $policy = $this->Policies_model->get($id);
        if (!$policy) return;

        $visibility = $policy->type === 'public' ? 'Public (all employees)' : 'Private - ' . ($policy->department_names ?: '-');
        if ($is_update) {
            $template_key = 'policy_updated';
            $placeholders = [
                '{title}'        => $policy->title,
                '{visibility}'   => $visibility,
                '{content}'      => $this->_content_summary($policy),
                '{updated_info}' => _dt($policy->updated_at),
            ];
        } else {
            $template_key = 'policy_published';
            $placeholders = [
                '{title}'          => $policy->title,
                '{visibility}'     => $visibility,
                '{content}'        => $this->_content_summary($policy),
                '{published_info}' => _dt($policy->published_at) . ' by ' . ($policy->approved_by_name ?: '-'),
            ];
        }
        $tpl  = $this->Email_templates_model->render($template_key, $placeholders);
        $link = admin_url('hr_module/policies/view/' . $id);

        $this->Hr_module_model->send_policy_announcement(
            $tpl->subject,
            $tpl->body,
            $policy->type === 'public' ? null : $policy->department_id_list,
            $link
        );
        $this->Hr_module_model->send_whatsapp_announcement($template_key, $placeholders, $link);

        $audience = $policy->type === 'public'
            ? $this->Employees_model->get_active_staff_ids()
            : $this->Employees_model->get_active_staff_ids_for_departments($policy->department_id_list);
        $this->Hr_module_model->notify_staff_list(
            $audience,
            $is_update ? 'not_hr_policy_updated_published' : 'not_hr_policy_published',
            'hr_module/policies/view/' . $id,
            [$policy->title]
        );
    }
}
