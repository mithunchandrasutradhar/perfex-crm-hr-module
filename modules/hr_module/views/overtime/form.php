<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $employees  */
/** @var bool  $own_only   */
/** @var int   $own_emp_id */
/** @var array $dates      */
if (!isset($own_only))   $own_only   = false;
if (!isset($own_emp_id)) $own_emp_id = 0;
if (!isset($dates))      $dates      = [];
$is_edit  = !empty($overtime);
$form_url = $is_edit
    ? admin_url('hr_module/overtime/edit/' . $overtime->id)
    : admin_url('hr_module/overtime/request');
$existing_dates = $is_edit ? array_column($dates, 'overtime_date') : [];
// Self-service employees may only request overtime within the current
// calendar month (see Overtime::request() for the actual enforcement) -
// this only narrows the date picker for a NEW request; edit() is untouched.
$restrict_to_current_month = $own_only && !$is_edit;
// The xdsoft datetimepicker (assets/plugins/datetimepicker) parses
// data-date-min-date/data-date-end-date against its own "formatDate" option
// (default 'Y/m/d'), which Perfex's appDatepicker() never overrides - it
// only sets "format" (the site's display format, e.g. d-m-Y). Passing the
// site-format string here failed to parse, so the picker silently fell back
// to "now" for both bounds, collapsing the selectable range to just today.
$current_month_start = $restrict_to_current_month ? date('Y/m/01') : '';
$current_month_end   = $restrict_to_current_month ? date('Y/m/t') : '';
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
            <div class="alert alert-info tw-text-sm">
              <i class="fa fa-info-circle tw-mr-1"></i><?php echo _l('hr_overtime_eligibility_hint'); ?>
              <?php echo _l('hr_overtime_multi_date_hint'); ?>
              <?php if ($restrict_to_current_month): ?>
              <?php echo _l('hr_overtime_current_month_only'); ?>
              <?php endif; ?>
            </div>
            <?php echo form_open($form_url, ['id' => 'overtimeForm']); ?>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group select-placeholder">
                    <label><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
                    <select name="employee_id" id="ot_emp" class="selectpicker" data-width="100%" data-live-search="true" required
                      <?php if ($own_only) echo 'disabled'; ?>>
                      <option value=""><?php echo _l('hr_select'); ?></option>
                      <?php foreach ($employees as $id => $name): ?>
                      <option value="<?php echo $id; ?>"
                        <?php if ($own_only || ($is_edit && $overtime->employee_id == $id)) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($name); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                    <?php if ($own_only): ?>
                    <input type="hidden" name="employee_id" value="<?php echo (int) $own_emp_id; ?>">
                    <?php endif; ?>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?php echo _l('hr_overtime_dates'); ?> <span class="text-danger">*</span></label>
                    <div id="ot_dates_wrap">
                      <?php if ($is_edit && $existing_dates): ?>
                        <?php foreach ($existing_dates as $d): ?>
                        <div class="ot-date-row tw-mb-2">
                          <div class="tw-flex tw-gap-2">
                            <div class="input-group date">
                              <input type="text" name="overtime_date[]" class="form-control datepicker ot-date-input" autocomplete="off" required value="<?php echo _d($d); ?>">
                              <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                            </div>
                            <button type="button" class="btn btn-default btn-sm ot-remove-date" style="display:none">
                              <i class="fa fa-times"></i>
                            </button>
                          </div>
                          <div class="ot-date-status tw-text-xs tw-mt-1"></div>
                        </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                      <div class="ot-date-row tw-mb-2">
                        <div class="tw-flex tw-gap-2">
                          <div class="input-group date">
                            <input type="text" name="overtime_date[]" class="form-control datepicker ot-date-input" autocomplete="off" required
                              <?php if ($restrict_to_current_month): ?>
                              data-date-min-date="<?php echo $current_month_start; ?>" data-date-end-date="<?php echo $current_month_end; ?>"
                              <?php endif; ?>>
                            <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                          </div>
                          <button type="button" class="btn btn-default btn-sm ot-remove-date" style="display:none">
                            <i class="fa fa-times"></i>
                          </button>
                        </div>
                        <div class="ot-date-status tw-text-xs tw-mt-1"></div>
                      </div>
                      <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-default btn-xs" id="ot_add_date">
                      <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_overtime_add_another_date'); ?>
                    </button>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_overtime_reason'); ?></label>
                <textarea name="reason" class="form-control" rows="3"
                          placeholder="Work description for this overtime..."><?php echo $is_edit ? htmlspecialchars($overtime->reason) : ''; ?></textarea>
              </div>
              <div class="tw-flex tw-gap-2">
                <button type="submit" class="btn btn-primary" id="ot_submit_btn">
                  <?php echo $is_edit ? _l('hr_save') : 'Submit Request'; ?>
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
    var dateRangeAttrs = <?php echo $restrict_to_current_month
        ? "' data-date-min-date=\"" . $current_month_start . "\" data-date-end-date=\"" . $current_month_end . "\"'"
        : "''"; ?>;
    var dayTypeLabels = {
        weekend: '<?php echo _l('hr_overtime_weekend'); ?>',
        government_holiday: '<?php echo _l('hr_overtime_government_holiday'); ?>',
        company_holiday: '<?php echo _l('hr_overtime_company_holiday'); ?>'
    };
    var selectPrompt = '<?php echo $own_only ? _l('hr_overtime_select_date') : _l('hr_overtime_select_employee_date'); ?>';
    var notEligible   = '<?php echo _l('hr_overtime_not_eligible_date'); ?>';

    function updateRowPreview($row){
        var eid  = $('#ot_emp').val();
        var date = $row.find('.ot-date-input').val();
        var $status = $row.find('.ot-date-status');
        if (!eid || !date) {
            $status.removeClass('text-success text-danger').addClass('text-muted').text(selectPrompt);
            $row.attr('data-eligible', '0');
            return;
        }
        $.getJSON('<?php echo admin_url('hr_module/overtime/preview'); ?>', {employee_id: eid, overtime_date: date}, function(d){
            if (!d || !d.eligible) {
                $status.removeClass('text-muted text-success').addClass('text-danger')
                    .html('<i class="fa fa-times-circle tw-mr-1"></i>' + (d && d.message ? d.message : notEligible));
                $row.attr('data-eligible', '0');
                return;
            }
            var label = dayTypeLabels[d.day_type] || d.day_type;
            if (d.holiday_name) label += ' &mdash; ' + d.holiday_name;
            $status.removeClass('text-muted text-danger').addClass('text-success')
                .html('<i class="fa fa-check-circle tw-mr-1"></i>' + label);
            $row.attr('data-eligible', '1');
        });
    }

    function updateAllPreviews(){
        $('#ot_dates_wrap .ot-date-row').each(function(){ updateRowPreview($(this)); });
    }

    function refreshRemoveButtons(){
        var $rows = $('#ot_dates_wrap .ot-date-row');
        $rows.find('.ot-remove-date').toggle($rows.length > 1);
    }

    $(document).on('change changed.bs.select', '#ot_emp', updateAllPreviews);
    $(document).on('change', '.ot-date-input', function(){ updateRowPreview($(this).closest('.ot-date-row')); });

    $('#ot_add_date').on('click', function(){
        var $row = $('<div class="ot-date-row tw-mb-2"><div class="tw-flex tw-gap-2">'
            + '<div class="input-group date">'
            + '<input type="text" name="overtime_date[]" class="form-control datepicker ot-date-input" autocomplete="off" required' + dateRangeAttrs + '>'
            + '<div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>'
            + '</div>'
            + '<button type="button" class="btn btn-default btn-sm ot-remove-date"><i class="fa fa-times"></i></button>'
            + '</div><div class="ot-date-status tw-text-xs tw-mt-1"></div></div>');
        $('#ot_dates_wrap').append($row);
        init_datepicker($row.find('.ot-date-input'));
        updateRowPreview($row);
        refreshRemoveButtons();
    });

    $(document).on('click', '.ot-remove-date', function(){
        $(this).closest('.ot-date-row').remove();
        refreshRemoveButtons();
    });

    updateAllPreviews();
    refreshRemoveButtons();

    $('#overtimeForm').on('submit', function(e){
        var dates = [];
        var allEligible = true;
        $('#ot_dates_wrap .ot-date-row').each(function(){
            var $row = $(this);
            var date = $row.find('.ot-date-input').val();
            if (date) dates.push(date);
            if ($row.attr('data-eligible') !== '1') allEligible = false;
        });
        if (!dates.length) {
            e.preventDefault();
            alert_float('danger', selectPrompt);
            return;
        }
        if (!allEligible) {
            e.preventDefault();
            alert_float('danger', notEligible);
            return;
        }
        if (new Set(dates).size !== dates.length) {
            e.preventDefault();
            alert_float('danger', '<?php echo _l('hr_overtime_duplicate_date'); ?>');
            return;
        }
    });
});
</script>
