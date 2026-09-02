<?php
defined('BASEPATH') or exit('No direct script access allowed');
$can_edit = staff_can('edit', 'hr_settings') || is_admin();
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
                    <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                        <?php echo _l('hr_module_settings'); ?>
                    </h4>
                    <div class="tw-flex tw-gap-2">
                        <a href="<?php echo admin_url('hr_module/email_templates'); ?>" class="btn btn-default btn-sm">
                            <i class="fa-regular fa-envelope tw-mr-1"></i><?php echo _l('hr_email_templates'); ?>
                        </a>
                        <a href="<?php echo admin_url('hr_module/whatsapp_templates'); ?>" class="btn btn-default btn-sm">
                            <i class="fa-brands fa-whatsapp tw-mr-1"></i><?php echo _l('hr_whatsapp_templates'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <form id="hr-settings-form" method="post">
            <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>

            <!-- General Settings -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="tw-font-semibold tw-border-b tw-pb-2 tw-mb-4">
                                <i class="fa fa-cog tw-mr-2"></i><?php echo _l('hr_settings_general'); ?>
                            </h5>
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_employee_id_prefix'); ?></label>
                                        <input type="text" name="employee_id_prefix" class="form-control"
                                            value="<?php echo isset($settings['employee_id_prefix']) ? htmlspecialchars($settings['employee_id_prefix']) : 'EMP'; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_currency'); ?></label>
                                        <input type="text" name="currency" class="form-control"
                                            value="<?php echo isset($settings['currency']) ? htmlspecialchars($settings['currency']) : 'BDT'; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group select-placeholder">
                                        <label><?php echo _l('hr_settings_fiscal_year_start'); ?></label>
                                        <select name="fiscal_year_start_month" class="selectpicker" data-width="100%" <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <?php
                                            $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                                            $current_month = isset($settings['fiscal_year_start_month']) ? (int)$settings['fiscal_year_start_month'] : 1;
                                            foreach ($months as $i => $m):
                                                $val = $i + 1;
                                            ?>
                                            <option value="<?php echo $val; ?>" <?php echo $current_month === $val ? 'selected' : ''; ?>>
                                                <?php echo $m; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_payroll_day'); ?></label>
                                        <input type="number" name="payroll_generation_day" class="form-control" min="1" max="31"
                                            value="<?php echo isset($settings['payroll_generation_day']) ? (int)$settings['payroll_generation_day'] : 25; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group tw-mb-0">
                                        <label><?php echo _l('hr_settings_default_max_loan_amount'); ?> <i class="fa-solid fa-circle-info tw-text-neutral-400" data-toggle="tooltip" data-title="<?php echo _l('hr_settings_default_max_loan_amount_hint'); ?>" style="cursor:help;"></i></label>
                                        <input type="number" name="default_max_loan_amount" class="form-control" step="0.01" min="0" max="99999999.99"
                                            value="<?php echo isset($settings['default_max_loan_amount']) ? $settings['default_max_loan_amount'] : ''; ?>"
                                            placeholder="99999999.99"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <?php if (staff_can('view', 'hr_departments')): ?>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group tw-mb-0">
                                        <label><?php echo _l('hr_settings_company_structure'); ?></label>
                                        <a href="<?php echo admin_url('hr_module/designations'); ?>" class="btn btn-default btn-sm btn-block">
                                            <i class="fa fa-id-badge tw-mr-1"></i><?php echo _l('hr_menu_designations'); ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group tw-mb-0">
                                        <label>&nbsp;</label>
                                        <a href="<?php echo admin_url('hr_module/branches'); ?>" class="btn btn-default btn-sm btn-block">
                                            <i class="fa fa-code-branch tw-mr-1"></i><?php echo _l('hr_menu_branches'); ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Settings -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="tw-font-semibold tw-border-b tw-pb-2 tw-mb-4">
                                <i class="fa fa-clock tw-mr-2"></i><?php echo _l('hr_settings_attendance'); ?>
                            </h5>
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_working_days'); ?></label>
                                        <input type="number" name="working_days_per_week" class="form-control" min="1" max="7"
                                            value="<?php echo isset($settings['working_days_per_week']) ? (int)$settings['working_days_per_week'] : 5; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_working_hours'); ?></label>
                                        <input type="number" name="working_hours_per_day" class="form-control" min="1" max="24"
                                            value="<?php echo isset($settings['working_hours_per_day']) ? (int)$settings['working_hours_per_day'] : 8; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_office_start_time'); ?></label>
                                        <input type="time" name="office_start_time" class="form-control"
                                            value="<?php echo isset($settings['office_start_time']) ? htmlspecialchars($settings['office_start_time']) : '09:00'; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_office_end_time'); ?></label>
                                        <input type="time" name="office_end_time" class="form-control"
                                            value="<?php echo isset($settings['office_end_time']) ? htmlspecialchars($settings['office_end_time']) : '18:00'; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_late_threshold'); ?></label>
                                        <input type="number" name="late_threshold_minutes" class="form-control" min="0" max="120"
                                            value="<?php echo isset($settings['late_threshold_minutes']) ? (int)$settings['late_threshold_minutes'] : 15; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_overtime_rate'); ?></label>
                                        <input type="number" name="default_overtime_rate" class="form-control" step="0.1" min="1" max="5"
                                            value="<?php echo isset($settings['default_overtime_rate']) ? $settings['default_overtime_rate'] : '1.5'; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_overtime_holiday_rate'); ?></label>
                                        <input type="number" name="overtime_holiday_rate" class="form-control" step="0.1" min="1" max="5"
                                            value="<?php echo isset($settings['overtime_holiday_rate']) ? $settings['overtime_holiday_rate'] : '2.0'; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_overtime_day_divisor'); ?> <i class="fa-solid fa-circle-info tw-text-neutral-400" data-toggle="tooltip" data-title="<?php echo _l('hr_settings_overtime_day_divisor_hint'); ?>" style="cursor:help;"></i></label>
                                        <input type="number" name="overtime_day_divisor" class="form-control" step="1" min="1" max="31"
                                            value="<?php echo isset($settings['overtime_day_divisor']) ? $settings['overtime_day_divisor'] : '26'; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shift Types -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <div class="tw-flex tw-items-center tw-justify-between tw-border-b tw-pb-2 tw-mb-4">
                                <h5 class="tw-font-semibold tw-mb-0">
                                    <i class="fa fa-user-clock tw-mr-2"></i><?php echo _l('hr_settings_shift_types'); ?>
                                </h5>
                                <?php if ($can_edit): ?>
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addShiftModal">
                                    <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_shift_add'); ?>
                                </button>
                                <?php endif; ?>
                            </div>

                            <?php if (empty($shift_types)): ?>
                            <p class="text-muted tw-mb-0"><?php echo _l('hr_shift_none_added'); ?></p>
                            <?php else: ?>
                            <table class="table table-condensed tw-mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('hr_shift_name'); ?></th>
                                        <th><?php echo _l('hr_shift_start_time'); ?></th>
                                        <th><?php echo _l('hr_shift_end_time'); ?></th>
                                        <th style="width:60px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $time_fmt = (get_option('time_format') == 24) ? 'H:i' : 'g:i A'; ?>
                                    <?php foreach ($shift_types as $s): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s->name); ?></td>
                                        <td><?php echo date($time_fmt, strtotime($s->start_time)); ?></td>
                                        <td><?php echo date($time_fmt, strtotime($s->end_time)); ?></td>
                                        <td>
                                            <?php if ($can_edit): ?>
                                            <a href="#" class="hr-edit-shift tw-mr-2"
                                                data-id="<?php echo $s->id; ?>"
                                                data-name="<?php echo htmlspecialchars($s->name, ENT_QUOTES); ?>"
                                                data-start="<?php echo date('H:i', strtotime($s->start_time)); ?>"
                                                data-end="<?php echo date('H:i', strtotime($s->end_time)); ?>"
                                                title="<?php echo _l('hr_edit'); ?>"><i class="fa fa-pencil"></i></a>
                                            <a href="<?php echo admin_url('hr_module/settings/delete_shift/' . $s->id); ?>" class="_delete text-danger" title="<?php echo _l('hr_delete'); ?>"><i class="fa fa-trash"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>

                            <hr>
                            <p class="tw-text-sm tw-font-semibold tw-mb-2">
                                <?php echo _l('hr_settings_shift_allowances_title'); ?>
                                <i class="fa-solid fa-circle-info tw-text-neutral-400" data-toggle="tooltip" data-title="<?php echo _l('hr_settings_shift_allowance_hint'); ?>" style="cursor:help;"></i>
                            </p>
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group tw-mb-0">
                                        <label><?php echo _l('hr_settings_shift_allowance_evening'); ?></label>
                                        <input type="number" name="shift_allowance_evening_amount" class="form-control" step="0.01" min="0"
                                            value="<?php echo isset($settings['shift_allowance_evening_amount']) ? $settings['shift_allowance_evening_amount'] : '0'; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group tw-mb-0">
                                        <label><?php echo _l('hr_settings_shift_allowance_night'); ?></label>
                                        <input type="number" name="shift_allowance_night_amount" class="form-control" step="0.01" min="0"
                                            value="<?php echo isset($settings['shift_allowance_night_amount']) ? $settings['shift_allowance_night_amount'] : '0'; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Devices Settings (ZKTeco + AiFace) -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="tw-font-semibold tw-border-b tw-pb-2 tw-mb-4">
                                <i class="fa fa-microchip tw-mr-2"></i><?php echo _l('hr_zkteco_devices'); ?>
                            </h5>
                            <div class="row">
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-primary">
                                            <input type="checkbox" name="zkteco_enabled" id="setting_zkteco_enabled" value="1"
                                                <?php echo isset($settings['zkteco_enabled']) && $settings['zkteco_enabled'] == '1' ? 'checked' : ''; ?>
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <label for="setting_zkteco_enabled"><?php echo _l('hr_settings_zkteco_enabled'); ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-primary">
                                            <input type="checkbox" name="aiface_enabled" id="setting_aiface_enabled" value="1"
                                                <?php echo isset($settings['aiface_enabled']) && $settings['aiface_enabled'] == '1' ? 'checked' : ''; ?>
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <label for="setting_aiface_enabled"><?php echo _l('hr_settings_aiface_enabled'); ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="tw-block">&nbsp;</label>
                                        <a href="<?php echo admin_url('hr_module/devices'); ?>" class="btn btn-default btn-sm">
                                            <i class="fa fa-microchip"></i> <?php echo _l('hr_zkteco_devices'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="tw-font-semibold tw-border-b tw-pb-2 tw-mb-4">
                                <i class="fa fa-bell tw-mr-2"></i><?php echo _l('hr_settings_notifications'); ?>
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_notification_email'); ?> <i class="fa-solid fa-circle-info tw-text-neutral-400" data-toggle="tooltip" data-title="<?php echo _l('hr_settings_notification_email_hint'); ?>" style="cursor:help;"></i></label>
                                        <input type="email" name="hr_notification_email" class="form-control"
                                            value="<?php echo isset($settings['hr_notification_email']) ? htmlspecialchars($settings['hr_notification_email']) : ''; ?>"
                                            placeholder="hr@example.com"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_policy_approver'); ?> <i class="fa-solid fa-circle-info tw-text-neutral-400" data-toggle="tooltip" data-title="<?php echo _l('hr_settings_policy_approver_hint'); ?>" style="cursor:help;"></i></label>
                                        <select name="policy_approver_ids[]" multiple class="form-control selectpicker" data-live-search="true" data-actions-box="true"
                                            <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <?php
                                                $cur_approvers = isset($settings['policy_approver_ids']) && $settings['policy_approver_ids'] !== ''
                                                    ? array_map('intval', explode(',', $settings['policy_approver_ids']))
                                                    : [];
                                            ?>
                                            <?php foreach ($admin_staff as $s): ?>
                                            <option value="<?php echo $s->staffid; ?>" <?php echo in_array((int) $s->staffid, $cur_approvers, true) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($s->firstname . ' ' . $s->lastname); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <?php
                                $notif_settings = [
                                    'notify_leave_apply'        => _l('hr_settings_notify_leave_apply'),
                                    'notify_leave_approve'       => _l('hr_settings_notify_leave_approve'),
                                    'notify_leave_cancellation'  => _l('hr_settings_notify_leave_cancellation'),
                                    'notify_loan_apply'          => _l('hr_settings_notify_loan_apply'),
                                    'notify_loan_approve'        => _l('hr_settings_notify_loan_approve'),
                                    'notify_loan_deduction'      => _l('hr_settings_notify_loan_deduction'),
                                    'notify_overtime'            => _l('hr_settings_notify_overtime'),
                                    'notify_helpdesk'            => _l('hr_settings_notify_helpdesk'),
                                    'notify_shift'               => _l('hr_settings_notify_shift'),
                                    'notify_policy'              => _l('hr_settings_notify_policy'),
                                    'notify_training'            => _l('hr_settings_notify_training'),
                                    'notify_payroll'             => _l('hr_settings_notify_payroll'),
                                ];
                                foreach ($notif_settings as $key => $label):
                                    // Defaults to checked when never saved, matching
                                    // Hr_module_model::notifications_enabled()'s "enabled
                                    // unless explicitly turned off" default.
                                    $checked = (!isset($settings[$key]) || $settings[$key] == '1') ? 'checked' : '';
                                ?>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-primary">
                                            <input type="checkbox" name="<?php echo $key; ?>" id="setting_<?php echo $key; ?>" value="1" <?php echo $checked; ?>
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <label for="setting_<?php echo $key; ?>"><?php echo $label; ?></label>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Notifications -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="tw-font-semibold tw-border-b tw-pb-2 tw-mb-4">
                                <i class="fa-brands fa-whatsapp tw-mr-2"></i><?php echo _l('hr_settings_whatsapp'); ?>
                            </h5>
                            <p class="text-muted tw-mb-3" style="font-size:0.85rem"><?php echo _l('hr_settings_whatsapp_hint'); ?></p>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-primary">
                                            <input type="checkbox" name="whatsapp_enabled" id="setting_whatsapp_enabled" value="1"
                                                <?php echo isset($settings['whatsapp_enabled']) && $settings['whatsapp_enabled'] == '1' ? 'checked' : ''; ?>
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <label for="setting_whatsapp_enabled"><?php echo _l('hr_settings_whatsapp_enabled'); ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="tw-text-sm tw-font-semibold tw-mb-2"><?php echo _l('hr_settings_whatsapp_events_title'); ?></p>
                            <div class="row">
                                <?php
                                $whatsapp_notif_settings = [
                                    'whatsapp_notify_leave_announcement'              => _l('hr_settings_whatsapp_notify_leave_announcement'),
                                    'whatsapp_notify_leave_cancellation_announcement' => _l('hr_settings_whatsapp_notify_leave_cancellation_announcement'),
                                    'whatsapp_notify_holiday_reminder'                => _l('hr_settings_whatsapp_notify_holiday_reminder'),
                                    'whatsapp_notify_policy_announcement'             => _l('hr_settings_whatsapp_notify_policy_announcement'),
                                ];
                                foreach ($whatsapp_notif_settings as $key => $label):
                                    // Defaults to checked when never saved, matching
                                    // send_whatsapp_announcement()'s "enabled unless
                                    // explicitly turned off" default.
                                    $checked = (!isset($settings[$key]) || $settings[$key] == '1') ? 'checked' : '';
                                ?>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-primary">
                                            <input type="checkbox" name="<?php echo $key; ?>" id="setting_<?php echo $key; ?>" value="1" <?php echo $checked; ?>
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <label for="setting_<?php echo $key; ?>"><?php echo $label; ?></label>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="row">
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_whatsapp_base_url'); ?></label>
                                        <input type="text" name="whatsapp_base_url" class="form-control"
                                            value="<?php echo isset($settings['whatsapp_base_url']) ? htmlspecialchars($settings['whatsapp_base_url']) : 'https://waha.abutalha.com.bd'; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_whatsapp_session'); ?></label>
                                        <input type="text" name="whatsapp_session" class="form-control"
                                            value="<?php echo isset($settings['whatsapp_session']) ? htmlspecialchars($settings['whatsapp_session']) : 'default'; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_whatsapp_api_key'); ?></label>
                                        <input type="password" name="whatsapp_api_key" class="form-control" autocomplete="new-password"
                                            placeholder="<?php echo !empty($settings['whatsapp_api_key']) ? '•••••••• (saved — leave blank to keep)' : ''; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_whatsapp_group_id'); ?> <i class="fa-solid fa-circle-info tw-text-neutral-400" data-toggle="tooltip" data-title="<?php echo _l('hr_settings_whatsapp_group_id_hint'); ?>" style="cursor:help;"></i></label>
                                        <input type="text" name="whatsapp_group_id" class="form-control" placeholder="e.g. 1203xxxxxxxxx@g.us"
                                            value="<?php echo isset($settings['whatsapp_group_id']) ? htmlspecialchars($settings['whatsapp_group_id']) : ''; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_whatsapp_phone'); ?> <i class="fa-solid fa-circle-info tw-text-neutral-400" data-toggle="tooltip" data-title="<?php echo _l('hr_settings_whatsapp_phone_hint'); ?>" style="cursor:help;"></i></label>
                                        <input type="text" name="whatsapp_phone_number" class="form-control" placeholder="e.g. 01765447530"
                                            value="<?php echo isset($settings['whatsapp_phone_number']) ? htmlspecialchars($settings['whatsapp_phone_number']) : ''; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <?php if ($can_edit): ?>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group tw-mb-0">
                                        <label class="tw-block">&nbsp;</label>
                                        <button type="button" class="btn btn-default btn-sm" id="btn-whatsapp-test">
                                            <i class="fa fa-paper-plane tw-mr-1"></i><?php echo _l('hr_settings_whatsapp_send_test'); ?>
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Holiday Reminder -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="tw-font-semibold tw-border-b tw-pb-2 tw-mb-4">
                                <i class="fa-regular fa-calendar-check tw-mr-2"></i><?php echo _l('hr_settings_holiday_reminder'); ?>
                            </h5>
                            <p class="text-muted tw-mb-3" style="font-size:0.85rem"><?php echo _l('hr_settings_holiday_reminder_hint'); ?></p>
                            <div class="row">
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <div class="checkbox checkbox-primary">
                                            <input type="checkbox" name="holiday_reminder_enabled" id="setting_holiday_reminder_enabled" value="1"
                                                <?php echo isset($settings['holiday_reminder_enabled']) && $settings['holiday_reminder_enabled'] == '1' ? 'checked' : ''; ?>
                                                <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                            <label for="setting_holiday_reminder_enabled"><?php echo _l('hr_settings_holiday_reminder_enabled'); ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_holiday_reminder_time'); ?></label>
                                        <input type="time" name="holiday_reminder_time" class="form-control"
                                            value="<?php echo isset($settings['holiday_reminder_time']) ? htmlspecialchars($settings['holiday_reminder_time']) : '09:00'; ?>"
                                            <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (is_admin()): ?>
            <!-- Danger Zone -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s tw-border tw-border-red-300">
                        <div class="panel-body">
                            <h5 class="tw-font-semibold tw-border-b tw-pb-2 tw-mb-4 tw-text-red-600">
                                <i class="fa fa-exclamation-triangle tw-mr-2"></i><?php echo _l('hr_settings_danger_zone'); ?>
                            </h5>
                            <div class="checkbox checkbox-danger">
                                <input type="checkbox" name="allow_data_removal_on_uninstall" id="setting_allow_data_removal_on_uninstall" value="1"
                                    <?php echo isset($settings['allow_data_removal_on_uninstall']) && $settings['allow_data_removal_on_uninstall'] == '1' ? 'checked' : ''; ?>>
                                <label for="setting_allow_data_removal_on_uninstall"><?php echo _l('hr_settings_allow_data_removal'); ?></label>
                            </div>
                            <p class="text-muted tw-mt-2 tw-mb-0"><?php echo _l('hr_settings_allow_data_removal_hint'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($can_edit): ?>
            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary" id="hr-save-settings">
                        <?php echo _l('hr_save'); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Add Shift Modal -->
<?php if ($can_edit): ?>
<div class="modal fade" id="addShiftModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title">
        <span class="shift-add-title"><?php echo _l('hr_shift_add'); ?></span>
        <span class="shift-edit-title hide"><?php echo _l('hr_shift_edit'); ?></span>
      </h4>
    </div>
    <form id="addShiftForm" data-mode="add">
      <input type="hidden" name="id" value="">
      <div class="modal-body">
        <div class="form-group">
          <label><?php echo _l('hr_shift_name'); ?> <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Night Shift" required>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><?php echo _l('hr_shift_start_time'); ?> <span class="text-danger">*</span></label>
              <input type="time" name="start_time" class="form-control" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?php echo _l('hr_shift_end_time'); ?> <span class="text-danger">*</span></label>
              <input type="time" name="end_time" class="form-control" required>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
        <button type="submit" class="btn btn-primary" id="add-shift-submit-btn"><?php echo _l('hr_save'); ?></button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<?php init_tail(); ?>

<script>
$(document).ready(function(){
    var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
    var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';

    $('#hr-settings-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#hr-save-settings').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        var formData = $(this).serialize();
        $.post('<?php echo admin_url('hr_module/settings/save'); ?>', formData, function(resp){
            if (resp.success) {
                alert_float('success', resp.message);
            } else {
                alert_float('danger', resp.message);
            }
        }, 'json').always(function(){
            $btn.prop('disabled', false).html('<?php echo _l('hr_save'); ?>');
        });
    });

    // WhatsApp "Send Test" - uses whatever is currently in the base URL/session/
    // API key/group/phone fields (even if not saved yet), same UX as the Email
    // Templates page's test-send.
    $('#btn-whatsapp-test').on('click', function(){
        var $btn = $(this).prop('disabled', true);
        var originalHtml = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i>');
        var $form = $('#hr-settings-form');
        $.post('<?php echo admin_url('hr_module/settings/send_whatsapp_test'); ?>', {
            base_url: $form.find('[name="whatsapp_base_url"]').val(),
            session:  $form.find('[name="whatsapp_session"]').val(),
            api_key:  $form.find('[name="whatsapp_api_key"]').val(),
            group_id: $form.find('[name="whatsapp_group_id"]').val(),
            phone:    $form.find('[name="whatsapp_phone_number"]').val(),
            [csrfName]: csrfHash
        }, function(r){
            alert_float(r.success ? 'success' : 'danger', r.message);
        }, 'json').always(function(){
            $btn.prop('disabled', false).html(originalHtml);
        });
    });

    // Shift Types (independent AJAX - not part of the settings form submit above)
    // Reset happens on close (not open) so edit_shift's pre-fill below isn't
    // wiped out by this handler firing after .modal('show').
    $('#addShiftModal').on('hidden.bs.modal', function(){
        var $form = $('#addShiftForm');
        $form[0].reset();
        $form.attr('data-mode', 'add');
        $form.find('input[name="id"]').val('');
        $('.shift-add-title').removeClass('hide');
        $('.shift-edit-title').addClass('hide');
    });
    $('#addShiftModal').on('shown.bs.modal', function(){
        $('#addShiftForm input[name="name"]').focus();
    });

    $(document).on('click', '.hr-edit-shift', function(e){
        e.preventDefault();
        var $form = $('#addShiftForm');
        $form.attr('data-mode', 'edit');
        $form.find('input[name="id"]').val($(this).data('id'));
        $form.find('input[name="name"]').val($(this).data('name'));
        $form.find('input[name="start_time"]').val($(this).data('start'));
        $form.find('input[name="end_time"]').val($(this).data('end'));
        $('.shift-add-title').addClass('hide');
        $('.shift-edit-title').removeClass('hide');
        $('#addShiftModal').modal('show');
    });

    $('#addShiftForm').on('submit', function(e){
        e.preventDefault();
        var $btn  = $('#add-shift-submit-btn').prop('disabled', true);
        var mode  = $(this).attr('data-mode') || 'add';
        var id    = $(this).find('input[name="id"]').val();
        var url   = mode === 'edit'
            ? '<?php echo admin_url('hr_module/settings/edit_shift/'); ?>' + id
            : '<?php echo admin_url('hr_module/settings/add_shift'); ?>';
        var successMsg = mode === 'edit' ? '<?php echo _l('hr_shift_updated'); ?>' : '<?php echo _l('hr_shift_add'); ?>';
        $.post(url, $(this).serialize() + '&' + csrfName + '=' + csrfHash, function(r){
            if (r.success) {
                alert_float('success', successMsg);
                location.reload();
            } else {
                alert_float('danger', r.message || 'Error saving shift.');
            }
        }, 'json').fail(function(){
            alert_float('danger', 'Unexpected error saving shift.');
        }).always(function(){
            $btn.prop('disabled', false);
        });
    });
    // Shift type deletion uses the `_delete` class (bound globally in app.js) -
    // no custom handler needed here.
});
</script>
