<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/performance'); ?>"><?php echo _l('hr_performance_list'); ?></a></li>
          <li><a href="<?php echo admin_url('hr_module/performance/view/'.$target->id); ?>">#<?php echo $target->id; ?></a></li>
          <li class="active"><?php echo _l('hr_performance_edit'); ?></li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo _l('hr_performance_edit'); ?></h4>
            <?php echo form_open(admin_url('hr_module/performance/edit/'.$target->id)); ?>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group select-placeholder">
                    <label for="employee_id"><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
                    <select name="employee_id" id="employee_id" class="selectpicker" required
                            data-width="100%" data-live-search="true"
                            data-none-selected-text="<?php echo _l('hr_select'); ?>">
                      <option value=""><?php echo _l('hr_select'); ?></option>
                      <?php foreach ($employees as $id => $name): ?>
                      <option value="<?php echo $id; ?>" <?php if ($target->employee_id == $id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($name); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?php echo _l('hr_performance_due_date'); ?></label>
                    <div class="input-group date">
                      <input type="text" name="due_date" class="form-control datepicker" autocomplete="off" value="<?php echo $target->due_date ? _d($target->due_date) : ''; ?>">
                      <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_performance_target_title'); ?> <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required
                       value="<?php echo htmlspecialchars($target->title); ?>">
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_performance_target_description'); ?></label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($target->description); ?></textarea>
              </div>
              <div class="alert alert-info tw-text-sm">
                <i class="fa fa-info-circle tw-mr-1"></i><?php echo _l('hr_performance_manage_sub_targets_hint'); ?>
              </div>
              <div class="tw-flex tw-gap-2">
                <button type="submit" class="btn btn-primary"><?php echo _l('hr_save'); ?></button>
                <a href="<?php echo admin_url('hr_module/performance/view/'.$target->id); ?>" class="btn btn-default">Cancel</a>
              </div>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
