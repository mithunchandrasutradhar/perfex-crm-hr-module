<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/** @var array  $employees       */
/** @var array  $leave_types     */
/** @var bool   $own_only        */
/** @var int    $own_emp_id      */
/** @var string $holidays_json   */
/** @var string $weekly_off_json */
if (!isset($employees))        $employees        = [];
if (!isset($leave_types))      $leave_types      = [];
if (!isset($own_only))         $own_only         = false;
if (!isset($own_emp_id))       $own_emp_id       = 0;
if (!isset($holidays_json))    $holidays_json    = '[]';
if (!isset($weekly_off_json))  $weekly_off_json  = '[5]';
?>
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
              <select name="employee_id" id="leave-employee" class="form-control" required
                <?php if (!empty($own_only)) echo 'disabled'; ?>>
                <option value=""><?php echo _l('hr_select'); ?></option>
                <?php foreach ($employees as $id => $name): ?>
                <option value="<?php echo $id; ?>" <?php if (!empty($own_only)) echo 'selected'; ?>>
                  <?php echo htmlspecialchars($name); ?>
                </option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($own_only)): ?>
              <input type="hidden" name="employee_id" value="<?php echo (int) $own_emp_id; ?>">
              <?php endif; ?>
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
                <span id="days-count" class="label label-default" style="font-size:1em;padding:6px 10px">0 days</span>
              </div>
            </div>
            <div id="holiday-notice" class="alert alert-info tw-py-2 tw-text-sm" style="display:none"></div>
            <div class="form-group" id="half-day-box" style="display:none">
              <div class="checkbox checkbox-primary">
                <input type="checkbox" name="is_half_day" value="1" id="leave-half">
                <label for="leave-half"><?php echo _l('hr_leave_half_day'); ?></label>
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
(function(){
    // Holidays + weekly-off loaded from server (for current year)
    var gHolidays  = <?php echo isset($holidays_json)  ? $holidays_json  : '[]'; ?>;
    var gWeeklyOff = <?php echo isset($weekly_off_json) ? $weekly_off_json : '[5]'; ?>;

    function parseDateLocal(str) {
        if (!str) return null;
        var p = str.split('-');
        if (p.length === 3 && p[0].length === 2) {
            // DD-MM-YYYY (Perfex datepicker)
            return new Date(parseInt(p[2]), parseInt(p[1]) - 1, parseInt(p[0]));
        }
        // YYYY-MM-DD
        return new Date(parseInt(p[0]), parseInt(p[1]) - 1, parseInt(p[2]));
    }

    function toYMD(d) {
        var mm = ('0' + (d.getMonth() + 1)).slice(-2);
        var dd = ('0' + d.getDate()).slice(-2);
        return d.getFullYear() + '-' + mm + '-' + dd;
    }

    function isWorkingDay(d) {
        if (gWeeklyOff.indexOf(d.getDay()) !== -1) return false;
        var ymd = toYMD(d);
        for (var i = 0; i < gHolidays.length; i++) {
            if (gHolidays[i].date === ymd) return false;
        }
        return true;
    }

    function countWorkingDays(d1, d2) {
        var count = 0;
        var cur   = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate());
        var end   = new Date(d2.getFullYear(), d2.getMonth(), d2.getDate());
        while (cur <= end) {
            if (isWorkingDay(cur)) count++;
            cur.setDate(cur.getDate() + 1);
        }
        return count;
    }

    function getHolidaysInRange(d1, d2) {
        var list = [];
        var end = toYMD(d2);
        var start = toYMD(d1);
        for (var i = 0; i < gHolidays.length; i++) {
            if (gHolidays[i].date >= start && gHolidays[i].date <= end) {
                list.push(gHolidays[i]);
            }
        }
        return list;
    }

    function calcDays() {
        var from = $('#leave-from').val();
        var to   = $('#leave-to').val();
        var half = $('#leave-half').is(':checked');

        if (!from || !to) {
            $('#days-count').removeClass('label-success label-warning label-danger').addClass('label-default').text('0 days');
            $('#holiday-notice').hide();
            return;
        }
        var d1 = parseDateLocal(from), d2 = parseDateLocal(to);
        if (!d1 || !d2 || isNaN(d1) || isNaN(d2) || d2 < d1) {
            $('#days-count').removeClass('label-success label-warning').addClass('label-danger').text('Invalid range');
            $('#holiday-notice').hide();
            return;
        }

        if (half) {
            $('#days-count').removeClass('label-default label-danger').addClass('label-info').text('0.5 day (half)');
            $('#holiday-notice').hide();
            return;
        }

        var calDays     = Math.round((d2 - d1) / 86400000) + 1;
        var workingDays = countWorkingDays(d1, d2);
        var excluded    = calDays - workingDays;
        var holidays    = getHolidaysInRange(d1, d2);

        $('#days-count')
            .removeClass('label-default label-danger label-warning')
            .addClass(workingDays === 0 ? 'label-warning' : 'label-info')
            .text(workingDays + ' working day' + (workingDays !== 1 ? 's' : ''));

        if (excluded > 0) {
            var parts = [];
            // count weekly off days
            var woCount = 0;
            var cur = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate());
            while (cur <= d2) {
                if (gWeeklyOff.indexOf(cur.getDay()) !== -1) woCount++;
                cur.setDate(cur.getDate() + 1);
            }
            if (woCount > 0) parts.push(woCount + ' weekly off');
            if (holidays.length > 0) parts.push(holidays.length + ' holiday' + (holidays.length > 1 ? 's' : ''));
            var msg = '<i class="fa fa-info-circle tw-mr-1"></i>' + excluded + ' day(s) excluded: ' + parts.join(' + ');
            if (holidays.length > 0) {
                msg += ' <span class="text-muted">(' + holidays.map(function(h){ return h.name; }).join(', ') + ')</span>';
            }
            $('#holiday-notice').html(msg).show();
        } else {
            $('#holiday-notice').hide();
        }
    }

    function loadBalance() {
        var emp  = $('#leave-employee').val();
        var type = $('#leave-type').val();
        if (!emp || !type) { $('#balance-box').hide(); return; }
        $.getJSON('<?php echo admin_url('hr_module/leave/get_balance_ajax'); ?>', {
            employee_id: emp, leave_type_id: type
        }, function(data){
            var rem = parseFloat(data.remaining) || 0;
            $('#balance-remaining').text(rem);
            $('#balance-remaining').closest('.form-control-static')
                .removeClass('text-success text-danger')
                .addClass(rem <= 0 ? 'text-danger' : 'text-success');
            $('#balance-box').show();
        });
    }

    $(document).ready(function(){
        $('#leave-from, #leave-to').on('change dp.change', calcDays);
        $('#leave-half').on('change', calcDays);
        $('#leave-employee, #leave-type').on('change', function(){
            loadBalance();
            calcDays();
        });
        $('#leave-type').on('change', function(){
            var halfAllowed = $(this).find(':selected').data('half');
            halfAllowed ? $('#half-day-box').show() : $('#half-day-box').hide();
        });
    });
})();
</script>
