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
      <div class="col-md-9">
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
                    <option value="<?php echo $t->id; ?>"
                            data-half="<?php echo $t->allow_half_day; ?>"
                            data-hours-per-day="<?php echo $t->hours_per_day; ?>"
                            data-is-range="<?php echo $t->is_date_range; ?>">
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

            <hr>

            <!-- Range mode: long continuous leave (e.g. Maternity Leave) - a plain From/To range -->
            <div id="range-mode-section" style="display:none">
              <div class="row">
                <div class="col-md-5">
                  <div class="form-group">
                    <label><?php echo _l('hr_from_date'); ?> <span class="text-danger">*</span></label>
                    <div class="input-group date">
                      <input type="text" name="range_from_date" id="range-from" class="form-control datepicker" autocomplete="off">
                      <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                </div>
                <div class="col-md-5">
                  <div class="form-group">
                    <label><?php echo _l('hr_to_date'); ?> <span class="text-danger">*</span></label>
                    <div class="input-group date">
                      <input type="text" name="range_to_date" id="range-to" class="form-control datepicker" autocomplete="off">
                      <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                </div>
                <div class="col-md-2 tw-flex tw-items-end tw-pb-4">
                  <span id="range-days-count" class="label label-default" style="font-size:1em;padding:6px 10px">0 <?php echo _l('hr_days'); ?></span>
                </div>
              </div>
            </div>

            <!-- Daily mode: day-by-day builder for normal leave types -->
            <div id="daily-mode-section">
              <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
                <label class="tw-mb-0"><?php echo _l('hr_leave_days_breakdown'); ?> <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-primary btn-sm" id="btn-add-day">
                  <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_leave_add_day'); ?>
                </button>
              </div>

              <div id="day-rows"></div>
              <p id="no-days-msg" class="text-muted tw-text-sm"><?php echo _l('hr_leave_no_days_added'); ?></p>

              <div id="bridge-info" class="alert alert-info tw-py-2 tw-text-sm" style="display:none"></div>

              <div class="tw-flex tw-justify-end tw-mb-3">
                <span id="total-days" class="label label-default" style="font-size:1em;padding:6px 10px">0 <?php echo _l('hr_days'); ?></span>
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

<!-- Row template: cloned via JS, never submitted directly (outside the form) -->
<div id="day-row-template" style="display:none">
  <div class="panel panel-default leave-day-row">
    <div class="panel-body">
      <div class="row">
        <div class="col-sm-3">
          <label class="tw-text-xs"><?php echo _l('hr_date'); ?></label>
          <input type="text" class="form-control input-sm datepicker day-date" autocomplete="off" required>
        </div>
        <div class="col-sm-3">
          <label class="tw-text-xs"><?php echo _l('hr_leave_duration_type'); ?></label>
          <select class="form-control input-sm day-type">
            <option value="full"><?php echo _l('hr_leave_day_type_full'); ?></option>
            <option value="half"><?php echo _l('hr_leave_day_type_half'); ?></option>
            <option value="hourly"><?php echo _l('hr_leave_day_type_hourly'); ?></option>
          </select>
        </div>
        <div class="col-sm-4">
          <div class="day-detail-half" style="display:none">
            <label class="tw-text-xs"><?php echo _l('hr_leave_half_day'); ?></label>
            <select class="form-control input-sm day-half-period">
              <option value="before_lunch"><?php echo _l('hr_leave_before_lunch'); ?></option>
              <option value="after_lunch"><?php echo _l('hr_leave_after_lunch'); ?></option>
            </select>
          </div>
          <div class="day-detail-hourly" style="display:none">
            <div class="row">
              <div class="col-xs-6">
                <label class="tw-text-xs"><?php echo _l('hr_leave_hour_start'); ?></label>
                <input type="time" class="form-control input-sm day-hour-start">
              </div>
              <div class="col-xs-6">
                <label class="tw-text-xs"><?php echo _l('hr_leave_hour_end'); ?></label>
                <input type="time" class="form-control input-sm day-hour-end">
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-1 tw-flex tw-items-end tw-mb-2">
          <span class="label label-info day-value">1.0</span>
        </div>
        <div class="col-sm-1 tw-flex tw-items-end tw-mb-2">
          <button type="button" class="btn btn-danger btn-xs remove-day"><i class="fa fa-times"></i></button>
        </div>
      </div>
      <div class="day-warning text-muted tw-text-xs" style="display:none"></div>
    </div>
  </div>
</div>

<script>
(function(){
    var gHolidays  = <?php echo isset($holidays_json)  ? $holidays_json  : '[]'; ?>;
    var gWeeklyOff = <?php echo isset($weekly_off_json) ? $weekly_off_json : '[5]'; ?>;
    var rowIndex = 0;

    function parseDateLocal(str) {
        if (!str) return null;
        var p = str.split('-');
        if (p.length === 3 && p[0].length === 2) {
            // DD-MM-YYYY (Perfex datepicker)
            return new Date(parseInt(p[2]), parseInt(p[1]) - 1, parseInt(p[0]));
        }
        return new Date(parseInt(p[0]), parseInt(p[1]) - 1, parseInt(p[2]));
    }

    function toYMD(d) {
        var mm = ('0' + (d.getMonth() + 1)).slice(-2);
        var dd = ('0' + d.getDate()).slice(-2);
        return d.getFullYear() + '-' + mm + '-' + dd;
    }

    function dayNoteFor(dateStr) {
        var d = parseDateLocal(dateStr);
        if (!d || isNaN(d)) return '';
        if (gWeeklyOff.indexOf(d.getDay()) !== -1) {
            return '<i class="fa fa-info-circle tw-mr-1"></i>This is a weekly off day.';
        }
        var ymd = toYMD(d);
        for (var i = 0; i < gHolidays.length; i++) {
            if (gHolidays[i].date === ymd) {
                return '<i class="fa fa-info-circle tw-mr-1"></i>Holiday: ' + gHolidays[i].name;
            }
        }
        return '';
    }

    function selectedLeaveTypeMeta() {
        var $opt = $('#leave-type').find(':selected');
        return {
            allowHalf: $opt.data('half') == 1,
            hoursPerDay: parseFloat($opt.data('hours-per-day')) || 8
        };
    }

    function computeRowValue($row) {
        var type = $row.find('.day-type').val();
        var meta = selectedLeaveTypeMeta();
        if (type === 'full') return 1;
        if (type === 'half') return meta.allowHalf ? 0.5 : 0;
        if (type === 'hourly') {
            var start = $row.find('.day-hour-start').val();
            var end   = $row.find('.day-hour-end').val();
            if (!start || !end) return 0;
            var s = start.split(':'), e = end.split(':');
            var startMin = parseInt(s[0]) * 60 + parseInt(s[1]);
            var endMin   = parseInt(e[0]) * 60 + parseInt(e[1]);
            if (endMin <= startMin) return 0;
            var hours = (endMin - startMin) / 60;
            return Math.round((hours / meta.hoursPerDay) * 100) / 100;
        }
        return 0;
    }

    function refreshRow($row) {
        var type = $row.find('.day-type').val();
        $row.find('.day-detail-half').toggle(type === 'half');
        $row.find('.day-detail-hourly').toggle(type === 'hourly');

        var dateVal = $row.find('.day-date').val();
        var notes = [];
        if (dateVal) {
            var holidayNote = dayNoteFor(dateVal);
            if (holidayNote) notes.push(holidayNote);
        }
        if (type === 'half' && !selectedLeaveTypeMeta().allowHalf) {
            notes.push('<i class="fa fa-exclamation-triangle tw-mr-1"></i><?php echo _l('hr_leave_half_day_not_allowed'); ?>');
        }
        $row.find('.day-warning').toggle(notes.length > 0).html(notes.join(' '));

        var value = computeRowValue($row);
        $row.find('.day-value').text(parseFloat(value.toFixed(2)));
        refreshTotal();
    }

    // Sandwich rule preview: mirrors Leave_model::_add_bridge_days() so the employee
    // sees the real total (e.g. Thu + Sat with Fri off = 3 days) before submitting.
    // Only triggers between two Full Day entries - half-day/hourly days never bridge.
    function computeBridgeDays() {
        var entries = [];
        $('#day-rows .leave-day-row').each(function(){
            var v = $(this).find('.day-date').val();
            var d = v ? parseDateLocal(v) : null;
            if (d && !isNaN(d)) entries.push({ date: d, type: $(this).find('.day-type').val() });
        });
        entries.sort(function(a, b){ return a.date - b.date; });

        var bridges = [];
        for (var i = 0; i < entries.length - 1; i++) {
            if (entries[i].type !== 'full' || entries[i + 1].type !== 'full') continue;

            var gapStart = new Date(entries[i].date);     gapStart.setDate(gapStart.getDate() + 1);
            var gapEnd   = new Date(entries[i + 1].date); gapEnd.setDate(gapEnd.getDate() - 1);
            if (gapStart > gapEnd) continue;

            var gapDays = [];
            var allNonWorking = true;
            var cur = new Date(gapStart);
            while (cur <= gapEnd) {
                var ymd = toYMD(cur);
                var isWeeklyOff = gWeeklyOff.indexOf(cur.getDay()) !== -1;
                var holidayName = null;
                for (var h = 0; h < gHolidays.length; h++) {
                    if (gHolidays[h].date === ymd) { holidayName = gHolidays[h].name; break; }
                }
                if (!isWeeklyOff && !holidayName) { allNonWorking = false; break; }
                gapDays.push({ date: ymd, name: holidayName || 'Weekly Off' });
                cur.setDate(cur.getDate() + 1);
            }
            if (allNonWorking) bridges = bridges.concat(gapDays);
        }
        return bridges;
    }

    function refreshTotal() {
        var total = 0;
        $('#day-rows .leave-day-row').each(function(){
            total += computeRowValue($(this));
        });
        var bridges = computeBridgeDays();
        total += bridges.length;
        total = Math.round(total * 100) / 100;
        $('#total-days').text(total + ' <?php echo _l('hr_days'); ?>');
        $('#no-days-msg').toggle($('#day-rows .leave-day-row').length === 0);

        if (bridges.length > 0) {
            var msg = '<i class="fa fa-info-circle tw-mr-1"></i>' + bridges.length +
                ' <?php echo _l('hr_leave_day_type_bridge'); ?>: ' +
                bridges.map(function(b){ return b.date + ' (' + b.name + ')'; }).join(', ');
            $('#bridge-info').html(msg).show();
        } else {
            $('#bridge-info').hide();
        }
    }

    function addDayRow() {
        var $row = $('#day-row-template .leave-day-row').clone();
        rowIndex++;
        var i = rowIndex;
        $row.find('.day-date').attr('name', 'days[' + i + '][date]');
        $row.find('.day-type').attr('name', 'days[' + i + '][type]');
        $row.find('.day-half-period').attr('name', 'days[' + i + '][half_period]');
        $row.find('.day-hour-start').attr('name', 'days[' + i + '][hour_start]');
        $row.find('.day-hour-end').attr('name', 'days[' + i + '][hour_end]');

        $('#day-rows').append($row);
        init_datepicker($row.find('.day-date'));
        refreshRow($row);
    }

    function isRangeMode() {
        return $('#leave-type').find(':selected').data('is-range') == 1;
    }

    // Range mode (e.g. Maternity Leave): shows/hides the two UI modes and, when
    // switching into range mode, clears out the day-by-day rows so they aren't
    // accidentally submitted alongside a range.
    function updateModeVisibility() {
        var rangeMode = isRangeMode();
        $('#range-mode-section').toggle(rangeMode);
        $('#daily-mode-section').toggle(!rangeMode);
        if (rangeMode) {
            $('#day-rows').empty();
            refreshRangeTotal();
        } else {
            $('#range-from, #range-to').val('');
        }
    }

    function refreshRangeTotal() {
        var from = parseDateLocal($('#range-from').val());
        var to   = parseDateLocal($('#range-to').val());
        if (!from || !to || isNaN(from) || isNaN(to) || to < from) {
            $('#range-days-count').removeClass('label-info').addClass('label-default').text('0 <?php echo _l('hr_days'); ?>');
            return;
        }
        var days = Math.round((to - from) / 86400000) + 1;
        $('#range-days-count').removeClass('label-default').addClass('label-info').text(days + ' <?php echo _l('hr_days'); ?>');
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
        $('#btn-add-day').on('click', addDayRow);

        $(document).on('click', '.remove-day', function(){
            $(this).closest('.leave-day-row').remove();
            refreshTotal();
        });

        $(document).on('change dp.change', '#day-rows .day-date, #day-rows .day-type, #day-rows .day-half-period, #day-rows .day-hour-start, #day-rows .day-hour-end', function(){
            refreshRow($(this).closest('.leave-day-row'));
        });

        $('#leave-employee, #leave-type').on('change', function(){
            loadBalance();
        });
        $('#leave-type').on('change', function(){
            updateModeVisibility();
            // Re-check every row's warnings (half-day support, holiday notes) against
            // the newly selected leave type, without touching what the user picked.
            $('#day-rows .leave-day-row').each(function(){
                refreshRow($(this));
            });
        });
        $('#range-from, #range-to').on('change dp.change input blur', refreshRangeTotal);

        $('#leave-form').on('submit', function(e){
            if (isRangeMode()) {
                var from = $('#range-from').val(), to = $('#range-to').val();
                var d1 = parseDateLocal(from), d2 = parseDateLocal(to);
                if (!from || !to || !d1 || !d2 || isNaN(d1) || isNaN(d2) || d2 < d1) {
                    e.preventDefault();
                    alert_float('danger', '<?php echo _l('hr_val_date_range'); ?>');
                    return false;
                }
                return;
            }

            var $rows = $('#day-rows .leave-day-row');
            if ($rows.length === 0) {
                e.preventDefault();
                alert_float('danger', '<?php echo _l('hr_val_no_leave_days'); ?>');
                return false;
            }
            var dates = [];
            var invalid = false;
            $rows.each(function(){
                var d = $(this).find('.day-date').val();
                if (!d || dates.indexOf(d) !== -1) invalid = true;
                dates.push(d);
                if (computeRowValue($(this)) <= 0) invalid = true;
            });
            if (invalid) {
                e.preventDefault();
                alert_float('danger', '<?php echo _l('hr_val_duplicate_leave_dates'); ?>');
                return false;
            }
        });

        addDayRow();
    });
})();
</script>
