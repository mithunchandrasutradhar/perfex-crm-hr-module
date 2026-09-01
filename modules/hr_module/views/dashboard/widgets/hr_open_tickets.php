<?php defined('BASEPATH') or exit('No direct script access allowed');
// Self-contained, like every module-contributed dashboard widget has to be -
// see hr_attendance.php for why the stats call is shared/cached across the
// 5 "HR ..." widgets.
[$employee_id, $hr_stats] = hr_get_own_employee_dashboard_stats();
?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>"
    data-name="HR Open Tickets">
    <?php if ($employee_id): ?>
    <div class="panel_s">
        <div class="panel-body padding-10">
            <div class="widget-dragger"></div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-1.5">
                <p class="tw-font-semibold tw-flex tw-items-center tw-mb-0 tw-space-x-1.5 rtl:tw-space-x-reverse">
                    <i class="fa fa-ticket-alt tw-text-neutral-500 tw-mr-1"></i>
                    <span class="tw-text-neutral-700">HR Open Tickets</span>
                </p>
                <a href="<?php echo admin_url('hr_module/helpdesk'); ?>">
                    <?php echo _l('home_widget_view_all'); ?>
                </a>
            </div>

            <hr class="-tw-mx-3 tw-mt-2 tw-mb-4">

            <div class="tw-text-center tw-py-2">
                <div class="tw-text-2xl tw-font-bold text-info"><?php echo (int) $hr_stats['open_tickets']; ?></div>
                <div class="tw-text-xs tw-text-neutral-500 tw-mt-1">Open / in-progress tickets</div>
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
