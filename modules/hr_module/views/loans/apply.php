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

              <div class="form-group">
                <label><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-control" required>
                  <option value=""><?php echo _l('hr_select'); ?></option>
                  <?php foreach ($employees as $id => $name): ?>
                  <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Repayment Calculator -->
              <div class="panel_s tw-mb-4" style="border:1px solid #e2e8f0;background:#f8fafc">
                <div class="panel-body tw-py-3">
                  <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                    <h5 class="tw-font-semibold tw-mb-0">Repayment Terms</h5>
                    <span id="modeLabel" class="label label-default" style="font-size:11px">Auto-calculated</span>
                  </div>
                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label><?php echo _l('hr_loan_amount'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <span class="input-group-addon"><?php echo get_option('currency_symbol') ?: 'BDT'; ?></span>
                          <input type="number" step="0.01" min="1" name="amount" id="loan_amount"
                                 class="form-control" required placeholder="0.00">
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label><?php echo _l('hr_loan_repayment_months'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <input type="number" min="1" max="120" name="repayment_months" id="loan_months"
                                 class="form-control" required value="12">
                          <span class="input-group-addon">mo</span>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>
                          <?php echo _l('hr_loan_monthly_installment'); ?>
                          <span class="text-danger">*</span>
                          <small class="text-muted" style="font-weight:normal"> — editable</small>
                        </label>
                        <div class="input-group">
                          <span class="input-group-addon"><?php echo get_option('currency_symbol') ?: 'BDT'; ?></span>
                          <input type="number" step="0.01" min="0.01" name="monthly_installment"
                                 id="loan_installment" class="form-control" required placeholder="0.00">
                        </div>
                      </div>
                    </div>
                  </div>
                  <div id="calcHint" class="text-muted" style="font-size:11px;margin-top:-8px">
                    <i class="fa fa-info-circle"></i>
                    Change <strong>Months</strong> to auto-calculate the installment, or enter a custom
                    <strong>Monthly Installment</strong> to recalculate the repayment period.
                  </div>
                  <div id="calcSummary" class="alert alert-info tw-mt-3 tw-mb-0 tw-py-2 tw-px-3" style="display:none;font-size:13px"></div>
                </div>
              </div>

              <div class="form-group">
                <label><?php echo _l('hr_loan_reason'); ?></label>
                <textarea name="reason" class="form-control" rows="3"
                          placeholder="Purpose of loan..."></textarea>
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
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-paper-plane tw-mr-1"></i>Submit Application
                </button>
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
$(function () {
    var $amount      = $('#loan_amount');
    var $months      = $('#loan_months');
    var $installment = $('#loan_installment');
    var $summary     = $('#calcSummary');
    var $modeLabel   = $('#modeLabel');

    // Track which field the user last edited to decide direction
    var lastEdited = 'months'; // 'months' or 'installment'

    function fmt(n) { return parseFloat(n).toFixed(2); }

    function updateSummary(amount, months, install) {
        if (amount > 0 && months > 0 && install > 0) {
            var total = months * install;
            $summary.html(
                '<i class="fa fa-calculator tw-mr-1"></i>' +
                '<strong>' + fmt(install) + '</strong> &times; <strong>' + months + ' months</strong>' +
                ' = total <strong>' + fmt(total) + '</strong>'
            ).show();
        } else {
            $summary.hide();
        }
    }

    // Months changed → recalculate installment (auto mode)
    $months.on('input', function () {
        lastEdited = 'months';
        $modeLabel.text('Auto-calculated').removeClass('label-warning').addClass('label-default');
        var amount = parseFloat($amount.val()) || 0;
        var months = parseInt($months.val()) || 1;
        if (amount > 0 && months > 0) {
            var install = amount / months;
            $installment.val(fmt(install));
            updateSummary(amount, months, install);
        }
    });

    // Installment changed → recalculate months (custom mode)
    $installment.on('input', function () {
        lastEdited = 'installment';
        $modeLabel.text('Custom installment').removeClass('label-default').addClass('label-warning');
        var amount  = parseFloat($amount.val()) || 0;
        var install = parseFloat($installment.val()) || 0;
        if (amount > 0 && install > 0) {
            var months = Math.ceil(amount / install);
            $months.val(months);
            updateSummary(amount, months, install);
        }
    });

    // Amount changed → preserve last-edited field, recalculate the other
    $amount.on('input', function () {
        var amount = parseFloat($amount.val()) || 0;
        if (lastEdited === 'installment') {
            var install = parseFloat($installment.val()) || 0;
            if (amount > 0 && install > 0) {
                $months.val(Math.ceil(amount / install));
                updateSummary(amount, parseInt($months.val()), install);
            }
        } else {
            var months = parseInt($months.val()) || 1;
            if (amount > 0 && months > 0) {
                var install = amount / months;
                $installment.val(fmt(install));
                updateSummary(amount, months, install);
            }
        }
    });

    // Client-side validation before submit
    $('#loanForm').on('submit', function (e) {
        var amount  = parseFloat($amount.val()) || 0;
        var install = parseFloat($installment.val()) || 0;
        var months  = parseInt($months.val()) || 0;

        if (install <= 0) {
            alert('Monthly installment must be greater than 0.');
            e.preventDefault(); return;
        }
        if (install > amount) {
            alert('Monthly installment cannot exceed the loan amount.');
            e.preventDefault(); return;
        }
        if (months < 1) {
            alert('Repayment period must be at least 1 month.');
            e.preventDefault(); return;
        }
    });

    // Initial calc on page load (amount=0 so just prime the field)
    if ($amount.val() && $months.val()) {
        var a = parseFloat($amount.val()), m = parseInt($months.val());
        if (a > 0 && m > 0) { $installment.val(fmt(a / m)); }
    }
});
</script>
