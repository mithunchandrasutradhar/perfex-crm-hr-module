<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/** @var int    $year           */
/** @var array  $holidays       */
/** @var array  $weekly_off     */
/** @var bool   $can_edit       */
/** @var int    $cal_year       */
/** @var int    $cal_month      */
/** @var array  $cal_holidays   */
/** @var array  $cal_leave_days */
/** @var array  $cal_shifts     */
/** @var string $roster_date    */
/** @var array  $shift_roster   */
if (!isset($year))           $year           = (int) date('Y');
if (!isset($holidays))       $holidays       = [];
if (!isset($weekly_off))     $weekly_off     = [5];
if (!isset($can_edit))       $can_edit       = false;
if (!isset($cal_year))       $cal_year       = (int) date('Y');
if (!isset($cal_month))      $cal_month      = (int) date('n');
if (!isset($cal_holidays))   $cal_holidays   = [];
if (!isset($cal_leave_days)) $cal_leave_days = [];
if (!isset($cal_shifts))     $cal_shifts     = [];
if (!isset($roster_date))    $roster_date    = date('Y-m-d');
if (!isset($shift_roster))   $shift_roster   = [];

$day_names = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

// Group approved leave days by date for quick per-cell lookup on the calendar below.
$leave_by_date = [];
foreach ($cal_leave_days as $ld) {
    $leave_by_date[$ld->leave_date][] = $ld;
}

$cal_prev_month = $cal_month - 1; $cal_prev_year = $cal_year;
if ($cal_prev_month < 1) { $cal_prev_month = 12; $cal_prev_year--; }
$cal_next_month = $cal_month + 1; $cal_next_year = $cal_year;
if ($cal_next_month > 12) { $cal_next_month = 1; $cal_next_year++; }

$cal_first_ts    = mktime(0, 0, 0, $cal_month, 1, $cal_year);
$cal_days_in_month = (int) date('t', $cal_first_ts);
$cal_first_dow     = (int) date('w', $cal_first_ts); // 0=Sun..6=Sat, matches weekly_off encoding

// Each shift assignment is a date RANGE (not per-day rows like leave), so expand it
// into a lookup by date, clipped to this calendar month.
$cal_month_from = sprintf('%04d-%02d-01', $cal_year, $cal_month);
$cal_month_to   = sprintf('%04d-%02d-%02d', $cal_year, $cal_month, $cal_days_in_month);
$shifts_by_date = [];
foreach ($cal_shifts as $sh) {
    $clip_from = max($sh->from_date, $cal_month_from);
    $clip_to   = min($sh->to_date, $cal_month_to);
    for ($ts = strtotime($clip_from); $ts <= strtotime($clip_to); $ts += 86400) {
        $shifts_by_date[date('Y-m-d', $ts)][] = $sh;
    }
}
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
                  <input type="date" id="new-holiday-date" class="form-control input-sm" value="">
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
                  <?php if ($can_edit): ?><th style="width:60px"></th><?php endif; ?>
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

    <!-- ── Who's on Leave (calendar) ── -->
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-3">
            <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-users tw-mr-2 text-primary"></i>Who's on Leave</h5>
            <div class="tw-flex tw-items-center tw-gap-2">
              <a href="?year=<?php echo $year; ?>&cal_year=<?php echo $cal_prev_year; ?>&cal_month=<?php echo $cal_prev_month; ?>"
                 class="btn btn-default btn-sm"><i class="fa fa-chevron-left"></i></a>
              <span class="tw-font-semibold"><?php echo date('F', $cal_first_ts) . ' ' . $cal_year; ?></span>
              <a href="?year=<?php echo $year; ?>&cal_year=<?php echo $cal_next_year; ?>&cal_month=<?php echo $cal_next_month; ?>"
                 class="btn btn-default btn-sm"><i class="fa fa-chevron-right"></i></a>
            </div>
          </div>
          <div class="panel-body">
            <div class="table-responsive">
            <table class="table table-bordered tw-mb-0" style="table-layout:fixed">
              <thead>
                <tr>
                  <?php foreach ($day_names as $idx => $dname): ?>
                  <th class="text-center <?php echo in_array($idx, $weekly_off) ? 'tw-bg-neutral-100' : ''; ?>"><?php echo substr($dname, 0, 3); ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php
                $day_num = 1 - $cal_first_dow;
                while ($day_num <= $cal_days_in_month):
                ?>
                <tr>
                  <?php for ($dow = 0; $dow < 7; $dow++, $day_num++): ?>
                    <?php if ($day_num < 1 || $day_num > $cal_days_in_month): ?>
                    <td class="tw-bg-neutral-50"></td>
                    <?php else: ?>
                    <?php
                      $cell_date    = sprintf('%04d-%02d-%02d', $cal_year, $cal_month, $day_num);
                      $is_off       = in_array($dow, $weekly_off);
                      $holiday_name = $cal_holidays[$cell_date] ?? null;
                      $on_leave     = $leave_by_date[$cell_date] ?? [];
                    ?>
                    <td class="<?php echo $is_off ? 'tw-bg-neutral-50' : ''; ?>" style="vertical-align:top;height:85px">
                      <strong class="<?php echo $is_off ? 'text-muted' : ''; ?>"><?php echo $day_num; ?></strong>
                      <?php if ($holiday_name): ?>
                      <div class="tw-text-xs tw-mt-1">
                        <span class="label label-danger" title="<?php echo htmlspecialchars($holiday_name); ?>">
                          <?php echo htmlspecialchars($holiday_name); ?>
                        </span>
                      </div>
                      <?php endif; ?>
                      <?php
                        $visible_leave = array_slice($on_leave, 0, 2);
                        $hidden_leave  = array_slice($on_leave, 2);
                      ?>
                      <?php foreach ($visible_leave as $ld): ?>
                      <div class="tw-text-xs tw-mt-1">
                        <span class="label label-warning" title="<?php echo htmlspecialchars(hr_leave_day_type_label($ld->day_type)); ?>">
                          <i class="fa fa-user tw-mr-1"></i><?php echo htmlspecialchars($ld->employee_name); ?>
                        </span>
                      </div>
                      <?php endforeach; ?>
                      <?php if (!empty($hidden_leave)): ?>
                      <?php
                        $hidden_html = '';
                        foreach ($hidden_leave as $ld) {
                            $hidden_html .= '<div class="tw-text-xs tw-mb-1"><i class="fa fa-user tw-mr-1"></i>' . htmlspecialchars($ld->employee_name) . '</div>';
                        }
                      ?>
                      <div class="tw-text-xs tw-mt-1">
                        <span class="label label-default pointer" data-toggle="popover" data-trigger="hover click"
                              data-html="true" data-placement="top" data-container="body" title="On Leave"
                              data-content="<?php echo htmlspecialchars($hidden_html); ?>">
                          +<?php echo count($hidden_leave); ?> more
                        </span>
                      </div>
                      <?php endif; ?>
                    </td>
                    <?php endif; ?>
                  <?php endfor; ?>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
            </div>
            <?php if (empty($cal_leave_days)): ?>
            <p class="text-muted tw-text-sm tw-mt-3 tw-mb-0"><i class="fa fa-info-circle tw-mr-1"></i>No approved leave for this month.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Shift Roster (calendar) ── -->
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-3">
            <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-user-clock tw-mr-2 text-primary"></i>Shift Roster</h5>
            <div class="tw-flex tw-items-center tw-gap-2">
              <a href="?year=<?php echo $year; ?>&cal_year=<?php echo $cal_prev_year; ?>&cal_month=<?php echo $cal_prev_month; ?>"
                 class="btn btn-default btn-sm"><i class="fa fa-chevron-left"></i></a>
              <span class="tw-font-semibold"><?php echo date('F', $cal_first_ts) . ' ' . $cal_year; ?></span>
              <a href="?year=<?php echo $year; ?>&cal_year=<?php echo $cal_next_year; ?>&cal_month=<?php echo $cal_next_month; ?>"
                 class="btn btn-default btn-sm"><i class="fa fa-chevron-right"></i></a>
            </div>
          </div>
          <div class="panel-body">
            <div class="table-responsive">
            <table class="table table-bordered tw-mb-0" style="table-layout:fixed">
              <thead>
                <tr>
                  <?php foreach ($day_names as $idx => $dname): ?>
                  <th class="text-center <?php echo in_array($idx, $weekly_off) ? 'tw-bg-neutral-100' : ''; ?>"><?php echo substr($dname, 0, 3); ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php
                $day_num = 1 - $cal_first_dow;
                while ($day_num <= $cal_days_in_month):
                ?>
                <tr>
                  <?php for ($dow = 0; $dow < 7; $dow++, $day_num++): ?>
                    <?php if ($day_num < 1 || $day_num > $cal_days_in_month): ?>
                    <td class="tw-bg-neutral-50"></td>
                    <?php else: ?>
                    <?php
                      $cell_date = sprintf('%04d-%02d-%02d', $cal_year, $cal_month, $day_num);
                      $is_off    = in_array($dow, $weekly_off);
                      $on_shift  = $shifts_by_date[$cell_date] ?? [];
                      $visible_shift = array_slice($on_shift, 0, 2);
                      $hidden_shift  = array_slice($on_shift, 2);
                    ?>
                    <td class="<?php echo $is_off ? 'tw-bg-neutral-50' : ''; ?>" style="vertical-align:top;height:85px">
                      <strong class="<?php echo $is_off ? 'text-muted' : ''; ?>"><?php echo $day_num; ?></strong>
                      <?php foreach ($visible_shift as $sh): ?>
                      <div class="tw-text-xs tw-mt-1">
                        <span class="label label-info" title="<?php echo htmlspecialchars($sh->shift_name); ?>">
                          <i class="fa fa-user tw-mr-1"></i><?php echo htmlspecialchars($sh->employee_name); ?>
                        </span>
                      </div>
                      <?php endforeach; ?>
                      <?php if (!empty($hidden_shift)): ?>
                      <?php
                        $hidden_html = '';
                        foreach ($hidden_shift as $sh) {
                            $hidden_html .= '<div class="tw-text-xs tw-mb-1"><i class="fa fa-user tw-mr-1"></i>' . htmlspecialchars($sh->employee_name) . ' - ' . htmlspecialchars($sh->shift_name) . '</div>';
                        }
                      ?>
                      <div class="tw-text-xs tw-mt-1">
                        <span class="label label-default pointer" data-toggle="popover" data-trigger="hover click"
                              data-html="true" data-placement="top" data-container="body" title="On Shift"
                              data-content="<?php echo htmlspecialchars($hidden_html); ?>">
                          +<?php echo count($hidden_shift); ?> more
                        </span>
                      </div>
                      <?php endif; ?>
                    </td>
                    <?php endif; ?>
                  <?php endfor; ?>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
            </div>
            <?php if (empty($cal_shifts)): ?>
            <p class="text-muted tw-text-sm tw-mt-3 tw-mb-0"><i class="fa fa-info-circle tw-mr-1"></i>No approved shift assignments for this month.</p>
            <?php endif; ?>
          </div>
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
    $('#btn-add-holiday').on('click', function(){
        $('#add-holiday-form').slideToggle(150);
        var y = <?php echo $year; ?>;
        var today = new Date();
        var defDate = today.getFullYear() === y
            ? today.toISOString().split('T')[0]
            : (y + '-01-01');
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
});
</script>
