<?php defined('BASEPATH') or exit('No direct script access allowed');
$is_edit  = !empty($overtime);
$form_url = $is_edit
    ? admin_url('hr_module/overtime/edit/' . $overtime->id)
    : admin_url('hr_module/overtime/request');
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/overtime'); ?>"><?php echo _l('hr_overtime_list'); ?></a></li>
          <?php if ($is_edit): ?>
          <li><a href="<?php echo admin_url('hr_module/overtime/view/'.$overtime->id); ?>">#<?php echo $overtime->id; ?></a></li>
          <?php endif; ?>
          <li class="active"><?php echo $is_edit ? _l('hr_overtime_edit') : _l('hr_overtime_add'); ?></li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo $is_edit ? _l('hr_overtime_edit') : _l('hr_overtime_add'); ?></h4>
            <?php echo form_open($form_url, ['id' => 'overtimeForm']); ?>
              <div class="row">
                <div class="col-md-8">
                  <div class="form-group">
                    <label><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
                    <select name="employee_id" id="ot_emp" class="form-control" required>
                      <option value=""><?php echo _l('hr_select'); ?></option>
                      <?php foreach ($employees as $id => $name): ?>
                      <option value="<?php echo $id; ?>"
                        <?php if ($is_edit && $overtime->employee_id == $id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($name); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_overtime_date'); ?> <span class="text-danger">*</span></label>
                    <input type="date" name="overtime_date" class="form-control" required
                           value="<?php echo $is_edit ? $overtime->overtime_date : date('Y-m-d'); ?>">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_overtime_hours'); ?> <span class="text-danger">*</span></label>
                    <input type="number" step="0.5" min="0.5" max="24" name="hours" id="ot_hours"
                           class="form-control" required
                           value="<?php echo $is_edit ? $overtime->hours : '2'; ?>">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_overtime_rate'); ?></label>
                    <select name="rate_multiplier" id="ot_rate" class="form-control">
                      <?php foreach ([1.0=>'1x (Normal)',1.5=>'1.5x (Standard)',2.0=>'2x (Double)',2.5=>'2.5x'] as $v=>$l): ?>
                      <option value="<?php echo $v; ?>" <?php if($is_edit && $overtime->rate_multiplier==$v) echo 'selected'; elseif(!$is_edit && $v==1.5) echo 'selected'; ?>><?php echo $l; ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_overtime_total_amount'); ?></label>
                    <input type="text" id="ot_preview" class="form-control" readonly
                           style="background:#f8fafc"
                           value="<?php echo $is_edit ? number_format($overtime->total_amount,2) : ''; ?>">
                    <small class="text-muted">Auto-calculated from basic salary</small>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_overtime_reason'); ?></label>
                <textarea name="reason" class="form-control" rows="3"
                          placeholder="Work description for this overtime..."><?php echo $is_edit ? htmlspecialchars($overtime->reason) : ''; ?></textarea>
              </div>
              <div class="tw-flex tw-gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-save tw-mr-1"></i><?php echo $is_edit ? _l('hr_save') : 'Submit Request'; ?>
                </button>
                <a href="<?php echo $is_edit ? admin_url('hr_module/overtime/view/'.$overtime->id) : admin_url('hr_module/overtime'); ?>" class="btn btn-default">Cancel</a>
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
    // Preview estimated amount via AJAX-free calculation
    // basic_salary stored per employee — we fetch it for preview only
    var empSalaries = {};
    function updatePreview(){
        var eid   = $('#ot_emp').val();
        var hours = parseFloat($('#ot_hours').val()) || 0;
        var rate  = parseFloat($('#ot_rate').val()) || 1.5;
        if (!eid || !hours) { $('#ot_preview').val(''); return; }
        if (empSalaries[eid] !== undefined) {
            var hourly = empSalaries[eid] / (26 * 8);
            $('#ot_preview').val((hourly * hours * rate).toFixed(2));
        } else {
            $.getJSON('<?php echo admin_url('hr_module/employees/view/'); ?>'+eid+'?json=1', function(d){
                if (d && d.basic_salary) {
                    empSalaries[eid] = parseFloat(d.basic_salary);
                    var hourly = empSalaries[eid] / (26 * 8);
                    $('#ot_preview').val((hourly * hours * rate).toFixed(2));
                }
            });
        }
    }
    $('#ot_emp, #ot_hours, #ot_rate').on('change input', updatePreview);
    updatePreview();
});
</script>
