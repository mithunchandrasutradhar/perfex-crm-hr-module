<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Lets HR customize the body/subject text of every notification email this
 * module sends, without touching code. Each template is a plain-text body
 * with {placeholder} tokens that get substituted at send time - see render().
 */
class Email_templates_model extends App_Model
{
    private $table = 'hr_email_templates';

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
        return $this->db->where('id', $id)->update(db_prefix() . $this->table, [
            'subject'    => $subject,
            'body'       => $body,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // Fetches the template by key and substitutes {placeholder} tokens. Values
    // in $placeholders are plain text (not pre-escaped) - the subject is used
    // as-is (plain text, not HTML), while the body is HTML-escaped first, then
    // substituted, then converted to <br> line breaks, so callers never need to
    // worry about escaping and multi-line placeholder values (e.g. a list of
    // leave dates joined by "\n") still render correctly.
    // Returns null if the template key doesn't exist (should not happen once
    // _ensure_tables() has run, since every default key is always seeded).
    public function render($key, array $placeholders)
    {
        $tpl = $this->get_by_key($key);
        if (!$tpl) {
            return null;
        }
        return $this->render_text($tpl->subject, $tpl->body, $placeholders);
    }

    // Same substitution/escaping as render(), but for a raw subject/body pair
    // instead of a stored template - used by the "Send Test Email" preview so
    // it can render whatever the admin currently has typed in the edit form,
    // even before they've saved it.
    public function render_text($subject_text, $body_text, array $placeholders)
    {
        $subject = strtr($subject_text, $placeholders);

        $escaped = [];
        foreach ($placeholders as $token => $value) {
            $escaped[$token] = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        $body = nl2br(strtr(htmlspecialchars($body_text, ENT_QUOTES, 'UTF-8'), $escaped));

        return (object) ['subject' => $subject, 'body' => $body];
    }

    // Same template + placeholders as render(), but returns plain text (no
    // HTML escaping, no <br> conversion, real newlines kept intact) - used by
    // the WhatsApp sender so it reuses the exact same admin-editable template
    // as email, just without HTML markup. Returns null if the key doesn't exist.
    public function render_plain($key, array $placeholders)
    {
        $tpl = $this->get_by_key($key);
        if (!$tpl) {
            return null;
        }
        return (object) [
            'subject' => strtr($tpl->subject, $placeholders),
            'body'    => strtr($tpl->body, $placeholders),
        ];
    }

    // Builds a readable [Sample Value] for every {placeholder} a template
    // declares (from its stored `placeholders` hint column), for the "Send
    // Test Email" preview - lets an admin see formatting/wording without a
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

    // The full set of default templates, seeded on first use. Keys map 1:1 to
    // the notification-sending call sites across the module's controllers.
    private function _defaults()
    {
        return [
            'leave_apply' => [
                'name' => 'Leave Request Submitted (to HR)',
                'subject' => 'New Leave Request Submitted',
                'placeholders' => '{employee_name}, {department}, {designation}, {leave_type}, {leave_dates}, {total_days}, {reason}',
                'body' => "A new leave request has been submitted and is awaiting review.\n\nEmployee: {employee_name}\nDepartment: {department}\nDesignation: {designation}\nLeave Type: {leave_type}\nLeave Dates:\n{leave_dates}\nTotal Days: {total_days}\nReason: {reason}",
            ],
            'leave_approved' => [
                'name' => 'Leave Request Approved (to Employee)',
                'subject' => 'Your Leave Request Has Been Approved',
                'placeholders' => '{employee_name}, {department}, {designation}, {leave_type}, {leave_dates}, {total_days}, {notes}',
                'body' => "Hi {employee_name},\n\nYour leave request has been approved.\n\nDepartment: {department}\nDesignation: {designation}\nLeave Type: {leave_type}\nLeave Dates:\n{leave_dates}\nTotal Days: {total_days}\nNotes: {notes}",
            ],
            'leave_announcement' => [
                'name' => 'Leave Announcement (Broadcast to All Staff)',
                'subject' => 'Leave Announcement: {employee_name} will be on leave',
                'placeholders' => '{employee_name}, {employee_code}, {department}, {designation}, {leave_type}, {leave_dates}, {total_days}',
                'body' => "Dear Team,\n\nThis is to formally inform you that {employee_name} ({employee_code}) will be on leave as per the schedule below. Please plan your work accordingly during this period.\n\nEmployee: {employee_name} ({employee_code})\nDepartment: {department}\nDesignation: {designation}\nLeave Type: {leave_type}\nLeave Dates:\n{leave_dates}\nTotal Days: {total_days}\n\nRegards,\nHR Department",
            ],
            'leave_cancellation_request' => [
                'name' => 'Leave Cancellation Request Submitted (to HR)',
                'subject' => 'Leave Cancellation Request Submitted',
                'placeholders' => '{employee_name}, {department}, {designation}, {leave_type}, {leave_dates}, {total_days}, {reason}',
                'body' => "An employee has requested to cancel an already-approved leave request.\n\nEmployee: {employee_name}\nDepartment: {department}\nDesignation: {designation}\nLeave Type: {leave_type}\nLeave Dates:\n{leave_dates}\nTotal Days: {total_days}\nReason: {reason}",
            ],
            'leave_cancellation_approved' => [
                'name' => 'Leave Cancellation Approved (to Employee)',
                'subject' => 'Your Leave Cancellation Request Has Been Approved',
                'placeholders' => '{employee_name}, {department}, {designation}, {leave_type}, {leave_dates}, {total_days}',
                'body' => "Hi {employee_name},\n\nYour leave cancellation request has been approved - this leave is now cancelled.\n\nDepartment: {department}\nDesignation: {designation}\nLeave Type: {leave_type}\nLeave Dates:\n{leave_dates}\nTotal Days: {total_days}",
            ],
            'leave_cancellation_rejected' => [
                'name' => 'Leave Cancellation Rejected (to Employee)',
                'subject' => 'Your Leave Cancellation Request Has Been Rejected',
                'placeholders' => '{employee_name}, {department}, {designation}, {leave_type}, {leave_dates}, {total_days}',
                'body' => "Hi {employee_name},\n\nYour leave cancellation request has been rejected - this leave remains approved.\n\nDepartment: {department}\nDesignation: {designation}\nLeave Type: {leave_type}\nLeave Dates:\n{leave_dates}\nTotal Days: {total_days}",
            ],
            'leave_cancellation_announcement' => [
                'name' => 'Leave Cancellation Announcement (Broadcast to All Staff)',
                'subject' => "Leave Cancellation: {employee_name}'s leave has been cancelled",
                'placeholders' => '{employee_name}, {employee_code}, {department}, {designation}, {leave_type}, {leave_dates}',
                'body' => "Dear Team,\n\nPlease note that the previously announced leave for {employee_name} ({employee_code}) has been cancelled. Please disregard the earlier announcement.\n\nEmployee: {employee_name} ({employee_code})\nDepartment: {department}\nDesignation: {designation}\nLeave Type: {leave_type}\nLeave Dates:\n{leave_dates}\n\nRegards,\nHR Department",
            ],
            'loan_apply' => [
                'name' => 'Loan Request Submitted (to HR)',
                'subject' => 'New Loan Request Submitted',
                'placeholders' => '{employee_name}, {department}, {designation}, {amount}, {monthly_installment}, {repayment_months}, {reason}',
                'body' => "A new loan request has been submitted and is awaiting review.\n\nEmployee: {employee_name}\nDepartment: {department}\nDesignation: {designation}\nAmount: {amount}\nMonthly Installment: {monthly_installment}\nRepayment Months: {repayment_months}\nReason: {reason}",
            ],
            'loan_approved' => [
                'name' => 'Loan Request Approved (to Employee)',
                'subject' => 'Your Loan Request Has Been Approved',
                'placeholders' => '{employee_name}, {department}, {designation}, {amount}, {monthly_installment}, {repayment_months}, {disbursement_date}',
                'body' => "Hi {employee_name},\n\nYour loan request has been approved.\n\nDepartment: {department}\nDesignation: {designation}\nAmount: {amount}\nMonthly Installment: {monthly_installment}\nRepayment Months: {repayment_months}\nDisbursement Date: {disbursement_date}",
            ],
            'loan_rejected' => [
                'name' => 'Loan Request Rejected (to Employee)',
                'subject' => 'Your Loan Request Has Been Rejected',
                'placeholders' => '{employee_name}, {department}, {designation}, {amount}, {monthly_installment}, {repayment_months}, {reason}',
                'body' => "Hi {employee_name},\n\nYour loan request has been rejected.\n\nDepartment: {department}\nDesignation: {designation}\nAmount: {amount}\nMonthly Installment: {monthly_installment}\nRepayment Months: {repayment_months}\nReason: {reason}",
            ],
            'loan_deduction_request' => [
                'name' => 'Loan Deduction Request Submitted (to HR)',
                'subject' => 'New Loan Deduction Request Submitted',
                'placeholders' => '{employee_name}, {department}, {designation}, {loan_amount}, {outstanding}, {pay_period}, {amount}, {type}, {notes}',
                'body' => "A new loan deduction request has been submitted and is awaiting review.\n\nEmployee: {employee_name}\nDepartment: {department}\nDesignation: {designation}\nLoan: {loan_amount} (Outstanding: {outstanding})\nPay Period: {pay_period}\nAmount: {amount}\nType: {type}\nNotes: {notes}",
            ],
            'loan_deduction_approved' => [
                'name' => 'Loan Deduction Approved (to Employee)',
                'subject' => 'Your Loan Deduction Request Has Been Approved',
                'placeholders' => '{employee_name}, {department}, {designation}, {pay_period}, {amount}, {type}, {notes}',
                'body' => "Hi {employee_name},\n\nYour loan deduction request has been approved.\n\nDepartment: {department}\nDesignation: {designation}\nPay Period: {pay_period}\nAmount: {amount}\nType: {type}\nNotes: {notes}",
            ],
            'loan_deduction_rejected' => [
                'name' => 'Loan Deduction Rejected (to Employee)',
                'subject' => 'Your Loan Deduction Request Has Been Rejected',
                'placeholders' => '{employee_name}, {department}, {designation}, {pay_period}, {amount}, {type}, {notes}',
                'body' => "Hi {employee_name},\n\nYour loan deduction request has been rejected.\n\nDepartment: {department}\nDesignation: {designation}\nPay Period: {pay_period}\nAmount: {amount}\nType: {type}\nNotes: {notes}",
            ],
            'overtime_apply' => [
                'name' => 'Overtime Request Submitted (to HR)',
                'subject' => 'New Overtime Request Submitted',
                'placeholders' => '{employee_name}, {dates}, {reason}',
                'body' => "A new overtime request has been submitted and is awaiting review.\n\nEmployee: {employee_name}\nDates: {dates}\nReason: {reason}",
            ],
            'helpdesk_ticket_submitted' => [
                'name' => 'Helpdesk Ticket Submitted (to HR)',
                'subject' => 'New Helpdesk Ticket Submitted',
                'placeholders' => '{employee_name}, {subject}, {category}, {priority}, {message}',
                'body' => "A new helpdesk ticket has been submitted and is awaiting review.\n\nEmployee: {employee_name}\nSubject: {subject}\nCategory: {category}\nPriority: {priority}\nMessage: {message}",
            ],
            'policy_submitted_for_approval' => [
                'name' => 'Policy Submitted For Approval',
                'subject' => 'New Policy Submitted For Approval',
                'placeholders' => '{title}, {visibility}, {content}, {submitted_by}',
                'body' => "A new policy has been submitted and is awaiting approval.\n\nTitle: {title}\nVisibility: {visibility}\nContent: {content}\nSubmitted By: {submitted_by}",
            ],
            'policy_revision_submitted' => [
                'name' => 'Policy Update Submitted For Approval',
                'subject' => 'Policy Update Submitted For Approval',
                'placeholders' => '{title}, {visibility}, {content}, {submitted_by}',
                'body' => "An update to an existing policy has been submitted and is awaiting approval. The current version stays visible to employees until then.\n\nPolicy: {title}\nVisibility: {visibility}\nContent: {content}\nSubmitted By: {submitted_by}",
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
            'shift_applied' => [
                'name' => 'Shift Assignment Request Submitted (to HR)',
                'subject' => 'New Shift Assignment Request Submitted',
                'placeholders' => '{employee_name}, {department}, {designation}, {shift_name}, {date_range}, {reason}',
                'body' => "A new shift assignment request has been submitted and is awaiting review.\n\nEmployee: {employee_name}\nDepartment: {department}\nDesignation: {designation}\nShift: {shift_name}\nDate Range: {date_range}\nReason: {reason}",
            ],
            'shift_approved' => [
                'name' => 'Shift Assignment Approved (to Employee)',
                'subject' => 'Your Shift Assignment Request Has Been Approved',
                'placeholders' => '{employee_name}, {shift_name}, {date_range}',
                'body' => "Hi {employee_name},\n\nYour shift assignment request has been approved.\n\nShift: {shift_name}\nDate Range: {date_range}",
            ],
            'shift_rejected' => [
                'name' => 'Shift Assignment Rejected (to Employee)',
                'subject' => 'Your Shift Assignment Request Has Been Rejected',
                'placeholders' => '{employee_name}, {shift_name}, {date_range}, {reason}',
                'body' => "Hi {employee_name},\n\nYour shift assignment request has been rejected.\n\nShift: {shift_name}\nDate Range: {date_range}\nReason: {reason}",
            ],
            'training_instructor_assigned' => [
                'name' => 'Training: Instructor Assigned',
                'subject' => 'You Have Been Assigned as Instructor: {training_title}',
                'placeholders' => '{instructor_name}, {training_title}, {venue}, {schedule}, {status}, {description}',
                'body' => "Hi {instructor_name},\n\nYou have been assigned as the instructor for the following training:\n\nTraining: {training_title}\nVenue: {venue}\nDate & Time: {schedule}\nStatus: {status}\nDescription: {description}",
            ],
            'training_enrolled' => [
                'name' => 'Training: Employee Enrolled',
                'subject' => 'You Have Been Enrolled in a Training: {training_title}',
                'placeholders' => '{employee_name}, {training_title}, {instructor_name}, {venue}, {schedule}, {description}',
                'body' => "Hi {employee_name},\n\nYou have been enrolled in the following training:\n\nTraining: {training_title}\nInstructor: {instructor_name}\nVenue: {venue}\nDate & Time: {schedule}\nDescription: {description}",
            ],
            'holiday_reminder' => [
                'name' => 'Holiday Reminder (Broadcast, Day Before)',
                'subject' => 'Holiday Notice: {holiday_name} on {day_name}, {date}',
                'placeholders' => '{holiday_name}, {day_name}, {date}',
                'body' => "Dear Team,\n\nThis is to formally notify all employees that {holiday_name} falls on {day_name}, {date}, and the office will remain closed in observance of this holiday.\n\nPlease note that our Customer Service Executive Department and Support Department will continue to operate as per their scheduled duty roster on this day, ensuring uninterrupted service to our clients.\n\nWe wish you a pleasant and restful holiday.\n\nRegards,\nHR Department",
            ],
            'payroll_generated' => [
                'name' => 'Payroll Generated (to HR)',
                'subject' => 'Payroll Generated for {period}',
                'placeholders' => '{period}, {success_count}, {skipped_count}',
                'body' => "Dear Team,\n\nThe payroll for {period} has been generated.\n\nEmployees Processed: {success_count}\nSkipped (already existed): {skipped_count}\n\nRegards,\nHR Department",
            ],
        ];
    }
}
