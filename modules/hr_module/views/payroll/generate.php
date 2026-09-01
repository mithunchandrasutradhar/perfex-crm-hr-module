<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/payroll'); ?>"><?php echo _l('hr_payroll_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_payroll_generate'); ?></li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo _l('hr_payroll_generate'); ?></h4>
            <?php echo form_open(admin_url('hr_module/payroll/generate'), ['id'=>'generateForm']); ?>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group select-placeholder">
                    <label><?php echo _l('hr_payroll_month'); ?> <span class="text-danger">*</span></label>
                    <select name="pay_month" class="selectpicker" data-width="100%" required>
                      <?php for($m=1;$m<=12;$m++): ?>
                      <option value="<?php echo $m; ?>" <?php if(date('n')==$m) echo 'selected'; ?>><?php echo date('F',mktime(0,0,0,$m,1)); ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group select-placeholder">
                    <label><?php echo _l('hr_payroll_year'); ?> <span class="text-danger">*</span></label>
                    <select name="pay_year" class="selectpicker" data-width="100%" required>
                      <?php for($y=date('Y');$y>=date('Y')-2;$y--): ?>
                      <option value="<?php echo $y; ?>" <?php if(date('Y')==$y) echo 'selected'; ?>><?php echo $y; ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label><?php echo _l('hr_employees'); ?> <span class="text-danger">*</span></label>
                <div class="tw-flex tw-gap-2 tw-mb-2">
                  <button type="button" class="btn btn-default btn-xs" id="sel-all">Select All</button>
                  <button type="button" class="btn btn-default btn-xs" id="desel-all">Deselect All</button>
                </div>
                <div style="max-height:300px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;padding:8px">
                  <?php foreach ($employees as $emp): ?>
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="employee_ids[]" id="payroll_emp_<?php echo $emp->id; ?>" value="<?php echo $emp->id; ?>" checked>
                    <label for="payroll_emp_<?php echo $emp->id; ?>">
                      <strong><?php echo htmlspecialchars($emp->first_name.' '.$emp->last_name); ?></strong>
                      <span class="text-muted">(<?php echo $emp->employee_code; ?>)</span>
                      — <?php echo htmlspecialchars($emp->department_name ?? ''); ?>
                      &nbsp;|&nbsp; Basic: <?php echo number_format($emp->basic_salary,2); ?>
                    </label>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="form-group">
                <label><?php echo _l('hr_notes'); ?></label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
              </div>

              <div class="alert alert-info tw-text-sm">
                <i class="fa fa-info-circle tw-mr-1"></i>
                Payroll is calculated from employee basic salary + active payroll items (allowances/deductions).
                Attendance data, approved overduty, and pending loan installments are included automatically.
                Employees with payroll already generated for the selected period will be skipped.
              </div>

              <div class="tw-flex tw-gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-cogs tw-mr-1"></i>Generate Payroll</button>
                <a href="<?php echo admin_url('hr_module/payroll'); ?>" class="btn btn-default">Cancel</a>
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
    $('#sel-all').on('click',   function(){ $('input[name="employee_ids[]"]').prop('checked', true); });
    $('#desel-all').on('click', function(){ $('input[name="employee_ids[]"]').prop('checked', false); });
});
</script>
