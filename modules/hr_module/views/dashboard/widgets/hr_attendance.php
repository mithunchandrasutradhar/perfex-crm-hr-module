<?php defined('BASEPATH') or exit('No direct script access allowed');
// Self-contained, like every module-contributed dashboard widget has to be -
// core's Dashboard controller has no idea this module exists, so this widget
// loads its own model data (via the shared per-request cache, since 4 other
// "HR ..." widgets need the same underlying stats call) and does its own
// profile check.
[$employee_id, $hr_stats] = hr_get_own_employee_dashboard_stats();

$att_labels = [
    'present'  => ['label' => 'Present',  'class' => 'text-success'],
    'late'     => ['label' => 'Late',     'class' => 'text-warning'],
    'absent'   => ['label' => 'Absent',   'class' => 'text-danger'],
    'half_day' => ['label' => 'Half Day', 'class' => 'text-info'],
];
$att = $employee_id ? ($hr_stats['attendance_today'] ?? null) : null;
$att_info = $att ? ($att_labels[$att] ?? ['label' => ucfirst($att), 'class' => 'tw-text-neutral-800']) : null;
?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>"
    data-name="HR Today's Attendance">
    <?php if ($employee_id): ?>
    <div class="panel_s">
        <div class="panel-body padding-10">
            <div class="widget-dragger"></div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-1.5">
                <p class="tw-font-semibold tw-flex tw-items-center tw-mb-0 tw-space-x-1.5 rtl:tw-space-x-reverse">
                    <i class="fa fa-calendar-check tw-text-neutral-500 tw-mr-1"></i>
                    <span class="tw-text-neutral-700">HR Today's Attendance</span>
                </p>
                <a href="<?php echo admin_url('hr_module/attendance'); ?>">
                    <?php echo _l('home_widget_view_all'); ?>
                </a>
            </div>

            <hr class="-tw-mx-3 tw-mt-2 tw-mb-4">

            <div class="tw-text-center tw-py-2">
                <div class="tw-text-2xl tw-font-bold <?php echo $att_info ? $att_info['class'] : 'tw-text-neutral-400'; ?>">
                    <?php echo $att_info ? $att_info['label'] : 'Not Marked'; ?>
                </div>
                <div class="tw-text-xs tw-text-neutral-500 tw-mt-1"><?php echo date('l, d M Y'); ?></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="panel_s">
        <div class="panel-body padding-10 tw-text-center tw-text-neutral-500">
            <div class="widget-dragger"></div>
            <i class="fa fa-user-times tw-mr-1"></i>
            Your staff account is not linked to an HR employee profile yet.
        </div>
    </div>
    <?php endif; ?>
</div>
