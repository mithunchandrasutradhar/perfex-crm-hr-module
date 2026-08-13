<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var bool   $own_only                */
/** @var int    $own_emp_id              */
/** @var string $max_loan_json           */
/** @var string $exposure_json           */
/** @var float  $own_max_loan_amount     */
/** @var float  $own_loan_exposure       */
/** @var float  $own_remaining_capacity  */
/** @var float  $default_max_loan_amount */
if (!isset($own_only))                $own_only                = false;
if (!isset($own_emp_id))              $own_emp_id              = 0;
if (!isset($max_loan_json))           $max_loan_json           = '{}';
if (!isset($exposure_json))           $exposure_json           = '{}';
if (!isset($own_max_loan_amount))     $own_max_loan_amount     = 0;
if (!isset($own_loan_exposure))       $own_loan_exposure       = 0;
if (!isset($own_remaining_capacity))  $own_remaining_capacity  = 0;
if (!isset($default_max_loan_amount)) $default_max_loan_amount = 99999999.99;
?>
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

              <div class="form-group select-placeholder">
                <label><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
                <select name="employee_id" class="selectpicker" data-width="100%" data-live-search="true" required
                  <?php if ($own_only) echo 'disabled'; ?>>
                  <option value=""><?php echo _l('hr_select'); ?></option>
                  <?php foreach ($employees as $id => $name): ?>
                  <option value="<?php echo $id; ?>" <?php if ($own_only) echo 'selected'; ?>><?php echo htmlspecialchars($name); ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if ($own_only): ?>
                <input type="hidden" name="employee_id" value="<?php echo (int) $own_emp_id; ?>">
                <?php endif; ?>
              </div>

              <!-- Repayment Calculator -->
              <div class="panel_s tw-mb-4" style="border:1px solid #e2e8f0;background:#f8fafc">
                <div class="panel-body tw-py-3">
                  <h5 class="tw-font-semibold tw-mb-3">Repayment Terms</h5>
                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label><?php echo _l('hr_loan_amount'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <span class="input-group-addon"><?php echo get_option('currency_symbol') ?: 'BDT'; ?></span>
                          <input type="number" step="0.01" min="1" name="amount" id="loan_amount"
                                 class="form-control" required placeholder="0.00">
                        </div>
                        <p id="maxLoanHint" class="help-block tw-text-xs tw-mb-0"></p>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group select-placeholder">
                        <label>
                          <?php echo _l('hr_loan_monthly_installment'); ?>
                          <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                          <span class="input-group-addon"><?php echo get_option('currency_symbol') ?: 'BDT'; ?></span>
                          <select name="monthly_installment" id="loan_installment" class="selectpicker" data-width="100%" required disabled>
                            <option value="">Enter amount first</option>
                          </select>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label><?php echo _l('hr_loan_repayment_months'); ?></label>
                        <div class="input-group">
                          <input type="number" name="repayment_months" id="loan_months"
                                 class="form-control" readonly value="">
                          <span class="input-group-addon">mo</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div id="calcHint" class="text-muted" style="font-size:11px;margin-top:8px">
                    <i class="fa fa-info-circle"></i>
                    Installment is chosen in steps of <?php echo number_format(500, 0); ?> — the repayment period is calculated from it automatically.
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
                <button type="submit" class="btn btn-primary">Submit Application</button>
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
    // Preloaded once on page load (see Loans::apply()) so the "remaining loan
    // capacity" hint updates instantly on employee change with no AJAX round
    // trip - same pattern as the leave-apply balance box. max_loan_amount is a
    // cap on TOTAL current exposure, not a per-request cap, so what's actually
    // available is max minus whatever's still outstanding on approved/active loans.
    var maxLoanMap          = <?php echo $max_loan_json; ?>;
    var exposureMap         = <?php echo $exposure_json; ?>;
    var ownMaxLoanAmount    = <?php echo (float) $own_max_loan_amount; ?>;
    var ownLoanExposure     = <?php echo (float) $own_loan_exposure; ?>;
    var ownRemainingCapacity = <?php echo (float) $own_remaining_capacity; ?>;
    var defaultMaxLoanAmount = <?php echo (float) $default_max_loan_amount; ?>;
    // _l('key') always sprintf()s internally (with '' when no label is passed),
    // which silently eats the %s before it ever reaches here - the raw template
    // (with %s intact, for this JS-side replace) has to come from $this->lang->line()
    // directly instead.
    var capacityHintTpl     = <?php echo json_encode($this->lang->line('hr_loan_remaining_capacity_hint')); ?>;
    var capacityExceededTpl = <?php echo json_encode($this->lang->line('hr_loan_exceeds_remaining_capacity')); ?>;
    var isOwnOnly           = <?php echo $own_only ? 'true' : 'false'; ?>;

    function currentCapacity() {
        if (isOwnOnly) {
            return { max: ownMaxLoanAmount, exposure: ownLoanExposure, remaining: ownRemainingCapacity };
        }
        var empId = $('select[name="employee_id"]').val();
        var max      = (empId && maxLoanMap.hasOwnProperty(empId)) ? parseFloat(maxLoanMap[empId]) : defaultMaxLoanAmount;
        var exposure = (empId && exposureMap.hasOwnProperty(empId)) ? parseFloat(exposureMap[empId]) : 0;
        return { max: max, exposure: exposure, remaining: max - exposure };
    }

    // Both templates take [remaining, exposure, max] in that order - each %s is
    // replaced left to right, matching the PHP-side _l() label array order.
    function fillCapacityTpl(tpl, capacity) {
        var remaining = Math.max(0, capacity.remaining);
        return tpl.replace('%s', fmt(remaining)).replace('%s', fmt(capacity.exposure)).replace('%s', fmt(capacity.max));
    }

    function updateMaxLoanHint() {
        var capacity = currentCapacity();
        $('#maxLoanHint').text(fillCapacityTpl(capacityHintTpl, capacity));
        $('#loan_amount').attr('max', Math.max(0, capacity.remaining));
    }

    $(document).on('change changed.bs.select', 'select[name="employee_id"]', updateMaxLoanHint);
    updateMaxLoanHint();

    var STEP = 500;
    // A fixed 500-unit step turns into thousands of <option> elements once the
    // amount gets large (e.g. 2,000 options at a 1,000,000 loan) - rebuilding
    // that many via selectpicker's refresh() on every keystroke is what freezes
    // the tab. Capping the option count and widening the step to compensate
    // keeps the dropdown responsive at any amount, while still only ever
    // offering clean round numbers.
    var MAX_INSTALLMENT_OPTIONS = 200;
    var $amount      = $('#loan_amount');
    var $months      = $('#loan_months');
    var $installment = $('#loan_installment');
    var $summary     = $('#calcSummary');

    function fmt(n) { return parseFloat(n).toFixed(2); }

    function updateSummary(amount, months, install) {
        var total = months * install;
        var lastInstallment = amount - (months - 1) * install;
        $summary.html(
            '<i class="fa fa-calculator tw-mr-1"></i>' +
            '<strong>' + fmt(install) + '</strong> &times; <strong>' + months + ' months</strong>' +
            (lastInstallment < install
                ? ' (last month: <strong>' + fmt(lastInstallment) + '</strong>)'
                : '')
        ).show();
    }

    function updateMonths() {
        var amount  = parseFloat($amount.val()) || 0;
        var install = parseFloat($installment.val()) || 0;
        if (amount > 0 && install > 0) {
            var months = Math.ceil(amount / install);
            $months.val(months);
            updateSummary(amount, months, install);
        } else {
            $months.val('');
            $summary.hide();
        }
    }

    // Installment is always a multiple of STEP - options are rebuilt whenever the
    // amount changes, and the repayment period is derived from whichever is picked.
    function rebuildInstallmentOptions() {
        var amount  = parseFloat($amount.val()) || 0;
        var prevVal = parseFloat($installment.val()) || 0;
        $installment.empty();

        if (amount <= 0) {
            $installment.append($('<option></option>').val('').text('Enter amount first'));
            $installment.prop('disabled', true);
            $installment.selectpicker('refresh');
            $months.val('');
            $summary.hide();
            return;
        }

        var top = Math.ceil(amount / STEP) * STEP;
        // Keep the option count bounded: widen the step to the next clean
        // multiple of STEP once a plain 500-unit granularity would produce more
        // than MAX_INSTALLMENT_OPTIONS choices.
        var effectiveStep = STEP;
        if (top / STEP > MAX_INSTALLMENT_OPTIONS) {
            effectiveStep = Math.ceil((top / MAX_INSTALLMENT_OPTIONS) / STEP) * STEP;
        }
        var steps = [];
        for (var v = effectiveStep; v <= top; v += effectiveStep) steps.push(v);

        // Keep the previous selection if it's still a valid step, otherwise default
        // to whichever step lands closest to a ~12 month repayment period.
        var defaultVal = -1;
        for (var i = 0; i < steps.length; i++) {
            if (steps[i] === prevVal) { defaultVal = prevVal; break; }
        }
        if (defaultVal === -1) {
            var target = amount / 12;
            defaultVal = steps[0];
            for (var j = 0; j < steps.length; j++) {
                if (Math.abs(steps[j] - target) < Math.abs(defaultVal - target)) defaultVal = steps[j];
            }
        }

        $installment.prop('disabled', false);
        for (var k = 0; k < steps.length; k++) {
            var opt = $('<option></option>').val(steps[k]).text(fmt(steps[k]));
            if (steps[k] === defaultVal) opt.prop('selected', true);
            $installment.append(opt);
        }
        $installment.selectpicker('refresh');
        updateMonths();
    }

    // Debounced so a full dropdown rebuild only runs once typing pauses,
    // instead of once per keystroke while the amount is being entered.
    var rebuildTimer = null;
    $amount.on('input', function () {
        clearTimeout(rebuildTimer);
        rebuildTimer = setTimeout(rebuildInstallmentOptions, 250);
    });
    $installment.on('change changed.bs.select', updateMonths);

    // Client-side validation before submit
    $('#loanForm').on('submit', function (e) {
        var amount  = parseFloat($amount.val()) || 0;
        var install = parseFloat($installment.val()) || 0;

        if (amount <= 0) {
            alert('Enter a loan amount.');
            e.preventDefault(); return;
        }
        if (install <= 0) {
            alert('Select a monthly installment.');
            e.preventDefault(); return;
        }
        var capacity = currentCapacity();
        if (amount > capacity.remaining) {
            alert(fillCapacityTpl(capacityExceededTpl, capacity));
            e.preventDefault(); return;
        }
    });
});
</script>
