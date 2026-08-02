<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Whatsapp_templates extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Whatsapp_templates_model');
        $this->load->model('hr_module/Hr_module_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_settings') && !is_admin()) {
            access_denied('hr_settings');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'whatsapp_templates/table'));
            return;
        }
        $data['title']    = _l('hr_whatsapp_templates');
        $data['can_edit'] = staff_can('edit', 'hr_settings') || is_admin();
        $this->load->view('hr_module/whatsapp_templates/index', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_settings') && !is_admin()) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }

        if (!$this->input->post()) {
            $tpl = $this->Whatsapp_templates_model->get($id);
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
        $this->Whatsapp_templates_model->update_template($id, $subject, $body);
        echo json_encode(['success' => true, 'message' => _l('hr_whatsapp_template_updated_msg')]);
    }

    // Sends a preview of a template (with sample [Placeholder Name] values in
    // place of the real data) to a WhatsApp group/number the admin types in, so
    // they can check formatting/wording without needing a real event to trigger
    // it. Uses whatever base URL/session/API key is currently saved in Settings.
    public function send_test($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (staff_cant('edit', 'hr_settings') && !is_admin()) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }

        $tpl = $this->Whatsapp_templates_model->get($id);
        if (!$tpl) {
            echo json_encode(['success' => false, 'message' => 'Template not found.']);
            return;
        }

        $target = trim($this->input->post('test_target'));
        if (!$target) {
            echo json_encode(['success' => false, 'message' => _l('hr_settings_whatsapp_no_target')]);
            return;
        }

        // Preview whatever is currently typed in the edit form (even if not
        // saved yet) - falls back to the saved template if those weren't posted.
        $subject_text = $this->input->post('subject', true) ?: $tpl->subject;
        $body_text    = $this->input->post('body', true) ?: $tpl->body;

        $sample   = $this->Whatsapp_templates_model->build_sample_placeholders($tpl->template_key);
        $rendered = $this->Whatsapp_templates_model->render_text($subject_text, $body_text, $sample);
        $text     = "*[TEST] " . $rendered->subject . "*\n\n" . $rendered->body;

        $base_url = $this->Hr_module_model->get_setting('whatsapp_base_url', 'https://waha.abutalha.com.bd');
        $session  = $this->Hr_module_model->get_setting('whatsapp_session', 'default');
        $api_key  = $this->Hr_module_model->get_setting('whatsapp_api_key');

        $this->load->library('hr_module/Waha_lib');
        $result = $this->waha_lib->send_text($base_url, $session, $api_key, $target, $text);

        echo json_encode([
            'success' => $result['success'],
            'message' => $result['success'] ? _l('hr_whatsapp_template_test_sent') : (_l('hr_whatsapp_template_test_failed') . ' (' . $result['message'] . ')'),
        ]);
    }
}
