<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/leave'); ?>"><?php echo _l('hr_leave_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_leave_add'); ?></li>
        </ol>
      </div>
    </div>
    <div class="row">
      <div class="col-md-7">
        <div class="panel_s">
          <div class="panel-body">
            <?php echo form_open_multipart(admin_url('hr_module/leave/apply'), ['id' => 'leave-form']); ?>
            <div class="form-group">
              <label><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
              <select name="employee_id" id="leave-employee" class="form-control" required>
                <option value=""><?php echo _l('hr_select'); ?></option>
                <?php foreach ($employees as $id => $name): ?>
                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label><?php echo _l('hr_leave_type'); ?> <span class="text-danger">*</span></label>
                  <select name="leave_type_id" id="leave-type" class="form-control" required>
                    <option value=""><?php echo _l('hr_select'); ?></option>
                    <?php foreach ($leave_types as $t): ?>
                    <option value="<?php echo $t->id; ?>" data-half="<?php echo $t->allow_half_day; ?>">
                      <?php echo htmlspecialchars($t->name); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group" id="balance-box" style="display:none">
                  <label><?php echo _l('hr_leave_balance'); ?></label>
                  <div class="form-control-static">
                    <strong id="balance-remaining">—</strong>
                    <span class="text-muted tw-text-sm"> days remaining</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-5">
                <div class="form-group">
                  <label><?php echo _l('hr_from_date'); ?> <span class="text-danger">*</span></label>
                  <div class="input-group date">
                    <input type="text" name="from_date" id="leave-from" class="form-control datepicker" autocomplete="off" required>
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-md-5">
                <div class="form-group">
                  <label><?php echo _l('hr_to_date'); ?> <span class="text-danger">*</span></label>
                  <div class="input-group date">
                    <input type="text" name="to_date" id="leave-to" class="form-control datepicker" autocomplete="off" required>
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-md-2 tw-flex tw-items-end tw-pb-4">
                <span id="days-count" class="label label-info" style="font-size:1em;padding:6px 10px">0 days</span>
              </div>
            </div>
            <div class="form-group" id="half-day-box" style="display:none">
              <div class="checkbox">
                <label>
                  <input type="checkbox" name="is_half_day" value="1" id="leave-half">
                  <?php echo _l('hr_leave_half_day'); ?>
                </label>
              </div>
            </div>
            <div class="form-group">
              <label><?php echo _l('hr_leave_reason'); ?></label>
              <textarea name="reason" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label><?php echo _l('hr_attachments'); ?></label>
              <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
              <small class="text-muted">Max 4MB — JPG, PNG, PDF, DOC</small>
            </div>
            <hr>
            <a href="<?php echo admin_url('hr_module/leave'); ?>" class="btn btn-default"><?php echo _l('hr_cancel'); ?></a>
            <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane tw-mr-1"></i><?php echo _l('hr_submit'); ?></button>
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
    function calcDays() {
        var from = $('#leave-from').val();
        var to   = $('#leave-to').val();
        var half = $('#leave-half').is(':checked');
        if (!from || !to) { $('#days-count').text('0 days'); return; }
        var d1 = new Date(from), d2 = new Date(to);
        if (isNaN(d1) || isNaN(d2) || d2 < d1) { $('#days-count').text('—'); return; }
        var days = half ? 0.5 : Math.round((d2 - d1) / 86400000) + 1;
        $('#days-count').text(days + ' day' + (days != 1 ? 's' : ''));
    }

    function loadBalance() {
        var emp  = $('#leave-employee').val();
        var type = $('#leave-type').val();
        if (!emp || !type) { $('#balance-box').hide(); return; }
        $.getJSON('<?php echo admin_url('hr_module/leave/get_balance_ajax'); ?>', {
            employee_id: emp, leave_type_id: type
        }, function(data){
            $('#balance-remaining').text(data.remaining);
            $('#balance-box').show();
        });
    }

    $('#leave-from, #leave-to').on('change', calcDays);
    $('#leave-half').on('change', calcDays);
    $('#leave-employee, #leave-type').on('change', loadBalance);

    $('#leave-type').on('change', function(){
        var halfAllowed = $(this).find(':selected').data('half');
        halfAllowed ? $('#half-day-box').show() : $('#half-day-box').hide();
    });
});
</script>
