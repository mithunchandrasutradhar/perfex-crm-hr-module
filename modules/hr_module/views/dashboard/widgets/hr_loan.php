<?php defined('BASEPATH') or exit('No direct script access allowed');
// Self-contained, like every module-contributed dashboard widget has to be -
// see hr_attendance.php for why the stats call is shared/cached across the
// 5 "HR ..." widgets.
[$employee_id, $hr_stats] = hr_get_own_employee_dashboard_stats();
$loan = $employee_id ? ($hr_stats['active_loan'] ?? null) : null;
?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>"
    data-name="HR Loan">
    <?php if ($employee_id): ?>
    <div class="panel_s">
        <div class="panel-body padding-10">
            <div class="widget-dragger"></div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-1.5">
                <p class="tw-font-semibold tw-flex tw-items-center tw-mb-0 tw-space-x-1.5 rtl:tw-space-x-reverse">
                    <i class="fa fa-hand-holding-usd tw-text-neutral-500 tw-mr-1"></i>
                    <span class="tw-text-neutral-700">HR Loan</span>
                </p>
                <a href="<?php echo admin_url('hr_module/loans'); ?>">
                    <?php echo _l('home_widget_view_all'); ?>
                </a>
            </div>

            <hr class="-tw-mx-3 tw-mt-2 tw-mb-4">

            <?php if ($loan): ?>
            <div class="tw-text-center tw-py-2">
                <div class="tw-text-2xl tw-font-bold text-danger"><?php echo number_format($loan->outstanding, 2); ?></div>
                <div class="tw-text-xs tw-text-neutral-500 tw-mt-1">Outstanding balance</div>
                <div class="tw-text-xs tw-text-neutral-400 tw-mt-1">
                    <?php echo number_format($loan->monthly_installment, 2); ?> / month &mdash; <?php echo ucfirst($loan->status); ?>
                </div>
            </div>
            <?php else: ?>
            <div class="tw-text-center tw-py-2 tw-text-neutral-500">
                <i class="fa fa-check tw-mr-1"></i> No active loan.
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
