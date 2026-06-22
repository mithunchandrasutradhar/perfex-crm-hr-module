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
                </div>
            </div>
        </div>

        <form id="hr-settings-form">
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
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_fiscal_year_start'); ?></label>
                                        <select name="fiscal_year_start_month" class="form-control" <?php echo !$can_edit ? 'disabled' : ''; ?>>
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
                                <?php
                                $notif_settings = [
                                    'notify_leave_apply'   => _l('hr_settings_notify_leave_apply'),
                                    'notify_leave_approve' => _l('hr_settings_notify_leave_approve'),
                                    'notify_loan_apply'    => _l('hr_settings_notify_loan_apply'),
                                    'notify_payroll'       => _l('hr_settings_notify_payroll'),
                                ];
                                foreach ($notif_settings as $key => $label):
                                    $checked = isset($settings[$key]) && $settings[$key] == '1' ? 'checked' : '';
                                ?>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="<?php echo $key; ?>" value="1" <?php echo $checked; ?>
                                                    <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                                <?php echo $label; ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ZKTeco Settings -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="tw-font-semibold tw-border-b tw-pb-2 tw-mb-4">
                                <i class="fa fa-fingerprint tw-mr-2"></i><?php echo _l('hr_settings_zkteco'); ?>
                            </h5>
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="zkteco_enabled" value="1"
                                                    <?php echo isset($settings['zkteco_enabled']) && $settings['zkteco_enabled'] == '1' ? 'checked' : ''; ?>
                                                    <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                                <?php echo _l('hr_settings_zkteco_enabled'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php echo _l('hr_settings_zkteco_sync_interval'); ?></label>
                                        <input type="number" name="zkteco_sync_interval" class="form-control" min="5" max="1440"
                                            value="<?php echo isset($settings['zkteco_sync_interval']) ? (int)$settings['zkteco_sync_interval'] : 30; ?>"
                                            <?php echo !$can_edit ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 tw-flex tw-items-end">
                                    <div class="form-group">
                                        <a href="<?php echo admin_url('hr_module/zkteco'); ?>" class="btn btn-default btn-sm">
                                            <i class="fa fa-microchip"></i> <?php echo _l('hr_zkteco_devices'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($can_edit): ?>
            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary" id="hr-save-settings">
                        <i class="fa fa-save"></i> <?php echo _l('hr_save'); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
$(document).ready(function(){
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
            $btn.prop('disabled', false).html('<i class="fa fa-save"></i> <?php echo _l('hr_save'); ?>');
        });
    });
});
</script>

<?php init_tail(); ?>
