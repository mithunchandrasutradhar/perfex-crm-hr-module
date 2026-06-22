<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$tables = [
    'hr_settings',
    'hr_audit_trail',
    'hr_helpdesk_replies',
    'hr_helpdesk',
    'hr_contracts',
    'hr_training_participants',
    'hr_training',
    'hr_performance_reviews',
    'hr_overtime',
    'hr_loan_repayments',
    'hr_loans',
    'hr_payroll_details',
    'hr_payroll',
    'hr_payroll_items',
    'hr_zkteco_sync_logs',
    'hr_zkteco_mapping',
    'hr_zkteco_devices',
    'hr_attendance',
    'hr_leave_balances',
    'hr_leave_requests',
    'hr_leave_types',
    'hr_employees',
    'hr_designations',
    'hr_departments',
];

foreach ($tables as $table) {
    $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . $table . '`');
}
