<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: HR Management
Description: Complete Human Resource Management System for Perfex CRM
Version: 1.0.0
Requires at least: 3.3.*
Author: Alpha Net BD
*/

define('HR_MODULE_NAME', 'hr_module');

// ─── Hook registrations ────────────────────────────────────────────────────

hooks()->add_action('admin_init', 'hr_module_init_menu_items');
hooks()->add_action('admin_init', 'hr_module_register_permissions');
hooks()->add_action('after_cron_run', 'hr_module_cron_tasks');

// ─── Activation / Deactivation / Uninstall ────────────────────────────────

register_activation_hook(HR_MODULE_NAME, 'hr_module_activation_hook');
register_deactivation_hook(HR_MODULE_NAME, 'hr_module_deactivation_hook');
register_uninstall_hook(HR_MODULE_NAME, 'hr_module_uninstall_hook');

function hr_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

function hr_module_deactivation_hook()
{
    // Nothing to do on deactivation
}

function hr_module_uninstall_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/uninstall.php');
}

// ─── Language ─────────────────────────────────────────────────────────────

register_language_files(HR_MODULE_NAME, [HR_MODULE_NAME]);

// ─── Permissions ──────────────────────────────────────────────────────────

function hr_module_register_permissions()
{
    $caps = [];
    $cap  = ['view' => _l('permission_view'), 'create' => _l('permission_create'), 'edit' => _l('permission_edit'), 'delete' => _l('permission_delete')];

    // Employees
    $caps['capabilities'] = $cap;
    register_staff_capabilities('hr_employees', $caps, _l('hr_perm_employees'));

    // Departments
    $caps['capabilities'] = $cap;
    register_staff_capabilities('hr_departments', $caps, _l('hr_perm_departments'));

    // Leave Management
    $caps['capabilities'] = array_merge($cap, ['approve' => _l('permission_view') . ' (Approve/Reject)']);
    register_staff_capabilities('hr_leave', $caps, _l('hr_perm_leave'));

    // Attendance
    $caps['capabilities'] = $cap;
    register_staff_capabilities('hr_attendance', $caps, _l('hr_perm_attendance'));

    // Payroll
    $caps['capabilities'] = array_merge($cap, ['approve' => _l('permission_view') . ' (Approve)']);
    register_staff_capabilities('hr_payroll', $caps, _l('hr_perm_payroll'));

    // Loans
    $caps['capabilities'] = array_merge($cap, ['approve' => _l('permission_view') . ' (Approve/Reject)']);
    register_staff_capabilities('hr_loans', $caps, _l('hr_perm_loans'));

    // Overtime
    $caps['capabilities'] = array_merge($cap, ['approve' => _l('permission_view') . ' (Approve/Reject)']);
    register_staff_capabilities('hr_overtime', $caps, _l('hr_perm_overtime'));

    // Performance
    $caps['capabilities'] = $cap;
    register_staff_capabilities('hr_performance', $caps, _l('hr_perm_performance'));

    // Training
    $caps['capabilities'] = $cap;
    register_staff_capabilities('hr_training', $caps, _l('hr_perm_training'));

    // Helpdesk
    $caps['capabilities'] = $cap;
    register_staff_capabilities('hr_helpdesk', $caps, _l('hr_perm_helpdesk'));

    // ZKTeco
    $caps['capabilities'] = $cap;
    register_staff_capabilities('hr_zkteco', $caps, _l('hr_perm_zkteco'));

    // Contracts
    $caps['capabilities'] = $cap;
    register_staff_capabilities('hr_contracts', $caps, _l('hr_perm_contracts'));

    // Reports
    $caps['capabilities'] = ['view' => _l('permission_view')];
    register_staff_capabilities('hr_reports', $caps, _l('hr_perm_reports'));

    // Settings
    $caps['capabilities'] = ['view' => _l('permission_view'), 'edit' => _l('permission_edit')];
    register_staff_capabilities('hr_settings', $caps, _l('hr_perm_settings'));
}

// ─── Menu ─────────────────────────────────────────────────────────────────

function hr_module_init_menu_items()
{
    $CI = &get_instance();

    // Top-level HR parent menu
    $CI->app_menu->add_sidebar_menu_item('human-resource', [
        'name'     => _l('hr_module'),
        'icon'     => 'fa fa-users',
        'href'     => admin_url('hr_module'),
        'position' => 15,
    ]);

    // Dashboard
    $CI->app_menu->add_sidebar_children_item('human-resource', [
        'slug'     => 'hr-dashboard',
        'name'     => _l('hr_dashboard_title'),
        'href'     => admin_url('hr_module'),
        'position' => 1,
    ]);

    // Employees
    if (staff_can('view', 'hr_employees')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-employees',
            'name'     => _l('hr_menu_employees'),
            'href'     => admin_url('hr_module/employees'),
            'position' => 2,
        ]);
    }

    // Designations
    if (staff_can('view', 'hr_departments')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-designations',
            'name'     => _l('hr_menu_designations'),
            'href'     => admin_url('hr_module/designations'),
            'position' => 4,
        ]);
    }

    // Leave Management
    if (staff_can('view', 'hr_leave')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-leave',
            'name'     => _l('hr_menu_leave'),
            'href'     => admin_url('hr_module/leave'),
            'position' => 4,
        ]);
    }

    // Attendance
    if (staff_can('view', 'hr_attendance')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-attendance',
            'name'     => _l('hr_menu_attendance'),
            'href'     => admin_url('hr_module/attendance'),
            'position' => 5,
        ]);
    }

    // Payroll
    if (staff_can('view', 'hr_payroll')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-payroll',
            'name'     => _l('hr_menu_payroll'),
            'href'     => admin_url('hr_module/payroll'),
            'position' => 6,
        ]);
    }

    // Loans
    if (staff_can('view', 'hr_loans')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-loans',
            'name'     => _l('hr_menu_loans'),
            'href'     => admin_url('hr_module/loans'),
            'position' => 7,
        ]);
    }

    // Overtime
    if (staff_can('view', 'hr_overtime')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-overtime',
            'name'     => _l('hr_menu_overtime'),
            'href'     => admin_url('hr_module/overtime'),
            'position' => 8,
        ]);
    }

    // Performance
    if (staff_can('view', 'hr_performance')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-performance',
            'name'     => _l('hr_menu_performance'),
            'href'     => admin_url('hr_module/performance'),
            'position' => 9,
        ]);
    }

    // Training
    if (staff_can('view', 'hr_training')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-training',
            'name'     => _l('hr_menu_training'),
            'href'     => admin_url('hr_module/training'),
            'position' => 10,
        ]);
    }

    // Helpdesk
    if (staff_can('view', 'hr_helpdesk')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-helpdesk',
            'name'     => _l('hr_menu_helpdesk'),
            'href'     => admin_url('hr_module/helpdesk'),
            'position' => 11,
        ]);
    }

    // HR Contracts
    if (staff_can('view', 'hr_contracts')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-contracts',
            'name'     => _l('hr_menu_contracts'),
            'href'     => admin_url('hr_module/hr_contracts'),
            'position' => 12,
        ]);
    }

    // ZKTeco
    if (staff_can('view', 'hr_zkteco')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-zkteco',
            'name'     => _l('hr_menu_zkteco'),
            'href'     => admin_url('hr_module/zkteco'),
            'position' => 13,
        ]);
    }

    // Reports
    if (staff_can('view', 'hr_reports')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-reports',
            'name'     => _l('hr_menu_reports'),
            'href'     => admin_url('hr_module/reports'),
            'position' => 13,
        ]);
    }

    // Settings
    if (staff_can('view', 'hr_settings') || is_admin()) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-settings',
            'name'     => _l('hr_module_settings'),
            'href'     => admin_url('hr_module/settings'),
            'position' => 14,
        ]);
    }
}

// ─── Cron Tasks ───────────────────────────────────────────────────────────

function hr_module_cron_tasks()
{
    $CI = &get_instance();
    $CI->load->model('hr_module/Hr_module_model');

    // Auto-sync ZKTeco devices if enabled
    if ($CI->Hr_module_model->get_setting('zkteco_enabled') == '1') {
        $CI->load->model('hr_module/Zkteco_model');
        $CI->Zkteco_model->auto_sync_all_devices();
    }

    // Auto-expire contracts past their end_date
    $CI->load->model('hr_module/Hr_contracts_model');
    $CI->Hr_contracts_model->auto_expire();

    // Notify expiring contracts (30 days ahead)
    $CI->load->model('hr_module/Hr_module_model');
    $CI->Hr_module_model->notify_expiring_contracts();
}
