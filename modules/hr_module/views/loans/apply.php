<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/loans'); ?>"><?php echo _l('hr_loan_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_loan_add'); ?></li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo _l('hr_loan_add'); ?></h4>
            <?php echo form_open_multipart(admin_url('hr_module/loans/apply'), ['id'=>'loanForm']); ?>
              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <label><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-control" required>
                      <option value=""><?php echo _l('hr_select'); ?></option>
                      <?php foreach ($employees as $id => $name): ?>
                      <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_loan_amount'); ?> <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="1" name="amount" id="loan_amount" class="form-control" required>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_loan_repayment_months'); ?> <span class="text-danger">*</span></label>
                    <input type="number" min="1" max="120" name="repayment_months" id="loan_months" class="form-control" required value="12">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_loan_monthly_installment'); ?></label>
                    <input type="text" id="installment_preview" class="form-control" readonly style="background:#f8fafc">
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_loan_reason'); ?></label>
                <textarea name="reason" class="form-control" rows="3" placeholder="Purpose of loan..."></textarea>
              </div>
              <div class="form-group">
                <label>Supporting Document <small class="text-muted">(PDF/JPG/PNG, max 2MB)</small></label>
                <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_notes'); ?></label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
              </div>
              <div class="tw-flex tw-gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane tw-mr-1"></i>Submit Application</button>
                <a href="<?php echo admin_url('hr_module/loans'); ?>" class="btn btn-default">Cancel</a>
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
    function calcInstallment() {
        var amount = parseFloat($('#loan_amount').val()) || 0;
        var months = parseInt($('#loan_months').val()) || 1;
        if (amount > 0 && months > 0) {
            $('#installment_preview').val((amount / months).toFixed(2));
        } else {
            $('#installment_preview').val('');
        }
    }
    $('#loan_amount, #loan_months').on('input', calcInstallment);
});
</script>
