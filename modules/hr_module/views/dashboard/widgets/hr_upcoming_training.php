<?php defined('BASEPATH') or exit('No direct script access allowed');
// Self-contained, like every module-contributed dashboard widget has to be -
// see hr_attendance.php for why the stats call is shared/cached across the
// 5 "HR ..." widgets.
[$employee_id, $hr_stats] = hr_get_own_employee_dashboard_stats();
$trainings = $employee_id ? ($hr_stats['upcoming_trainings'] ?? []) : [];
?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>"
    data-name="HR Upcoming Training">
    <?php if ($employee_id): ?>
    <div class="panel_s">
        <div class="panel-body padding-10">
            <div class="widget-dragger"></div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-1.5">
                <p class="tw-font-semibold tw-flex tw-items-center tw-mb-0 tw-space-x-1.5 rtl:tw-space-x-reverse">
                    <i class="fa fa-graduation-cap tw-text-neutral-500 tw-mr-1"></i>
                    <span class="tw-text-neutral-700">HR Upcoming Training</span>
                </p>
                <a href="<?php echo admin_url('hr_module/training'); ?>">
                    <?php echo _l('home_widget_view_all'); ?>
                </a>
            </div>

            <hr class="-tw-mx-3 tw-mt-2 tw-mb-4">

            <?php if (!empty($trainings)): ?>
            <?php foreach ($trainings as $tr): ?>
            <div class="tw-flex tw-justify-between tw-items-center tw-py-1.5" style="border-bottom:1px solid #f1f5f9">
                <div>
                    <a href="<?php echo admin_url('hr_module/training/view/' . $tr->id); ?>"><?php echo htmlspecialchars($tr->title); ?></a>
                    <div class="tw-text-xs tw-text-neutral-500">
                        <?php echo _d($tr->start_date); ?><?php if (!empty($tr->start_time)): ?> &mdash; <?php echo date('g:i A', strtotime($tr->start_time)); ?><?php endif; ?>
                    </div>
                </div>
                <?php if ($tr->status === 'in_progress'): ?>
                <span class="label label-success">In Progress</span>
                <?php else: ?>
                <span class="label label-info">Scheduled</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="tw-text-center tw-py-2 tw-text-neutral-500">
                <i class="fa fa-check tw-mr-1"></i> No upcoming training.
            </div>
            <?php endif; ?>
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
