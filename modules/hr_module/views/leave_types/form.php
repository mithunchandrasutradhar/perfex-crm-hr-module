<?php defined('BASEPATH') or exit('No direct script access allowed');
$is_edit = isset($leave_type) && $leave_type;
$t       = $is_edit ? $leave_type : (object)[];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/leave_types'); ?>"><?php echo _l('hr_leave_types_list'); ?></a></li>
          <li class="active"><?php echo $title; ?></li>
        </ol>
      </div>
    </div>

    <?php echo form_open(current_url(), ['id' => 'leave-type-form']); ?>
    <div class="row">
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <div class="row">
              <div class="col-md-8">
                <div class="form-group">
                  <label><?php echo _l('hr_name'); ?> <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control" required
                    value="<?php echo $is_edit ? htmlspecialchars($t->name) : ''; ?>">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label><?php echo _l('hr_leave_max_days'); ?></label>
                  <input type="number" name="days_per_year" class="form-control" min="0"
                    value="<?php echo $is_edit ? (int) $t->days_per_year : 0; ?>">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label><?php echo _l('hr_leave_hours_per_day'); ?></label>
                  <input type="number" name="hours_per_day" class="form-control" min="0.5" max="24" step="0.5"
                    value="<?php echo $is_edit ? $t->hours_per_day : '8.0'; ?>">
                  <p class="help-block tw-text-xs tw-mb-0"><?php echo _l('hr_leave_hours_per_day_hint'); ?></p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label><?php echo _l('hr_leave_carry_forward'); ?> (max days)</label>
                  <input type="number" name="max_carry_forward_days" class="form-control" min="0"
                    value="<?php echo $is_edit ? (int) $t->max_carry_forward_days : 0; ?>">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="carry_forward" id="ltype_carry" value="1"
                      <?php echo ($is_edit && $t->carry_forward == 1) ? 'checked' : ''; ?>>
                    <label for="ltype_carry"><?php echo _l('hr_leave_carry_forward'); ?></label>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="requires_attachment" id="ltype_attach" value="1"
                      <?php echo ($is_edit && $t->requires_attachment == 1) ? 'checked' : ''; ?>>
                    <label for="ltype_attach"><?php echo _l('hr_leave_requires_attachment'); ?></label>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="allow_half_day" id="ltype_half" value="1"
                      <?php echo (!$is_edit || $t->allow_half_day == 1) ? 'checked' : ''; ?>>
                    <label for="ltype_half"><?php echo _l('hr_leave_half_day'); ?></label>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <div class="checkbox checkbox-primary">
                <input type="checkbox" name="is_date_range" id="ltype_range" value="1"
                  <?php echo ($is_edit && $t->is_date_range == 1) ? 'checked' : ''; ?>>
                <label for="ltype_range"><?php echo _l('hr_leave_is_date_range'); ?></label>
              </div>
              <p class="help-block tw-text-xs tw-mb-0"><?php echo _l('hr_leave_is_date_range_hint'); ?></p>
            </div>

            <div class="form-group">
              <label><?php echo _l('hr_description'); ?></label>
              <textarea name="description" class="form-control" rows="2"><?php echo $is_edit ? htmlspecialchars($t->description ?? '') : ''; ?></textarea>
            </div>

            <div class="form-group">
              <div class="checkbox checkbox-primary">
                <input type="checkbox" name="status" id="ltype_status" value="1"
                  <?php echo (!$is_edit || $t->status == 1) ? 'checked' : ''; ?>>
                <label for="ltype_status"><?php echo _l('hr_active'); ?></label>
              </div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-primary"><i class="fa fa-save tw-mr-1"></i><?php echo _l('hr_save'); ?></button>
          <a href="<?php echo admin_url('hr_module/leave_types'); ?>" class="btn btn-default"><?php echo _l('hr_cancel'); ?></a>
        </div>
      </div>
    </div>
    <?php echo form_close(); ?>
  </div>
</div>
<?php init_tail(); ?>
