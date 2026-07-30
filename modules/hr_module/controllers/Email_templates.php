<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Email_templates extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Email_templates_model');
        $this->load->model('hr_module/Hr_module_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_settings') && !is_admin()) {
            access_denied('hr_settings');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'email_templates/table'));
            return;
        }
        $data['title']    = _l('hr_email_templates');
        $data['can_edit'] = staff_can('edit', 'hr_settings') || is_admin();
        $this->load->view('hr_module/email_templates/index', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_settings') && !is_admin()) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }

        if (!$this->input->post()) {
            $tpl = $this->Email_templates_model->get($id);
            if (!$tpl) { echo json_encode(null); return; }
            echo json_encode([
                'id'      => $tpl->id,
                'name'    => $tpl->name,
                'subject' => $tpl->subject,
                'body'    => $tpl->body,
                'placeholders' => $tpl->placeholders,
            ]);
            return;
        }

        $subject = $this->input->post('subject', true);
        $body    = $this->input->post('body', true);
        if (!$subject || !$body) {
            echo json_encode(['success' => false, 'message' => 'Subject and body are required.']);
            return;
        }
        $this->Email_templates_model->update_template($id, $subject, $body);
        echo json_encode(['success' => true, 'message' => _l('hr_email_template_updated_msg')]);
    }

    // Sends a preview of a template (with sample [Placeholder Name] values in
    // place of the real data) to an address the admin types in, so they can
    // check formatting/wording without needing a real record to trigger it.
    public function send_test($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (staff_cant('edit', 'hr_settings') && !is_admin()) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }

        $tpl = $this->Email_templates_model->get($id);
        if (!$tpl) {
            echo json_encode(['success' => false, 'message' => 'Template not found.']);
            return;
        }

        $test_email = trim($this->input->post('test_email'));
        if (!$test_email || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            return;
        }

        // Preview whatever is currently typed in the edit form (even if not
        // saved yet) - falls back to the saved template if those weren't posted.
        $subject_text = $this->input->post('subject', true) ?: $tpl->subject;
        $body_text    = $this->input->post('body', true) ?: $tpl->body;

        $sample   = $this->Email_templates_model->build_sample_placeholders($tpl->template_key);
        $rendered = $this->Email_templates_model->render_text($subject_text, $body_text, $sample);

        $sent = $this->Hr_module_model->send_employee_email(
            $test_email,
            '[TEST] ' . $rendered->subject,
            '<p style="background:#fef3c7;padding:8px 12px;border-radius:4px;color:#92400e;margin-top:0">'
                . '<strong>This is a test email.</strong> The bracketed values below are sample placeholders, not real data.</p>'
                . $rendered->body
        );

        echo json_encode([
            'success' => $sent,
            'message' => $sent ? _l('hr_email_template_test_sent') : _l('hr_email_template_test_failed'),
        ]);
    }
}
