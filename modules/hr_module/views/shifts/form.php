<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var bool  $own_only    */
/** @var int   $own_emp_id  */
/** @var array $shift_types */
if (!isset($own_only))   $own_only   = false;
if (!isset($own_emp_id)) $own_emp_id = 0;
if (!isset($shift_types)) $shift_types = [];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/shifts'); ?>"><?php echo _l('hr_shift_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_shift_add_request'); ?></li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo _l('hr_shift_add_request'); ?></h4>
            <?php echo form_open(admin_url('hr_module/shifts/apply'), ['id' => 'shiftForm']); ?>

              <div class="form-group select-placeholder">
                <label><?php echo _l('hr_shift_employee'); ?> <span class="text-danger">*</span></label>
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

              <div class="form-group">
                <label><?php echo _l('hr_shift_dates'); ?> <span class="text-danger">*</span></label>
                <div id="shift-dates-wrapper">
                  <div class="row shift-date-row tw-mb-2">
                    <div class="col-md-6">
                      <div class="input-group date">
                        <input type="text" name="dates[]" class="form-control datepicker" autocomplete="off" required>
                        <span class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></span>
                      </div>
                    </div>
                    <div class="col-md-5 select-placeholder">
                      <select name="shift_type_ids[]" class="form-control selectpicker" data-width="100%" required>
                        <option value=""><?php echo _l('hr_select'); ?></option>
                        <?php foreach ($shift_types as $s): ?>
                        <option value="<?php echo $s->id; ?>">
                          <?php echo htmlspecialchars($s->name) . ' (' . date('g:i A', strtotime($s->start_time)) . ' - ' . date('g:i A', strtotime($s->end_time)) . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-1">
                      <button type="button" class="btn btn-danger remove-shift-date" style="display:none" title="<?php echo _l('hr_delete'); ?>"><i class="fa fa-times"></i></button>
                    </div>
                  </div>
                </div>
                <button type="button" class="btn btn-default btn-sm" id="add-shift-date">
                  <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_shift_add_another_date'); ?>
                </button>
              </div>

              <div class="form-group">
                <label><?php echo _l('hr_shift_reason'); ?></label>
                <textarea name="reason" class="form-control" rows="3" placeholder="Reason for this shift assignment..."></textarea>
              </div>

              <div class="tw-flex tw-gap-2">
                <button type="submit" class="btn btn-primary"><?php echo _l('hr_submit'); ?></button>
                <a href="<?php echo admin_url('hr_module/shifts'); ?>" class="btn btn-default"><?php echo _l('hr_cancel'); ?></a>
              </div>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/template" id="shift-date-row-template">
  <div class="row shift-date-row tw-mb-2">
    <div class="col-md-6">
      <div class="input-group date">
        <input type="text" name="dates[]" class="form-control datepicker" autocomplete="off" required>
        <span class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></span>
      </div>
    </div>
    <div class="col-md-5 select-placeholder">
      <select name="shift_type_ids[]" class="form-control selectpicker" data-width="100%" required>
        <option value=""><?php echo _l('hr_select'); ?></option>
        <?php foreach ($shift_types as $s): ?>
        <option value="<?php echo $s->id; ?>">
          <?php echo htmlspecialchars($s->name) . ' (' . date('g:i A', strtotime($s->start_time)) . ' - ' . date('g:i A', strtotime($s->end_time)) . ')'; ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1">
      <button type="button" class="btn btn-danger remove-shift-date" title="<?php echo _l('hr_delete'); ?>"><i class="fa fa-times"></i></button>
    </div>
  </div>
</script>

<?php init_tail(); ?>

<script>
$(function(){
    function updateRemoveButtons(){
        var $rows = $('#shift-dates-wrapper .shift-date-row');
        $rows.find('.remove-shift-date').toggle($rows.length > 1);
    }

    $('#add-shift-date').on('click', function(){
        var $row = $($('#shift-date-row-template').html());
        $('#shift-dates-wrapper').append($row);
        init_datepicker($row.find('.datepicker'));
        appSelectPicker($row.find('.selectpicker'));
        updateRemoveButtons();
    });

    $(document).on('click', '#shift-dates-wrapper .remove-shift-date', function(){
        $(this).closest('.shift-date-row').remove();
        updateRemoveButtons();
    });
});
</script>
