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

// install.php only ever runs automatically at first activation. Every table/
// column it adds after that point (e.g. hr_training.instructor_id) is guarded
// by an "only if missing" check, so it's always safe to re-run - but nothing
// re-ran it on an already-activated install, so a site whose module files get
// updated in place without a manual Setup > Modules deactivate+reactivate could
// keep running against a stale schema indefinitely (this is exactly what
// caused a real 500 on hr_module/training on a live site: the column existed
// in install.php but never got added to that site's already-created table).
// HR_MODULE_SCHEMA_VERSION + hr_module_ensure_schema() below closes that gap:
// bump this number whenever install.php gains a new guarded table/column, and
// every site running this module will pick it up automatically on its very
// next admin page load - no manual reactivation step, ever, on any install.
define('HR_MODULE_SCHEMA_VERSION', 2);

// ─── Hook registrations ────────────────────────────────────────────────────

hooks()->add_action('admin_init', 'hr_module_ensure_schema');
hooks()->add_action('admin_init', 'hr_module_init_menu_items');
hooks()->add_action('admin_init', 'hr_module_register_permissions');
hooks()->add_action('after_cron_run', 'hr_module_cron_tasks');
hooks()->add_action('after_render_aside_menu', 'hr_module_sidebar_active_fix');

// ─── Activation / Deactivation / Uninstall ────────────────────────────────

register_activation_hook(HR_MODULE_NAME, 'hr_module_activation_hook');
register_deactivation_hook(HR_MODULE_NAME, 'hr_module_deactivation_hook');
register_uninstall_hook(HR_MODULE_NAME, 'hr_module_uninstall_hook');

function hr_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
    hr_module_mark_schema_current();
}

// Re-runs install.php whenever the running code's schema version is newer than
// whatever this specific site last applied - self-healing any table/column
// install.php would have added since this site was first activated, with no
// manual step required. One cheap SELECT on hr_settings per admin page view in
// the steady state (already caught up); the real work only runs once, right
// after a deploy that bumped HR_MODULE_SCHEMA_VERSION.
function hr_module_ensure_schema()
{
    $CI = &get_instance();
    if (!$CI->db->table_exists(db_prefix() . 'hr_settings')) {
        return;
    }
    $row = $CI->db->where('setting_key', '_schema_version')->get(db_prefix() . 'hr_settings')->row();
    $applied = $row ? (int) $row->setting_value : 0;
    if ($applied >= HR_MODULE_SCHEMA_VERSION) {
        return;
    }

    require_once(__DIR__ . '/install.php');
    hr_module_mark_schema_current();
}

function hr_module_mark_schema_current()
{
    $CI = &get_instance();
    $now = date('Y-m-d H:i:s');
    $row = $CI->db->where('setting_key', '_schema_version')->get(db_prefix() . 'hr_settings')->row();
    if ($row) {
        $CI->db->where('setting_key', '_schema_version')
            ->update(db_prefix() . 'hr_settings', ['setting_value' => HR_MODULE_SCHEMA_VERSION, 'updated_at' => $now]);
    } else {
        $CI->db->insert(db_prefix() . 'hr_settings', [
            'setting_key'   => '_schema_version',
            'setting_value' => HR_MODULE_SCHEMA_VERSION,
            'created_at'    => $now,
        ]);
    }
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
    $view_global = _l('permission_view') . ' (' . _l('permission_global') . ')';
    $view_own    = _l('permission_view_own');
    $create      = _l('permission_create');
    $edit        = _l('permission_edit');
    $delete      = _l('permission_delete');
    $approve     = 'Approve/Reject';

    // Personal-data features: view_own (own records) + view (all records) + full CRUD
    $cap_personal = [
        'view_own' => $view_own,
        'view'     => $view_global,
        'create'   => $create,
        'edit'     => $edit,
        'delete'   => $delete,
    ];
    // Personal-data features that also have an approval workflow
    $cap_personal_approve = array_merge($cap_personal, ['approve' => $approve]);
    // Config/reference tables: no view_own, just global view + CRUD
    $cap_config = [
        'view'   => $view_global,
        'create' => $create,
        'edit'   => $edit,
        'delete' => $delete,
    ];

    register_staff_capabilities('hr_employees',   ['capabilities' => $cap_personal],         _l('hr_perm_employees'));
    register_staff_capabilities('hr_departments',  ['capabilities' => $cap_config],            _l('hr_perm_departments'));
    register_staff_capabilities('hr_leave',        ['capabilities' => $cap_personal_approve],  _l('hr_perm_leave'));
    register_staff_capabilities('hr_attendance',   ['capabilities' => $cap_personal],          _l('hr_perm_attendance'));
    register_staff_capabilities('hr_payroll',      ['capabilities' => $cap_personal_approve],  _l('hr_perm_payroll'));
    register_staff_capabilities('hr_loans',        ['capabilities' => $cap_personal_approve],  _l('hr_perm_loans'));
    register_staff_capabilities('hr_overtime',     ['capabilities' => $cap_personal_approve],  _l('hr_perm_overtime'));
    register_staff_capabilities('hr_performance',  ['capabilities' => $cap_personal],          _l('hr_perm_performance'));
    register_staff_capabilities('hr_training',     ['capabilities' => $cap_personal],          _l('hr_perm_training'));
    register_staff_capabilities('hr_helpdesk',     ['capabilities' => $cap_personal],          _l('hr_perm_helpdesk'));
    register_staff_capabilities('hr_contracts',    ['capabilities' => $cap_personal],          _l('hr_perm_contracts'));
    register_staff_capabilities('hr_zkteco',       ['capabilities' => $cap_config],            _l('hr_perm_zkteco'));
    register_staff_capabilities('hr_reports',      ['capabilities' => ['view' => $view_global]], _l('hr_perm_reports'));
    register_staff_capabilities('hr_settings',     ['capabilities' => ['view' => $view_global, 'edit' => $edit]], _l('hr_perm_settings'));
    register_staff_capabilities('hr_holidays',     ['capabilities' => ['view' => $view_global, 'edit' => $edit]], _l('hr_perm_holidays'));
    register_staff_capabilities('hr_policies',     ['capabilities' => $cap_personal],          _l('hr_perm_policies'));
    register_staff_capabilities('hr_shifts',       ['capabilities' => $cap_personal_approve],  _l('hr_perm_shifts'));
}

/**
 * Drops a "Deny from all" .htaccess into an hr_module upload directory the
 * first time it's created, so contract/loan/policy/helpdesk/training/leave
 * attachments and employee photos - all real PII - can't be fetched by a
 * direct static URL even if a filename ever leaks. Every download now goes
 * through a permission-checked controller action instead (see each
 * controller's download()/photo() method), so this never needs to be undone.
 */
function hr_lock_upload_dir($path)
{
    $file = rtrim($path, '/\\') . '/.htaccess';
    if (!is_file($file)) {
        file_put_contents($file, "Order Deny,Allow\nDeny from all\n");
    }
}

/**
 * Returns the hr_employees.id for the currently logged-in staff member, or 0 if none.
 * Used for view_own permission filtering.
 */
function hr_get_own_employee_id()
{
    $CI = &get_instance();
    if (!class_exists('Employees_model', false)) {
        $CI->load->model('hr_module/Employees_model');
    }
    $emp = $CI->Employees_model->get_by_staff_id(get_staff_user_id());
    return $emp ? (int) $emp->id : 0;
}

/**
 * Whether the currently logged-in staff member is personally enrolled in (as
 * employee) or assigned to (as instructor) at least one training - used to show
 * the Training menu item even if their role has no view/view_own permission.
 */
function hr_training_has_own_records()
{
    $CI = &get_instance();
    if (!class_exists('Training_model', false)) {
        $CI->load->model('hr_module/Training_model');
    }
    return $CI->Training_model->has_own_or_instructor(hr_get_own_employee_id(), get_staff_user_id());
}

/**
 * Human-readable label for a hr_leave_request_days.day_type value - shared by the
 * leave list table and the leave view/detail page so "Before Lunch"/"After Lunch"
 * are labelled identically everywhere.
 */
function hr_leave_day_type_label($type)
{
    static $labels = null;
    if ($labels === null) {
        $labels = [
            'full'              => _l('hr_leave_day_type_full'),
            'half_before_lunch' => _l('hr_leave_day_type_half') . ' (' . _l('hr_leave_before_lunch') . ')',
            'half_after_lunch'  => _l('hr_leave_day_type_half') . ' (' . _l('hr_leave_after_lunch') . ')',
            'hourly'            => _l('hr_leave_day_type_hourly'),
            'bridge'            => _l('hr_leave_day_type_bridge'),
        ];
    }
    return $labels[$type] ?? $type;
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

    // Employees — only show the list page to users with global view (admins/HR managers)
    // Staff with view_own see their info via the dashboard, not the employee directory
    if (staff_can('view', 'hr_employees')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-employees',
            'name'     => _l('hr_menu_employees'),
            'href'     => admin_url('hr_module/employees'),
            'position' => 2,
        ]);
    }

    // Designations — managed from Settings > Company Structure, not the sidebar

    // Leave Management
    if (staff_can('view', 'hr_leave') || staff_can('view_own', 'hr_leave')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-leave',
            'name'     => _l('hr_menu_leave'),
            'href'     => admin_url('hr_module/leave'),
            'position' => 4,
        ]);
    }

    // Attendance
    if (staff_can('view', 'hr_attendance') || staff_can('view_own', 'hr_attendance')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-attendance',
            'name'     => _l('hr_menu_attendance'),
            'href'     => admin_url('hr_module/attendance'),
            'position' => 5,
        ]);
    }

    // Payroll
    if (staff_can('view', 'hr_payroll') || staff_can('view_own', 'hr_payroll')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-payroll',
            'name'     => _l('hr_menu_payroll'),
            'href'     => admin_url('hr_module/payroll'),
            'position' => 6,
        ]);
    }

    // Loans
    if (staff_can('view', 'hr_loans') || staff_can('view_own', 'hr_loans')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-loans',
            'name'     => _l('hr_menu_loans'),
            'href'     => admin_url('hr_module/loans'),
            'position' => 7,
        ]);
    }

    // Overtime
    if (staff_can('view', 'hr_overtime') || staff_can('view_own', 'hr_overtime')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-overtime',
            'name'     => _l('hr_menu_overtime'),
            'href'     => admin_url('hr_module/overtime'),
            'position' => 8,
        ]);
    }

    // Performance
    if (staff_can('view', 'hr_performance') || staff_can('view_own', 'hr_performance')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-performance',
            'name'     => _l('hr_menu_performance'),
            'href'     => admin_url('hr_module/performance'),
            'position' => 9,
        ]);
    }

    // Training - also shown to staff with no view/view_own permission if they're
    // personally enrolled in (or assigned as instructor for) at least one training
    if (staff_can('view', 'hr_training') || staff_can('view_own', 'hr_training') || hr_training_has_own_records()) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-training',
            'name'     => _l('hr_menu_training'),
            'href'     => admin_url('hr_module/training'),
            'position' => 10,
        ]);
    }

    // Helpdesk
    if (staff_can('view', 'hr_helpdesk') || staff_can('view_own', 'hr_helpdesk')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-helpdesk',
            'name'     => _l('hr_menu_helpdesk'),
            'href'     => admin_url('hr_module/helpdesk'),
            'position' => 11,
        ]);
    }

    // HR Contracts
    if (staff_can('view', 'hr_contracts') || staff_can('view_own', 'hr_contracts')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-contracts',
            'name'     => _l('hr_menu_contracts'),
            'href'     => admin_url('hr_module/hr_contracts'),
            'position' => 12,
        ]);
    }

    // Policies
    if (is_admin() || staff_can('view', 'hr_policies') || staff_can('view_own', 'hr_policies')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-policies',
            'name'     => 'Policies',
            'href'     => admin_url('hr_module/policies'),
            'position' => 12,
        ]);
    }

    // Shifts
    if (is_admin() || staff_can('view', 'hr_shifts') || staff_can('view_own', 'hr_shifts')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-shifts',
            'name'     => 'Shifts',
            'href'     => admin_url('hr_module/shifts'),
            'position' => 8.5, // right after Overtime (8), before Performance (9)
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

    // Official Calendar
    if (is_admin() || staff_can('view', 'hr_holidays')) {
        $CI->app_menu->add_sidebar_children_item('human-resource', [
            'slug'     => 'hr-holidays',
            'name'     => 'Official Calendar',
            'href'     => admin_url('hr_module/holidays'),
            'position' => 12,
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

// The core sidebar only marks a menu item active/expanded on an EXACT href
// match against the current URL (assets/js/main.js) - so it works for the
// HR Dashboard link (href == hr_module root, no deeper subpages) but breaks
// for every other HR item as soon as you're one level deeper, e.g.
// hr_module/leave/apply or hr_module/loans/view/15 no longer equals the
// sidebar's hr_module/leave or hr_module/loans href. Since that JS is core
// and shared by every module's sidebar, fix it here instead: open the HR
// parent menu whenever the current URL is anywhere under hr_module, and
// highlight whichever child's href is the longest prefix match of the URL.
//
// This has to wait for metisMenu (core's sidebar plugin) to finish binding
// its click handlers before it can simulate a click to expand the menu.
// metisMenu is initialized from core's own main.js, whose exact timing
// (document ready vs window load, and its position among other "load"
// listeners) isn't something this module should depend on - a fixed "load"
// listener registered here actually fired BEFORE core's own (registration
// order for same-event listeners), running before metisMenu had bound
// anything. Polling for metisMenu's own jQuery data key sidesteps that
// entirely: it only proceeds once metisMenu has verifiably finished.
function hr_module_sidebar_active_fix()
{
    $hr_base = rtrim(admin_url('hr_module'), '/');
    ?>
    <script>
    (function () {
        var HR_BASE = <?php echo json_encode($hr_base); ?>;
        if (window.location.href.indexOf(HR_BASE) === -1) {
            return;
        }

        function isCurrent(href) {
            if (!href) {
                return false;
            }
            return window.location.href === href
                || window.location.href.indexOf(href + '/') === 0
                || window.location.href.indexOf(href + '?') === 0;
        }

        function applyActiveState($, $parent) {
            if (!$parent.hasClass('active')) {
                $parent.children('a').first().trigger('click');
            }

            var $best = null;
            var bestLen = -1;
            $parent.find('> ul.nav-second-level > li > a').each(function () {
                var href = this.getAttribute('href');
                if (isCurrent(href) && href.length > bestLen) {
                    bestLen = href.length;
                    $best = $(this);
                }
            });
            if ($best) {
                $parent.find('> ul.nav-second-level > li').removeClass('active');
                $best.parent('li').addClass('active');
            }
        }

        var attempts = 0;
        (function waitForMetisMenu() {
            attempts++;
            var $ = window.jQuery;
            var $sideMenu = $ ? $('#side-menu') : null;
            if ($ && $sideMenu && $sideMenu.data('metisMenu')) {
                applyActiveState($, $sideMenu.find('li.menu-item-human-resource').first());
                return;
            }
            if (attempts < 60) {
                setTimeout(waitForMetisMenu, 50);
            }
        })();
    })();
    </script>
    <?php
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

    // Day-before holiday reminder to all employees (see send_holiday_reminder())
    $CI->Hr_module_model->send_holiday_reminder();
}
