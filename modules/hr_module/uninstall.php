<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Data removal is opt-in: by default, uninstalling the module only removes it
// from the modules registry and leaves all HR data intact. An admin must
// explicitly enable "Allow HR data removal on uninstall" in module settings
// before the tables below are dropped.
$data_removal_allowed = false;
if ($CI->db->table_exists(db_prefix() . 'hr_settings')) {
    $CI->db->where('setting_key', 'allow_data_removal_on_uninstall');
    $setting = $CI->db->get(db_prefix() . 'hr_settings')->row();
    $data_removal_allowed = $setting && $setting->setting_value == '1';
}

if (!$data_removal_allowed) {
    log_activity('HR Module uninstalled - data preserved (allow_data_removal_on_uninstall was not enabled)');
    return;
}

log_activity('HR Module uninstalled - data removal was explicitly enabled, dropping all HR tables');

$tables = [
    'hr_email_queue',
    'hr_loan_deduction_requests',
    'hr_holidays',
    'hr_settings',
    'hr_audit_trail',
    'hr_helpdesk_replies',
    'hr_helpdesk',
    'hr_contracts',
    'hr_training_attendance',
    'hr_training_sessions',
    'hr_training_participants',
    'hr_training',
    'hr_performance_sub_target_feedback',
    'hr_performance_sub_target_evaluators',
    'hr_performance_sub_targets',
    'hr_performance_targets',
    'hr_performance_task_feedback',
    'hr_performance_task_evaluators',
    'hr_performance_tasks',
    'hr_performance_reviews',
    'hr_overtime',
    'hr_loan_repayments',
    'hr_loans',
    'hr_payroll_details',
    'hr_payroll',
    'hr_payroll_items',
    'hr_zkteco_punches',
    'hr_zkteco_sync_logs',
    'hr_zkteco_mapping',
    'hr_zkteco_devices',
    'hr_attendance',
    'hr_leave_balances',
    'hr_leave_request_days',
    'hr_leave_requests',
    'hr_leave_types',
    'hr_policy_revisions',
    'hr_policies',
    'hr_shift_assignments',
    'hr_shift_types',
    'hr_email_templates',
    'hr_whatsapp_templates',
    'hr_employees',
    'hr_designations',
    'hr_branches',
    'hr_departments',
];

foreach ($tables as $table) {
    $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . $table . '`');
}
