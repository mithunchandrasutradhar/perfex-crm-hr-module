<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/** @var bool  $is_manager    */
/** @var bool  $no_profile    */
/** @var array $own_stats     */
/** @var array $manager_stats */
/** @var int   $employee_id   */
if (!isset($is_manager))    $is_manager    = false;
if (!isset($no_profile))    $no_profile    = false;
if (!isset($own_stats))     $own_stats     = [];
if (!isset($manager_stats)) $manager_stats = [];
if (!isset($employee_id))   $employee_id   = 0;
$show_own     = (bool) $employee_id;
$show_manager = (bool) $is_manager;
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">

<?php if (!empty($no_profile)): ?>
<!-- ── No employee profile linked ─────────────────────────────────── -->
<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="panel_s tw-mt-8">
            <div class="panel-body tw-text-center tw-py-12">
                <i class="fa fa-user-times fa-3x text-muted tw-mb-4"></i>
                <h4 class="tw-font-semibold"><?php echo _l('hr_dashboard_title'); ?></h4>
                <p class="text-muted">Your staff account is not linked to an HR employee profile yet.<br>Please contact HR to set up your employee record.</p>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<?php if ($show_own && $show_manager): ?>
<ul class="nav nav-tabs tw-mb-4" id="hr-dashboard-tabs">
    <li class="active"><a href="#hr-tab-own" data-toggle="tab"><i class="fa fa-user tw-mr-1"></i>My Dashboard</a></li>
    <li><a href="#hr-tab-company" data-toggle="tab"><i class="fa fa-building tw-mr-1"></i>Company Dashboard</a></li>
</ul>
<div class="tab-content">
<div class="tab-pane active" id="hr-tab-own">
<?php endif; ?>

<?php if ($show_own): $stats = $own_stats; ?>
<!-- ══════════════════════════════════════════════════════════════════
     PERSONAL DASHBOARD  (view_own staff)
     ══════════════════════════════════════════════════════════════════ -->

<?php
$att_status = $stats['attendance_today'] ?? null;
$att_labels = [
    'present'  => ['label' => 'Present',  'cls' => 'bg-success',  'icon' => 'fa-check-circle'],
    'late'     => ['label' => 'Late',     'cls' => 'bg-warning',  'icon' => 'fa-clock'],
    'absent'   => ['label' => 'Absent',   'cls' => 'bg-danger',   'icon' => 'fa-times-circle'],
    'half_day' => ['label' => 'Half Day', 'cls' => 'bg-info',     'icon' => 'fa-adjust'],
];
$att = $att_status ? ($att_labels[$att_status] ?? ['label' => ucfirst($att_status), 'cls' => 'bg-secondary', 'icon' => 'fa-circle']) : null;

$perf       = $stats['latest_task'] ?? null;
$open_tasks = $stats['open_tasks'] ?? 0;
$payroll    = $stats['latest_payroll'] ?? null;
$loan       = $stats['active_loan']    ?? null;
$trainings  = $stats['upcoming_trainings'] ?? [];

$task_status_colors = [
    'pending' => '#64748b', 'in_progress' => '#d97706',
    'partially_completed' => '#2563eb', 'completed' => '#16a34a',
];
?>

<!-- Row 1: Attendance · Leave Balance · Pending Leave · Open Tickets -->
<div class="row">

    <!-- Attendance Today -->
    <div class="col-md-3 col-sm-6">
        <div class="panel_s hr-stat-card">
            <div class="panel-body tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-3 tw-mb-3">
                    <div class="hr-stat-icon <?php echo $att ? $att['cls'] : 'bg-secondary'; ?>">
                        <i class="fa <?php echo $att ? $att['icon'] : 'fa-question-circle'; ?> fa-lg tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-uppercase tw-tracking-wide">Today's Attendance</div>
                        <div class="tw-text-xl tw-font-bold tw-text-neutral-800">
                            <?php echo $att ? $att['label'] : 'Not Marked'; ?>
                        </div>
                    </div>
                </div>
                <div class="tw-text-xs text-muted"><?php echo date('l, d M Y'); ?></div>
            </div>
        </div>
    </div>

    <!-- Leave Balance -->
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/leave'); ?>" class="block-link">
        <div class="panel_s hr-stat-card">
            <div class="panel-body tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-3 tw-mb-3">
                    <div class="hr-stat-icon bg-info">
                        <i class="fa fa-calendar-check fa-lg tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-uppercase tw-tracking-wide">Leave Balance</div>
                        <div class="tw-text-xl tw-font-bold tw-text-neutral-800">
                            <?php echo number_format($stats['leave_balance_remaining'] ?? 0, 1); ?> <span class="tw-text-sm tw-font-normal text-muted">days left</span>
                        </div>
                    </div>
                </div>
                <div class="tw-text-xs text-muted"><?php echo number_format($stats['leave_days_used'] ?? 0, 1); ?> days used this year</div>
            </div>
        </div>
        </a>
    </div>

    <!-- Pending Leave Requests -->
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/leave'); ?>" class="block-link">
        <div class="panel_s hr-stat-card">
            <div class="panel-body tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-3 tw-mb-3">
                    <div class="hr-stat-icon" style="background:#f59e0b">
                        <i class="fa fa-hourglass-half fa-lg tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-uppercase tw-tracking-wide">Pending Leaves</div>
                        <div class="tw-text-xl tw-font-bold tw-text-neutral-800">
                            <?php echo (int)($stats['pending_leaves'] ?? 0); ?>
                        </div>
                    </div>
                </div>
                <div class="tw-text-xs text-muted"><?php echo (int)($stats['approved_leaves'] ?? 0); ?> approved this year</div>
            </div>
        </div>
        </a>
    </div>

    <!-- Open Helpdesk Tickets -->
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/helpdesk'); ?>" class="block-link">
        <div class="panel_s hr-stat-card">
            <div class="panel-body tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-3 tw-mb-3">
                    <div class="hr-stat-icon" style="background:#6366f1">
                        <i class="fa fa-ticket-alt fa-lg tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-uppercase tw-tracking-wide">Open Tickets</div>
                        <div class="tw-text-xl tw-font-bold tw-text-neutral-800">
                            <?php echo (int)($stats['open_tickets'] ?? 0); ?>
                        </div>
                    </div>
                </div>
                <div class="tw-text-xs text-muted">Helpdesk requests</div>
            </div>
        </div>
        </a>
    </div>

</div>

<!-- Row 2: Payroll · Loan · Overtime · Performance -->
<div class="row">

    <!-- Latest Payslip -->
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/payroll'); ?>" class="block-link">
        <div class="panel_s hr-stat-card">
            <div class="panel-body tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-3 tw-mb-3">
                    <div class="hr-stat-icon bg-success">
                        <i class="fa fa-money-bill-wave fa-lg tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-uppercase tw-tracking-wide">Net Salary</div>
                        <div class="tw-text-xl tw-font-bold tw-text-neutral-800 tw-flex tw-items-center tw-gap-2">
                            <?php if ($payroll): ?>
                                <span id="net-salary-masked">****</span>
                                <span id="net-salary-value" style="display:none"><?php echo app_format_money($payroll->net_salary, get_base_currency()); ?></span>
                                <button type="button" id="net-salary-toggle" class="tw-bg-transparent tw-border-0 tw-p-0 tw-text-neutral-400" style="cursor:pointer" title="Show/hide amount">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            <?php else: ?>
                                <span class="tw-text-base text-muted">Not generated</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="tw-text-xs text-muted">
                    <?php if ($payroll): ?>
                        <?php echo date('F Y', mktime(0,0,0, $payroll->pay_month, 1, $payroll->pay_year)); ?>
                        — <span class="label label-<?php echo $payroll->status === 'paid' ? 'success' : ($payroll->status === 'approved' ? 'primary' : 'default'); ?> label-xs"><?php echo ucfirst($payroll->status); ?></span>
                    <?php else: ?>
                        No payslip available
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </a>
    </div>

    <!-- Active Loan -->
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/loans'); ?>" class="block-link">
        <div class="panel_s hr-stat-card">
            <div class="panel-body tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-3 tw-mb-3">
                    <div class="hr-stat-icon" style="background:#0891b2">
                        <i class="fa fa-hand-holding-usd fa-lg tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-uppercase tw-tracking-wide">Loan Outstanding</div>
                        <div class="tw-text-xl tw-font-bold tw-text-neutral-800">
                            <?php if ($loan): ?>
                                <?php echo app_format_money($loan->outstanding, get_base_currency()); ?>
                            <?php else: ?>
                                <span class="tw-text-base text-muted">No active loan</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="tw-text-xs text-muted">
                    <?php if ($loan): ?>
                        <?php echo app_format_money($loan->monthly_installment, get_base_currency()); ?>/month &bull; <?php echo $loan->repayment_months; ?> months
                    <?php else: ?>
                        Click to view loan history
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </a>
    </div>

    <!-- Overtime this month -->
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/overtime'); ?>" class="block-link">
        <div class="panel_s hr-stat-card">
            <div class="panel-body tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-3 tw-mb-3">
                    <div class="hr-stat-icon bg-warning">
                        <i class="fa fa-business-time fa-lg tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-uppercase tw-tracking-wide">Overtime (<?php echo date('M'); ?>)</div>
                        <div class="tw-text-xl tw-font-bold tw-text-neutral-800">
                            <?php echo number_format($stats['approved_overtime_hours'] ?? 0, 1); ?> <span class="tw-text-sm tw-font-normal text-muted">hrs</span>
                        </div>
                    </div>
                </div>
                <div class="tw-text-xs text-muted">
                    <?php
                    $po = (int)($stats['pending_overtime'] ?? 0);
                    echo $po > 0 ? $po . ' request(s) pending approval' : 'No pending overtime';
                    ?>
                </div>
            </div>
        </div>
        </a>
    </div>

    <!-- Performance -->
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/performance'); ?>" class="block-link">
        <div class="panel_s hr-stat-card">
            <div class="panel-body tw-p-4">
                <div class="tw-flex tw-items-center tw-gap-3 tw-mb-3">
                    <div class="hr-stat-icon" style="background:<?php echo $perf ? ($task_status_colors[$perf->status] ?? '#64748b') : '#64748b'; ?>">
                        <i class="fa fa-tasks fa-lg tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-xs tw-text-neutral-500 tw-uppercase tw-tracking-wide">Performance</div>
                        <div class="tw-text-xl tw-font-bold tw-text-neutral-800">
                            <?php if ($open_tasks > 0): ?>
                                <?php echo $open_tasks; ?> <span class="tw-text-base">open task<?php echo $open_tasks == 1 ? '' : 's'; ?></span>
                            <?php elseif ($perf): ?>
                                <span class="tw-text-base">All caught up</span>
                            <?php else: ?>
                                <span class="tw-text-base text-muted">No tasks yet</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="tw-text-xs text-muted">
                    <?php if ($perf): ?>
                        <?php echo htmlspecialchars($perf->title); ?>
                        &bull; <?php echo ucfirst(str_replace('_', ' ', $perf->status)); ?>
                    <?php else: ?>
                        No tasks on record
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </a>
    </div>

</div>

<!-- Row 3: Upcoming Training + Quick Actions -->
<div class="row">
    <?php if (!empty($trainings)): ?>
    <div class="col-md-6">
        <div class="panel_s">
            <div class="panel-heading">
                <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-graduation-cap tw-mr-2 text-info"></i>Upcoming Training</h5>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-condensed tw-mb-0">
                        <thead><tr><th>Training</th><th>Start Date</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($trainings as $tr): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tr->title); ?></td>
                                <td><?php echo _d($tr->start_date); ?></td>
                                <td>
                                    <?php if ($tr->status === 'in_progress'): ?>
                                        <span class="label label-success">In Progress</span>
                                    <?php else: ?>
                                        <span class="label label-info">Scheduled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-md-<?php echo !empty($trainings) ? '6' : '12'; ?>">
        <div class="panel_s">
            <div class="panel-heading">
                <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-bolt tw-mr-2 text-warning"></i>Quick Actions</h5>
            </div>
            <div class="panel-body">
                <div class="tw-flex tw-flex-wrap tw-gap-2">
                    <?php if (staff_can('create', 'hr_leave') || staff_can('view_own', 'hr_leave')): ?>
                    <a href="<?php echo admin_url('hr_module/leave/apply'); ?>" class="btn btn-primary">
                        <i class="fa fa-calendar-plus tw-mr-1"></i>Apply for Leave
                    </a>
                    <?php endif; ?>
                    <?php if (staff_can('view', 'hr_leave') || staff_can('view_own', 'hr_leave')): ?>
                    <a href="<?php echo admin_url('hr_module/leave'); ?>" class="btn btn-default btn-sm">
                        <i class="fa fa-calendar tw-mr-1"></i>My Leaves
                    </a>
                    <?php endif; ?>
                    <?php if (staff_can('view', 'hr_attendance') || staff_can('view_own', 'hr_attendance')): ?>
                    <a href="<?php echo admin_url('hr_module/attendance'); ?>" class="btn btn-default btn-sm">
                        <i class="fa fa-clock tw-mr-1"></i>My Attendance
                    </a>
                    <?php endif; ?>
                    <?php if (staff_can('view', 'hr_payroll') || staff_can('view_own', 'hr_payroll')): ?>
                    <a href="<?php echo admin_url('hr_module/payroll'); ?>" class="btn btn-default btn-sm">
                        <i class="fa fa-file-invoice-dollar tw-mr-1"></i>My Payslips
                    </a>
                    <?php endif; ?>
                    <?php if (staff_can('create', 'hr_helpdesk') || staff_can('view_own', 'hr_helpdesk')): ?>
                    <a href="<?php echo admin_url('hr_module/helpdesk/submit'); ?>" class="btn btn-info btn-sm">
                        <i class="fa fa-ticket-alt tw-mr-1"></i>New Ticket
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php if ($show_own && $show_manager): ?>
</div>
<div class="tab-pane" id="hr-tab-company">
<?php endif; ?>

<?php if ($show_manager): $stats = $manager_stats; ?>
<!-- ══════════════════════════════════════════════════════════════════
     ADMIN / GLOBAL DASHBOARD
     ══════════════════════════════════════════════════════════════════ -->

<!-- KPI Row 1 -->
<div class="row">
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/employees'); ?>" class="block-link">
            <div class="panel_s hr-stat-card">
                <div class="panel-body tw-p-4 tw-flex tw-items-center tw-gap-4">
                    <div class="hr-stat-icon bg-info">
                        <i class="fa fa-users fa-2x tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-2xl tw-font-bold tw-text-neutral-800"><?php echo $stats['total_employees']; ?></div>
                        <div class="tw-text-sm tw-text-neutral-500"><?php echo _l('hr_dashboard_total_employees'); ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/employees'); ?>" class="block-link">
            <div class="panel_s hr-stat-card">
                <div class="panel-body tw-p-4 tw-flex tw-items-center tw-gap-4">
                    <div class="hr-stat-icon bg-success">
                        <i class="fa fa-user-check fa-2x tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-2xl tw-font-bold tw-text-neutral-800"><?php echo $stats['active_employees']; ?></div>
                        <div class="tw-text-sm tw-text-neutral-500"><?php echo _l('hr_dashboard_active_employees'); ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/departments'); ?>" class="block-link">
            <div class="panel_s hr-stat-card">
                <div class="panel-body tw-p-4 tw-flex tw-items-center tw-gap-4">
                    <div class="hr-stat-icon bg-primary">
                        <i class="fa fa-sitemap fa-2x tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-2xl tw-font-bold tw-text-neutral-800"><?php echo $stats['total_departments']; ?></div>
                        <div class="tw-text-sm tw-text-neutral-500"><?php echo _l('hr_dashboard_departments'); ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/attendance'); ?>" class="block-link">
            <div class="panel_s hr-stat-card">
                <div class="panel-body tw-p-4 tw-flex tw-items-center tw-gap-4">
                    <div class="hr-stat-icon bg-warning">
                        <i class="fa fa-clock fa-2x tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-2xl tw-font-bold tw-text-neutral-800"><?php echo $stats['present_today']; ?></div>
                        <div class="tw-text-sm tw-text-neutral-500"><?php echo _l('hr_dashboard_present_today'); ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- KPI Row 2 -->
<div class="row">
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/leave'); ?>" class="block-link">
            <div class="panel_s hr-stat-card">
                <div class="panel-body tw-p-4 tw-flex tw-items-center tw-gap-4">
                    <div class="hr-stat-icon bg-danger">
                        <i class="fa fa-calendar-times fa-2x tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-2xl tw-font-bold tw-text-neutral-800"><?php echo $stats['on_leave_today']; ?></div>
                        <div class="tw-text-sm tw-text-neutral-500"><?php echo _l('hr_dashboard_on_leave_today'); ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/leave'); ?>" class="block-link">
            <div class="panel_s hr-stat-card">
                <div class="panel-body tw-p-4 tw-flex tw-items-center tw-gap-4">
                    <div class="hr-stat-icon" style="background:#f59e0b">
                        <i class="fa fa-hourglass-half fa-2x tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-2xl tw-font-bold tw-text-neutral-800"><?php echo $stats['pending_leaves']; ?></div>
                        <div class="tw-text-sm tw-text-neutral-500"><?php echo _l('hr_dashboard_pending_leaves'); ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/loans'); ?>" class="block-link">
            <div class="panel_s hr-stat-card">
                <div class="panel-body tw-p-4 tw-flex tw-items-center tw-gap-4">
                    <div class="hr-stat-icon" style="background:#6366f1">
                        <i class="fa fa-hand-holding-usd fa-2x tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-2xl tw-font-bold tw-text-neutral-800"><?php echo $stats['pending_loans']; ?></div>
                        <div class="tw-text-sm tw-text-neutral-500"><?php echo _l('hr_dashboard_pending_loans'); ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="<?php echo admin_url('hr_module/overtime'); ?>" class="block-link">
            <div class="panel_s hr-stat-card">
                <div class="panel-body tw-p-4 tw-flex tw-items-center tw-gap-4">
                    <div class="hr-stat-icon" style="background:#0891b2">
                        <i class="fa fa-business-time fa-2x tw-text-white"></i>
                    </div>
                    <div>
                        <div class="tw-text-2xl tw-font-bold tw-text-neutral-800"><?php echo $stats['pending_overtime']; ?></div>
                        <div class="tw-text-sm tw-text-neutral-500"><?php echo _l('hr_dashboard_pending_overtime'); ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Quick Actions (admin) -->
<div class="row">
    <div class="col-md-12">
        <div class="panel_s">
            <div class="panel-body">
                <h5 class="tw-font-semibold tw-mb-4"><?php echo _l('hr_dashboard_quick_actions'); ?></h5>
                <div class="tw-flex tw-flex-wrap tw-gap-2">
                    <?php if (staff_can('create', 'hr_employees')): ?>
                    <a href="<?php echo admin_url('hr_module/employees/add'); ?>" class="btn btn-primary">
                        <i class="fa-regular fa-plus"></i> <?php echo _l('hr_employee_add'); ?>
                    </a>
                    <?php endif; ?>
                    <?php if (staff_can('create', 'hr_leave')): ?>
                    <a href="<?php echo admin_url('hr_module/leave/apply'); ?>" class="btn btn-info btn-sm">
                        <i class="fa fa-calendar-plus"></i> <?php echo _l('hr_leave_add'); ?>
                    </a>
                    <?php endif; ?>
                    <?php if (staff_can('create', 'hr_attendance')): ?>
                    <a href="<?php echo admin_url('hr_module/attendance/add'); ?>" class="btn btn-success btn-sm">
                        <i class="fa fa-check-circle"></i> <?php echo _l('hr_attendance_add'); ?>
                    </a>
                    <?php endif; ?>
                    <?php if (staff_can('view', 'hr_payroll')): ?>
                    <a href="<?php echo admin_url('hr_module/payroll/generate'); ?>" class="btn btn-warning btn-sm">
                        <i class="fa fa-cogs"></i> <?php echo _l('hr_payroll_generate'); ?>
                    </a>
                    <?php endif; ?>
                    <?php if (staff_can('view', 'hr_reports')): ?>
                    <a href="<?php echo admin_url('hr_module/reports'); ?>" class="btn btn-default btn-sm">
                        <i class="fa fa-chart-bar"></i> <?php echo _l('hr_menu_reports'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php if ($show_own && $show_manager): ?>
</div>
</div>
<?php endif; ?>

<?php endif; ?>

    </div><!-- /.content -->
</div><!-- /#wrapper -->

<style>
.block-link { text-decoration: none; color: inherit; display: block; }
.block-link:hover .panel_s { box-shadow: 0 4px 12px rgba(0,0,0,.1); transform: translateY(-1px); transition: all .2s; }
.hr-stat-card { margin-bottom: 16px; border-radius: 8px; overflow: hidden; }
.hr-stat-icon {
    width: 48px; height: 48px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.bg-secondary { background: #64748b; }
.label-xs { font-size: 10px; padding: 2px 5px; }
</style>

<?php init_tail(); ?>
<script>
$(function () {
    // The Net Salary card is wrapped in an <a> (click anywhere navigates to
    // Payroll) - the toggle button must stop that click from bubbling up and
    // triggering the navigation too.
    $('#net-salary-toggle').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $masked = $('#net-salary-masked');
        var $value  = $('#net-salary-value');
        var $icon   = $(this).find('i');
        if ($value.is(':visible')) {
            $value.hide();
            $masked.show();
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            $masked.hide();
            $value.show();
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        }
    });
});
</script>
