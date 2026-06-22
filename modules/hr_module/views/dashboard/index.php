<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-4">
                    <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                        <?php echo _l('hr_dashboard_title'); ?>
                    </h4>
                </div>
            </div>
        </div>

        <!-- KPI Cards Row 1 -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <a href="<?php echo admin_url('hr_module/employees'); ?>" class="block-link">
                    <div class="panel_s hr-stat-card">
                        <div class="panel-body tw-p-4 tw-flex tw-items-center tw-gap-4">
                            <div class="hr-stat-icon bg-info">
                                <i class="fa fa-users fa-2x tw-text-white"></i>
                            </div>
                            <div>
                                <div class="tw-text-2xl tw-font-bold tw-text-neutral-800">
                                    <?php echo $stats['total_employees']; ?>
                                </div>
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
                                <div class="tw-text-2xl tw-font-bold tw-text-neutral-800">
                                    <?php echo $stats['active_employees']; ?>
                                </div>
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
                                <div class="tw-text-2xl tw-font-bold tw-text-neutral-800">
                                    <?php echo $stats['total_departments']; ?>
                                </div>
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
                                <div class="tw-text-2xl tw-font-bold tw-text-neutral-800">
                                    <?php echo $stats['present_today']; ?>
                                </div>
                                <div class="tw-text-sm tw-text-neutral-500"><?php echo _l('hr_dashboard_present_today'); ?></div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- KPI Cards Row 2 -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <a href="<?php echo admin_url('hr_module/leave'); ?>" class="block-link">
                    <div class="panel_s hr-stat-card">
                        <div class="panel-body tw-p-4 tw-flex tw-items-center tw-gap-4">
                            <div class="hr-stat-icon bg-danger">
                                <i class="fa fa-calendar-times fa-2x tw-text-white"></i>
                            </div>
                            <div>
                                <div class="tw-text-2xl tw-font-bold tw-text-neutral-800">
                                    <?php echo $stats['on_leave_today']; ?>
                                </div>
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
                                <div class="tw-text-2xl tw-font-bold tw-text-neutral-800">
                                    <?php echo $stats['pending_leaves']; ?>
                                </div>
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
                                <div class="tw-text-2xl tw-font-bold tw-text-neutral-800">
                                    <?php echo $stats['pending_loans']; ?>
                                </div>
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
                                <div class="tw-text-2xl tw-font-bold tw-text-neutral-800">
                                    <?php echo $stats['pending_overtime']; ?>
                                </div>
                                <div class="tw-text-sm tw-text-neutral-500"><?php echo _l('hr_dashboard_pending_overtime'); ?></div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="tw-font-semibold tw-mb-4"><?php echo _l('hr_dashboard_quick_actions'); ?></h5>
                        <div class="tw-flex tw-flex-wrap tw-gap-2">
                            <?php if (staff_can('create', 'hr_employees')): ?>
                            <a href="<?php echo admin_url('hr_module/employees/add'); ?>" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> <?php echo _l('hr_employee_add'); ?>
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
    </div>
</div>

<style>
.block-link { text-decoration: none; color: inherit; }
.block-link:hover .panel_s { box-shadow: 0 4px 12px rgba(0,0,0,.1); transform: translateY(-1px); transition: all .2s; }
.hr-stat-card { margin-bottom: 16px; border-radius: 8px; overflow: hidden; }
.hr-stat-icon {
    width: 56px; height: 56px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
</style>

<?php init_tail(); ?>
