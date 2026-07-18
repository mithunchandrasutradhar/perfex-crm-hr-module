<?php defined('BASEPATH') or exit('No direct script access allowed');
$editing = !empty($contract);
$v = function($field) use ($contract) {
    return $editing ? htmlspecialchars($contract->$field ?? '') : '';
};
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-10 col-md-offset-1">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/hr_contracts'); ?>"><?php echo _l('hr_contract_list'); ?></a></li>
          <?php if ($editing): ?>
          <li><a href="<?php echo admin_url('hr_module/hr_contracts/view/'.$contract->id); ?>"><?php echo htmlspecialchars($contract->title); ?></a></li>
          <?php endif; ?>
          <li class="active"><?php echo $title; ?></li>
        </ol>

        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo $title; ?></h4>
            <?php
            $action = $editing
                ? admin_url('hr_module/hr_contracts/edit/'.$contract->id)
                : admin_url('hr_module/hr_contracts/add');
            echo form_open_multipart($action);
            ?>
              <!-- Row 1 -->
              <div class="row">
                <div class="col-md-8">
                  <div class="form-group">
                    <label><?php echo _l('hr_contract_title'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required
                           value="<?php echo $v('title'); ?>" placeholder="e.g. Employment Agreement – John Doe">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group select-placeholder">
                    <label><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
                    <select name="employee_id" class="selectpicker" data-width="100%" data-live-search="true"
                            data-none-selected-text="<?php echo _l('hr_select'); ?>" required>
                      <option value=""><?php echo _l('hr_select'); ?></option>
                      <?php foreach ($employees as $id => $name): ?>
                      <option value="<?php echo $id; ?>" <?php if($editing && $contract->employee_id==$id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($name); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Row 2 -->
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group select-placeholder">
                    <label><?php echo _l('hr_contract_type'); ?> <span class="text-danger">*</span></label>
                    <select name="contract_type" class="selectpicker" data-width="100%" required>
                      <?php
                      $types = ['permanent'=>'Permanent','fixed'=>'Fixed Term','probation'=>'Probation','internship'=>'Internship','casual'=>'Casual'];
                      foreach ($types as $val => $label):
                      ?>
                      <option value="<?php echo $val; ?>" <?php if($editing && $contract->contract_type==$val) echo 'selected'; ?>>
                        <?php echo $label; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group select-placeholder">
                    <label><?php echo _l('hr_status'); ?></label>
                    <select name="status" class="selectpicker" data-width="100%">
                      <?php
                      $statuses = ['active'=>'Active','pending'=>'Pending','expired'=>'Expired','terminated'=>'Terminated'];
                      foreach ($statuses as $val => $label):
                      ?>
                      <option value="<?php echo $val; ?>" <?php if($editing && $contract->status==$val) echo 'selected'; elseif(!$editing && $val=='active') echo 'selected'; ?>>
                        <?php echo $label; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label><?php echo _l('hr_start_date'); ?> <span class="text-danger">*</span></label>
                    <div class="input-group date">
                      <input type="text" name="start_date" class="form-control datepicker" autocomplete="off" required
                             value="<?php echo $editing ? _d($contract->start_date) : ''; ?>">
                      <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label><?php echo _l('hr_end_date'); ?> <small class="text-muted">(leave blank = open-ended)</small></label>
                    <div class="input-group date">
                      <input type="text" name="end_date" class="form-control datepicker" autocomplete="off"
                             value="<?php echo ($editing && $contract->end_date) ? _d($contract->end_date) : ''; ?>">
                      <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Row 3 -->
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_contract_value'); ?> <small class="text-muted">(contract amount / salary)</small></label>
                    <input type="number" name="value" class="form-control" min="0" step="0.01"
                           value="<?php echo $v('value'); ?>" placeholder="0.00">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_contract_signed'); ?></label>
                    <div class="checkbox checkbox-primary">
                      <input type="hidden" name="signed" value="0">
                      <input type="checkbox" name="signed" id="contract_signed" value="1"
                             <?php if($editing && $contract->signed) echo 'checked'; ?>>
                      <label for="contract_signed">Contract has been signed</label>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group" id="signed-date-group" <?php if(!$editing || !$contract->signed) echo 'style="display:none"'; ?>>
                    <label><?php echo _l('hr_contract_sign_date'); ?></label>
                    <div class="input-group date">
                      <input type="text" name="signed_date" class="form-control datepicker" autocomplete="off"
                             value="<?php echo ($editing && $contract->signed_date) ? _d($contract->signed_date) : ''; ?>">
                      <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Contract Content -->
              <div class="form-group">
                <label><?php echo _l('hr_contract_content'); ?></label>
                <textarea name="content" id="contract-content" class="form-control" rows="12"
                          placeholder="Enter the full contract terms and conditions..."><?php echo $editing ? htmlspecialchars($contract->content ?? '') : ''; ?></textarea>
              </div>

              <!-- Attachment & Notes -->
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Attachment <small class="text-muted">(PDF/DOC/DOCX, max 10MB)</small></label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx">
                    <?php if ($editing && $contract->attachment): ?>
                    <small class="text-muted">
                      Current: <a href="<?php echo base_url('uploads/hr_module/contracts/'.$contract->attachment); ?>" target="_blank">
                        <?php echo $contract->attachment; ?>
                      </a> (upload new to replace)
                    </small>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Internal Notes</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="Any internal notes about this contract..."><?php echo $editing ? htmlspecialchars($contract->notes ?? '') : ''; ?></textarea>
                  </div>
                </div>
              </div>

              <div class="tw-flex tw-gap-2 tw-mt-4">
                <button type="submit" class="btn btn-primary">
                  <?php echo $editing ? _l('hr_save_changes') : _l('hr_contract_add'); ?>
                </button>
                <a href="<?php echo admin_url('hr_module/hr_contracts'.($editing ? '/view/'.$contract->id : '')); ?>" class="btn btn-default">
                  <?php echo _l('hr_cancel'); ?>
                </a>
              </div>

            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    // Show/hide signed date when checkbox toggled
    $('input[name="signed"][type="checkbox"]').on('change', function(){
        $('#signed-date-group').toggle(this.checked);
        if (!this.checked) $('input[name="signed_date"]').val('');
    });
});
</script>
