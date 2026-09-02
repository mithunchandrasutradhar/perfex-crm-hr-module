<?php defined('BASEPATH') or exit('No direct script access allowed');
$is_edit = isset($branch) && $branch;
$b       = $is_edit ? $branch : (object)[];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/branches'); ?>"><?php echo _l('hr_menu_branches'); ?></a></li>
          <li class="active"><?php echo $title; ?></li>
        </ol>
      </div>
    </div>

    <?php echo form_open(current_url(), ['id' => 'branch-form']); ?>
    <div class="row">
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <div class="form-group">
              <label><?php echo _l('hr_name'); ?> <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" required
                value="<?php echo $is_edit ? htmlspecialchars($b->name) : ''; ?>">
            </div>
            <div class="form-group">
              <label><?php echo _l('hr_description'); ?></label>
              <textarea name="description" class="form-control" rows="3"><?php echo $is_edit ? htmlspecialchars($b->description ?? '') : ''; ?></textarea>
            </div>
            <div class="form-group">
              <div class="checkbox checkbox-primary">
                <input type="checkbox" name="status" id="branch_status" value="1"
                  <?php echo (!$is_edit || $b->status == 1) ? 'checked' : ''; ?>>
                <label for="branch_status"><?php echo _l('hr_active'); ?></label>
              </div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-primary"><?php echo _l('hr_save'); ?></button>
          <a href="<?php echo admin_url('hr_module/branches'); ?>" class="btn btn-default"><?php echo _l('hr_cancel'); ?></a>
        </div>
      </div>
    </div>
    <?php echo form_close(); ?>
  </div>
</div>
<?php init_tail(); ?>
