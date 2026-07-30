<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var int    $cal_year       */
/** @var int    $cal_month      */
/** @var array  $weekly_off     */
/** @var array  $cal_holidays   */
/** @var array  $leave_by_date  */
/** @var array  $shifts_by_date */

$day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

$cal_prev_month = $cal_month - 1; $cal_prev_year = $cal_year;
if ($cal_prev_month < 1) { $cal_prev_month = 12; $cal_prev_year--; }
$cal_next_month = $cal_month + 1; $cal_next_year = $cal_year;
if ($cal_next_month > 12) { $cal_next_month = 1; $cal_next_year++; }

$cal_first_ts      = mktime(0, 0, 0, $cal_month, 1, $cal_year);
$cal_days_in_month = (int) date('t', $cal_first_ts);
$cal_first_dow     = (int) date('w', $cal_first_ts); // 0=Sun..6=Sat, matches weekly_off encoding

// Renders ONE compact badge per type (leave/shift) for a day, regardless of how
// many people it covers - a busy day with 20 people on leave must not blow up
// the cell's height. A single person shows their name directly; 2+ collapses
// to a count ("5 on Leave") with a click/hover popover listing everyone, so
// there's always a clear "see more" instead of the cell growing unbounded.
if (!function_exists('hr_calendar_summary_badge')) {
    function hr_calendar_summary_badge(array $entries, $type)
    {
        if (empty($entries)) {
            return '';
        }
        $color = $type === 'leave' ? 'label-warning' : 'label-info';
        $icon  = $type === 'leave' ? 'fa-plane' : 'fa-clock';
        $noun  = $type === 'leave' ? 'Leave' : 'Shift';

        if (count($entries) === 1) {
            $e     = $entries[0];
            $title = $type === 'leave' ? hr_leave_day_type_label($e->day_type) : $e->shift_name;
            return '<div class="tw-text-xs tw-mt-1"><span class="label ' . $color . '" title="On ' . $noun . ': ' . htmlspecialchars($title) . '">'
                . '<i class="fa ' . $icon . ' tw-mr-1"></i>' . htmlspecialchars($e->employee_name) . '</span></div>';
        }

        $list_html = '';
        foreach ($entries as $e) {
            $sub = $type === 'leave' ? hr_leave_day_type_label($e->day_type) : $e->shift_name;
            $list_html .= '<div class="tw-text-xs tw-mb-1"><i class="fa fa-user tw-mr-1"></i>' . htmlspecialchars($e->employee_name) . ' (' . htmlspecialchars($sub) . ')</div>';
        }
        return '<div class="tw-text-xs tw-mt-1">'
            . '<span class="label ' . $color . ' pointer" data-toggle="popover" data-trigger="hover click" data-html="true" data-placement="top" data-container="body" title="On ' . $noun . '"'
            . ' data-content="' . htmlspecialchars($list_html) . '">'
            . '<i class="fa ' . $icon . ' tw-mr-1"></i>' . count($entries) . ' on ' . $noun . '</span></div>';
    }
}
?>
<div class="panel-heading tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-3">
  <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-users tw-mr-2 text-primary"></i>Who's on Leave / Shift Roster</h5>
  <div class="tw-flex tw-items-center tw-gap-2">
    <button type="button" class="btn btn-default btn-sm cal-nav" data-cal-year="<?php echo $cal_prev_year; ?>" data-cal-month="<?php echo $cal_prev_month; ?>"><i class="fa fa-chevron-left"></i></button>
    <span class="tw-font-semibold" id="cal-month-label"><?php echo date('F', $cal_first_ts) . ' ' . $cal_year; ?></span>
    <button type="button" class="btn btn-default btn-sm cal-nav" data-cal-year="<?php echo $cal_next_year; ?>" data-cal-month="<?php echo $cal_next_month; ?>"><i class="fa fa-chevron-right"></i></button>
  </div>
</div>
<div class="panel-body">
  <div class="tw-flex tw-items-center tw-gap-4 tw-mb-3 tw-text-xs">
    <span><span class="label label-warning"><i class="fa fa-plane"></i></span> On Leave</span>
    <span><span class="label label-info"><i class="fa fa-clock"></i></span> On Shift</span>
    <span><span class="label label-danger"><i class="fa fa-calendar-times"></i></span> Holiday</span>
  </div>
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
          ?>
          <td class="<?php echo $is_off ? 'tw-bg-neutral-50' : ''; ?>" style="vertical-align:top;height:95px">
            <strong class="<?php echo $is_off ? 'text-muted' : ''; ?>"><?php echo $day_num; ?></strong>
            <?php if ($holiday_name): ?>
            <div class="tw-text-xs tw-mt-1">
              <span class="label label-danger" title="<?php echo htmlspecialchars($holiday_name); ?>">
                <i class="fa fa-calendar-times tw-mr-1"></i><?php echo htmlspecialchars($holiday_name); ?>
              </span>
            </div>
            <?php endif; ?>
            <?php echo hr_calendar_summary_badge($leave_by_date[$cell_date] ?? [], 'leave'); ?>
            <?php echo hr_calendar_summary_badge($shifts_by_date[$cell_date] ?? [], 'shift'); ?>
          </td>
          <?php endif; ?>
        <?php endfor; ?>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  </div>
  <?php if (empty($cal_holidays) && empty($leave_by_date) && empty($shifts_by_date)): ?>
  <p class="text-muted tw-text-sm tw-mt-3 tw-mb-0"><i class="fa fa-info-circle tw-mr-1"></i>No holidays, leave, or shift assignments for this month.</p>
  <?php endif; ?>
</div>
