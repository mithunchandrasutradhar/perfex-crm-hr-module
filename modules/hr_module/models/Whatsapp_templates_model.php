<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Lets HR customize the WhatsApp message text (subject/body, with {placeholder}
 * tokens) for the module's public, broadcast-style team announcements - the
 * only notifications ever sent over WhatsApp (see
 * Hr_module_model::send_whatsapp_announcement()). Uses the same template_key
 * values as the matching entries in Email_templates_model, but only for the
 * announcement-type events - independent wording from the email side.
 */
class Whatsapp_templates_model extends App_Model
{
    private $table = 'hr_whatsapp_templates';

    public function __construct()
    {
        parent::__construct();
        $this->_ensure_tables();
    }

    private function _ensure_tables()
    {
        if (!$this->db->table_exists(db_prefix() . $this->table)) {
            $this->db->query('CREATE TABLE `' . db_prefix() . $this->table . '` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `template_key` varchar(100) NOT NULL,
              `name` varchar(191) NOT NULL,
              `subject` varchar(255) NOT NULL,
              `body` text NOT NULL,
              `placeholders` varchar(500) DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `template_key` (`template_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ';');
        }

        // Seed any default template that doesn't exist yet (per-key, not just
        // per-table) - keyed so future additions get seeded automatically
        // without ever overwriting an already-customized row.
        $existing = array_column(
            $this->db->select('template_key')->get(db_prefix() . $this->table)->result_array(),
            'template_key'
        );
        $now = date('Y-m-d H:i:s');
        foreach ($this->_defaults() as $key => $tpl) {
            if (in_array($key, $existing, true)) continue;
            $this->db->insert(db_prefix() . $this->table, [
                'template_key' => $key,
                'name'         => $tpl['name'],
                'subject'      => $tpl['subject'],
                'body'         => $tpl['body'],
                'placeholders' => $tpl['placeholders'],
                'updated_at'   => $now,
            ]);
        }
    }

    public function get_all()
    {
        return $this->db->order_by('name', 'ASC')->get(db_prefix() . $this->table)->result();
    }

    public function get($id)
    {
        return $this->db->where('id', $id)->get(db_prefix() . $this->table)->row();
    }

    public function get_by_key($key)
    {
        return $this->db->where('template_key', $key)->get(db_prefix() . $this->table)->row();
    }

    public function update_template($id, $subject, $body)
    {
        $updated = $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'subject'    => $subject,
            'body'       => $body,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($updated) {
            log_activity('HR WhatsApp Template Updated [ID: ' . $id . ']');
        }
        return $updated;
    }

    // Fetches the template by key and substitutes {placeholder} tokens - plain
    // text, no HTML escaping/conversion, since this feeds a WhatsApp message
    // (not an email body). Returns null if the template key doesn't exist
    // (should not happen once _ensure_tables() has run).
    public function render($key, array $placeholders)
    {
        $tpl = $this->get_by_key($key);
        if (!$tpl) {
            return null;
        }
        return $this->render_text($tpl->subject, $tpl->body, $placeholders);
    }

    // Same substitution as render(), but for a raw subject/body pair instead of
    // a stored template - used by the "Send Test Message" preview so it can
    // render whatever the admin currently has typed in the edit form, even
    // before they've saved it.
    public function render_text($subject_text, $body_text, array $placeholders)
    {
        return (object) [
            'subject' => strtr($subject_text, $placeholders),
            'body'    => strtr($body_text, $placeholders),
        ];
    }

    // Builds a readable [Sample Value] for every {placeholder} a template
    // declares (from its stored `placeholders` hint column), for the "Send
    // Test Message" preview - lets an admin see formatting/wording without a
    // real record on hand. E.g. {employee_name} -> "[Sample Employee Name]".
    public function build_sample_placeholders($key)
    {
        $tpl = $this->get_by_key($key);
        if (!$tpl || empty($tpl->placeholders)) {
            return [];
        }
        $sample = [];
        foreach (explode(',', $tpl->placeholders) as $token) {
            $token = trim($token);
            if ($token === '') continue;
            $label = ucwords(str_replace('_', ' ', trim($token, '{}')));
            $sample[$token] = '[Sample ' . $label . ']';
        }
        return $sample;
    }

    // The full set of default templates, seeded on first use. Only public,
    // broadcast-style team announcements - the only WhatsApp messages this
    // module ever sends (see Hr_module_model::send_whatsapp_announcement()) -
    // seeded with the same wording as the matching Email Templates entry so
    // switching the WhatsApp sender over to this store didn't change any
    // message text an install was already sending, until an admin customizes
    // it here.
    private function _defaults()
    {
        return [
            'leave_announcement' => [
                'name' => 'Leave Announcement (Broadcast to All Staff)',
                'subject' => 'Leave Announcement: {employee_name} will be on leave',
                'placeholders' => '{employee_name}, {employee_code}, {department}, {designation}, {leave_type}, {leave_dates}, {total_days}',
                'body' => "Dear Team,\n\nThis is to formally inform you that {employee_name} ({employee_code}) will be on leave as per the schedule below. Please plan your work accordingly during this period.\n\nEmployee: {employee_name} ({employee_code})\nDepartment: {department}\nDesignation: {designation}\nLeave Type: {leave_type}\nLeave Dates:\n{leave_dates}\nTotal Days: {total_days}\n\nRegards,\nHR Department",
            ],
            'leave_cancellation_announcement' => [
                'name' => 'Leave Cancellation Announcement (Broadcast to All Staff)',
                'subject' => "Leave Cancellation: {employee_name}'s leave has been cancelled",
                'placeholders' => '{employee_name}, {employee_code}, {department}, {designation}, {leave_type}, {leave_dates}',
                'body' => "Dear Team,\n\nPlease note that the previously announced leave for {employee_name} ({employee_code}) has been cancelled. Please disregard the earlier announcement.\n\nEmployee: {employee_name} ({employee_code})\nDepartment: {department}\nDesignation: {designation}\nLeave Type: {leave_type}\nLeave Dates:\n{leave_dates}\n\nRegards,\nHR Department",
            ],
            'policy_published' => [
                'name' => 'Policy Published (Broadcast)',
                'subject' => 'New Policy Published: {title}',
                'placeholders' => '{title}, {visibility}, {content}, {published_info}',
                'body' => "A new policy has been published.\n\nTitle: {title}\nVisibility: {visibility}\nContent: {content}\nPublished: {published_info}",
            ],
            'policy_updated' => [
                'name' => 'Policy Updated (Broadcast)',
                'subject' => 'Policy Updated: {title}',
                'placeholders' => '{title}, {visibility}, {content}, {updated_info}',
                'body' => "An existing policy has been updated. Please review the changes.\n\nTitle: {title}\nVisibility: {visibility}\nContent: {content}\nUpdated: {updated_info}",
            ],
            'holiday_reminder' => [
                'name' => 'Holiday Reminder (Broadcast, Day Before)',
                'subject' => 'Holiday Notice: {holiday_name} on {day_name}, {date}',
                'placeholders' => '{holiday_name}, {day_name}, {date}',
                'body' => "Dear Team,\n\nThis is to formally notify all employees that {holiday_name} falls on {day_name}, {date}, and the office will remain closed in observance of this holiday.\n\nPlease note that our Customer Service Executive Department and Support Department will continue to operate as per their scheduled duty roster on this day, ensuring uninterrupted service to our clients.\n\nWe wish you a pleasant and restful holiday.\n\nRegards,\nHR Department",
            ],
        ];
    }
}
