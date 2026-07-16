<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $evaluators */
if (!isset($evaluators)) $evaluators = [];
$is_edit  = !empty($sub_target);
$form_url = $is_edit
    ? admin_url('hr_module/performance/edit_sub_target/' . $sub_target->id)
    : admin_url('hr_module/performance/add_sub_target/' . $target->id);
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/performance'); ?>"><?php echo _l('hr_performance_list'); ?></a></li>
          <li><a href="<?php echo admin_url('hr_module/performance/view/'.$target->id); ?>">#<?php echo $target->id; ?> - <?php echo htmlspecialchars($target->title); ?></a></li>
          <li class="active"><?php echo $is_edit ? _l('hr_performance_edit_sub_target') : _l('hr_performance_add_sub_target'); ?></li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo $is_edit ? _l('hr_performance_edit_sub_target') : _l('hr_performance_add_sub_target'); ?></h4>
            <?php echo form_open($form_url); ?>
              <div class="row">
                <div class="col-md-8">
                  <div class="form-group">
                    <label><?php echo _l('hr_performance_sub_target_title'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required
                           value="<?php echo $is_edit ? htmlspecialchars($sub_target->title) : ''; ?>">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_performance_due_date'); ?></label>
                    <input type="date" name="due_date" class="form-control"
                           value="<?php echo $is_edit ? $sub_target->due_date : ''; ?>">
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_performance_task_description'); ?></label>
                <textarea name="description" class="form-control" rows="3"><?php echo $is_edit ? htmlspecialchars($sub_target->description) : ''; ?></textarea>
              </div>
              <div class="form-group select-placeholder">
                <label for="evaluator_ids"><?php echo _l('hr_performance_evaluators'); ?></label>
                <select name="evaluator_ids[]" id="evaluator_ids" class="selectpicker" multiple
                        data-width="100%" data-live-search="true" data-actions-box="true"
                        data-none-selected-text="<?php echo _l('hr_select'); ?>">
                  <?php foreach ($staff as $id => $name): ?>
                  <option value="<?php echo $id; ?>" <?php if (in_array($id, $evaluators)) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($name); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-muted"><?php echo _l('hr_performance_evaluators_hint'); ?></small>
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
