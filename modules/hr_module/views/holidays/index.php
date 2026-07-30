<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/** @var int    $year           */
/** @var array  $holidays       */
/** @var array  $weekly_off     */
/** @var bool   $can_edit       */
/** @var int    $cal_year       */
/** @var int    $cal_month      */
/** @var array  $cal_holidays   */
/** @var array  $leave_by_date  */
/** @var array  $shifts_by_date */
/** @var string $roster_date    */
/** @var array  $shift_roster   */
if (!isset($year))           $year           = (int) date('Y');
if (!isset($holidays))       $holidays       = [];
if (!isset($weekly_off))     $weekly_off     = [5];
if (!isset($can_edit))       $can_edit       = false;
if (!isset($cal_year))       $cal_year       = (int) date('Y');
if (!isset($cal_month))      $cal_month      = (int) date('n');
if (!isset($cal_holidays))   $cal_holidays   = [];
if (!isset($leave_by_date))  $leave_by_date  = [];
if (!isset($shifts_by_date)) $shifts_by_date = [];
if (!isset($roster_date))    $roster_date    = date('Y-m-d');
if (!isset($shift_roster))   $shift_roster   = [];

$day_names = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">

    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-4 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-3">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
            <i class="fa fa-calendar-alt tw-mr-2 text-danger"></i>Official Calendar
          </h4>
          <!-- Year navigator -->
          <div class="tw-flex tw-items-center tw-gap-2">
            <a href="?year=<?php echo $year - 1; ?>&cal_year=<?php echo $cal_year; ?>&cal_month=<?php echo $cal_month; ?>" class="btn btn-default btn-sm"><i class="fa fa-chevron-left"></i></a>
            <span class="tw-font-semibold tw-text-lg"><?php echo $year; ?></span>
            <a href="?year=<?php echo $year + 1; ?>&cal_year=<?php echo $cal_year; ?>&cal_month=<?php echo $cal_month; ?>" class="btn btn-default btn-sm"><i class="fa fa-chevron-right"></i></a>
          </div>
        </div>
      </div>
    </div>

    <div class="row">

      <!-- ── Left: Holiday List ── -->
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-heading tw-flex tw-items-center tw-justify-between">
            <h5 class="tw-font-semibold tw-mb-0">Holidays for <?php echo $year; ?></h5>
            <?php if ($can_edit): ?>
            <button class="btn btn-primary" id="btn-add-holiday">
              <i class="fa-regular fa-plus tw-mr-1"></i>Add Holiday
            </button>
            <?php endif; ?>
          </div>

          <!-- Add form (hidden by default) -->
          <?php if ($can_edit): ?>
          <div id="add-holiday-form" style="display:none" class="tw-px-4 tw-py-3 tw-bg-neutral-50 tw-border-b">
            <div class="row">
              <div class="col-md-5">
                <div class="form-group tw-mb-2">
                  <label class="tw-text-sm">Holiday Name <span class="text-danger">*</span></label>
                  <input type="text" id="new-holiday-name" class="form-control input-sm" placeholder="e.g. Eid Ul Fitr">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group tw-mb-2">
                  <label class="tw-text-sm">Date <span class="text-danger">*</span></label>
                  <div class="input-group date">
                    <input type="text" id="new-holiday-date" class="form-control input-sm datepicker" autocomplete="off" value="">
                    <span class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group tw-mb-2">
                  <label class="tw-text-sm">Type</label>
                  <select id="new-holiday-type" class="form-control input-sm">
                    <option value="government">Government</option>
                    <option value="company">Company</option>
                  </select>
                </div>
              </div>
              <div class="col-md-2 tw-flex tw-items-end tw-pb-2">
                <button class="btn btn-success btn-sm btn-block" id="btn-save-holiday">
                  Save
                </button>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <div class="panel-body panel-table-full">
            <?php if (empty($holidays)): ?>
            <div class="tw-text-center tw-py-10 text-muted">
              <i class="fa fa-calendar-times fa-2x tw-mb-3"></i>
              <p>No holidays added for <?php echo $year; ?>.</p>
            </div>
            <?php else: ?>
            <table class="table table-condensed table-hover tw-mb-0">
              <thead>
                <tr>
                  <th style="width:130px">Date</th>
                  <th>Holiday Name</th>
                  <th style="width:100px">Day</th>
                  <th style="width:100px">Type</th>
                  <?php if ($can_edit): ?><th style="width:90px"></th><?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($holidays as $h): ?>
                <tr>
                  <td><strong><?php echo date('d M Y', strtotime($h->holiday_date)); ?></strong></td>
                  <td><?php echo htmlspecialchars($h->name); ?></td>
                  <td class="text-muted"><?php echo date('l', strtotime($h->holiday_date)); ?></td>
                  <td>
                    <?php if ($h->type === 'government'): ?>
                      <span class="label label-danger">Government</span>
                    <?php else: ?>
                      <span class="label label-info">Company</span>
                    <?php endif; ?>
                  </td>
                  <?php if ($can_edit): ?>
                  <td>
                    <a href="#" class="text-primary btn-send-announcement tw-text-sm tw-mr-2" data-id="<?php echo $h->id; ?>"
                       title="<?php echo _l('hr_holiday_send_announcement'); ?>"><i class="fa fa-paper-plane"></i></a>
                    <a href="#" class="text-danger btn-delete-holiday tw-text-sm" data-id="<?php echo $h->id; ?>"
                       title="Delete"><i class="fa fa-trash"></i></a>
                  </td>
                  <?php endif; ?>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- ── Right: Weekly Off + Summary ── -->
      <div class="col-md-4">

        <!-- Weekly Off Days -->
        <div class="panel_s">
          <div class="panel-heading">
            <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-ban tw-mr-2 text-warning"></i>Weekly Off Days</h5>
          </div>
          <div class="panel-body">
            <p class="text-muted tw-text-sm tw-mb-3">Checked days are excluded from leave day calculations.</p>
            <form id="weekly-off-form">
              <?php foreach ($day_names as $idx => $dname): ?>
              <div class="checkbox checkbox-primary">
                <input type="checkbox" name="weekly_off_days[]" value="<?php echo $idx; ?>"
                  id="dow-<?php echo $idx; ?>"
                  <?php echo in_array($idx, $weekly_off) ? 'checked' : ''; ?>
                  <?php echo !$can_edit ? 'disabled' : ''; ?>>
                <label for="dow-<?php echo $idx; ?>"><?php echo $dname; ?></label>
              </div>
              <?php endforeach; ?>
              <?php if ($can_edit): ?>
              <hr>
              <button type="submit" class="btn btn-primary btn-sm">
                Save Weekly Off
              </button>
              <span id="weekly-off-saved" style="display:none" class="text-success tw-ml-2 tw-text-sm">
                <i class="fa fa-check"></i> Saved
              </span>
              <?php endif; ?>
            </form>
          </div>
        </div>

        <!-- Summary -->
        <div class="panel_s">
          <div class="panel-heading">
            <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-info-circle tw-mr-2 text-info"></i>Summary <?php echo $year; ?></h5>
          </div>
          <div class="panel-body">
            <?php
            $govt_count    = count(array_filter($holidays, fn($h) => $h->type === 'government'));
            $company_count = count($holidays) - $govt_count;
            ?>
            <table class="table table-condensed tw-mb-0">
              <tr>
                <td>Government Holidays</td>
                <td class="tw-text-right"><strong class="text-danger"><?php echo $govt_count; ?></strong></td>
              </tr>
              <tr>
                <td>Company Holidays</td>
                <td class="tw-text-right"><strong class="text-info"><?php echo $company_count; ?></strong></td>
              </tr>
              <tr>
                <td>Total Holidays</td>
                <td class="tw-text-right"><strong><?php echo count($holidays); ?></strong></td>
              </tr>
              <tr>
                <td>Weekly Off Days</td>
                <td class="tw-text-right">
                  <strong><?php
                    $names = array_map(function($d) use ($day_names) { return $day_names[$d] ?? ''; }, $weekly_off);
                    echo $names ? implode(', ', $names) : 'None';
                  ?></strong>
                </td>
              </tr>
            </table>
          </div>
        </div>

      </div>
    </div>

    <!-- ── Who's on Leave / Shift Roster (merged calendar) ── -->
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s" id="team-calendar-panel">
          <?php echo $this->load->view('hr_module/holidays/calendar', [
              'cal_year'       => $cal_year,
              'cal_month'      => $cal_month,
              'weekly_off'     => $weekly_off,
              'cal_holidays'   => $cal_holidays,
              'leave_by_date'  => $leave_by_date,
              'shifts_by_date' => $shifts_by_date,
          ], true); ?>
        </div>
      </div>
    </div>

    <!-- ── Employees by Shift (specific date) ── -->
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-3">
            <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-list-ul tw-mr-2 text-primary"></i><?php echo _l('hr_shift_roster_by_date'); ?></h5>
            <form method="get" class="tw-flex tw-items-center tw-gap-2">
              <input type="hidden" name="year" value="<?php echo $year; ?>">
              <input type="hidden" name="cal_year" value="<?php echo $cal_year; ?>">
              <input type="hidden" name="cal_month" value="<?php echo $cal_month; ?>">
              <input type="date" name="roster_date" class="form-control input-sm" style="width:160px" value="<?php echo htmlspecialchars($roster_date); ?>">
              <button type="submit" class="btn btn-default btn-sm">View</button>
            </form>
          </div>
          <div class="panel-body panel-table-full">
            <table class="table table-condensed tw-mb-0">
              <thead>
                <tr>
                  <th style="width:220px">Shift</th>
                  <th>Employees</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($shift_roster as $type_id => $group): ?>
                <?php if ($type_id === 0) continue; // default "Day Shift" bucket - not shown on the calendar page ?>
                <tr>
                  <td>
                    <span class="label label-info">
                      <?php echo htmlspecialchars($group['name']); ?>
                    </span>
                    <span class="text-muted tw-text-sm">(<?php echo count($group['employees']); ?>)</span>
                  </td>
                  <td>
                    <?php echo !empty($group['employees']) ? htmlspecialchars(implode(', ', $group['employees'])) : '<span class="text-muted">-</span>'; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php init_tail(); ?>
<script>
$(function(){
    var csrfName  = '<?php echo $this->security->get_csrf_token_name(); ?>';
    var csrfHash  = '<?php echo $this->security->get_csrf_hash(); ?>';
    var baseUrl   = '<?php echo admin_url('hr_module/holidays'); ?>';
    var canEdit   = <?php echo $can_edit ? 'true' : 'false'; ?>;

    // Toggle add form
    var defaultDateToday = '<?php echo _d(date('Y-m-d')); ?>';
    var defaultDateJan1  = '<?php echo _d($year . '-01-01'); ?>';
    $('#btn-add-holiday').on('click', function(){
        $('#add-holiday-form').slideToggle(150);
        var y = <?php echo $year; ?>;
        var today = new Date();
        var defDate = today.getFullYear() === y ? defaultDateToday : defaultDateJan1;
        $('#new-holiday-date').val(defDate);
        $('#new-holiday-name').focus();
    });

    // Add holiday
    $('#btn-save-holiday').on('click', function(){
        var name = $.trim($('#new-holiday-name').val());
        var date = $('#new-holiday-date').val();
        var type = $('#new-holiday-type').val();
        if (!name || !date) { alert('Name and date are required.'); return; }

        $.post(baseUrl + '/add', {
            name: name, holiday_date: date, type: type,
            [csrfName]: csrfHash
        }, function(r){
            if (r.success) {
                location.reload();
            } else {
                alert(r.message || 'Error saving holiday.');
            }
        }, 'json');
    });

    // Delete holiday
    $(document).on('click', '.btn-delete-holiday', function(e){
        e.preventDefault();
        if (!confirm('Delete this holiday?')) return;
        var id = $(this).data('id');
        $.post(baseUrl + '/delete/' + id, { [csrfName]: csrfHash }, function(r){
            if (r.success) location.reload();
            else alert('Error deleting.');
        }, 'json');
    });

    // Save weekly off
    $('#weekly-off-form').on('submit', function(e){
        e.preventDefault();
        var days = [];
        $('input[name="weekly_off_days[]"]:checked').each(function(){
            days.push($(this).val());
        });
        $.post(baseUrl + '/save_weekly_off', {
            'weekly_off_days[]': days,
            [csrfName]: csrfHash
        }, function(r){
            if (r.success) {
                $('#weekly-off-saved').fadeIn().delay(2000).fadeOut();
            }
        }, 'json');
    });

    // Send holiday announcement manually (e.g. if the automated day-before
    // email failed) - uses the exact same "holiday_reminder" email template.
    $(document).on('click', '.btn-send-announcement', function(e){
        e.preventDefault();
        if (!confirm('Send the holiday announcement email now?')) return;
        var id = $(this).data('id');
        $.post(baseUrl + '/send_announcement/' + id, { [csrfName]: csrfHash }, function(r){
            alert_float(r.success ? 'success' : 'danger', r.message);
        }, 'json');
    });

    // Team calendar month navigation - loads just the calendar panel via AJAX
    // instead of reloading the whole page.
    function loadTeamCalendar(calYear, calMonth) {
        var $panel = $('#team-calendar-panel');
        $.getJSON(baseUrl + '/calendar', { cal_year: calYear, cal_month: calMonth }, function(r){
            $panel.html(r.html);
            $panel.find('[data-toggle="popover"]').popover();
        });
    }
    $(document).on('click', '#team-calendar-panel .cal-nav', function(){
        loadTeamCalendar($(this).data('cal-year'), $(this).data('cal-month'));
    });
});
</script>
