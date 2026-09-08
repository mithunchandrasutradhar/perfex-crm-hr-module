<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $weekly_off */
if (!isset($weekly_off)) $weekly_off = [];
?>
<div class="panel-heading tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-3">
  <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-users tw-mr-2 text-primary"></i>Who's on Leave / Shift Roster</h5>
  <div class="tw-flex tw-items-center tw-gap-4 tw-text-xs">
    <span><span class="label label-danger"><i class="fa fa-calendar-times"></i></span> Holiday</span>
    <span><span class="label label-warning"><i class="fa fa-plane"></i></span> On Leave</span>
    <span><span class="label label-info"><i class="fa fa-clock"></i></span> On Shift</span>
  </div>
</div>
<div class="panel-body">
  <div id="hr-team-calendar"></div>
</div>
<style>
/* Matches the rest of the module's weekly-off greying convention
   (see leave/apply.php's calendar preview) applied to FullCalendar's own
   day-cell classes instead of a hand-rolled table. */
#hr-team-calendar .hr-cal-weekly-off { background-color: #fafafa; }
#hr-team-calendar .fc-daygrid-day-number { color: #475569; }
#hr-team-calendar .hr-cal-weekly-off .fc-daygrid-day-number { color: #94a3b8; }
</style>
<script>
(function(){
    var weeklyOff = <?php echo json_encode(array_map('intval', $weekly_off)); ?>;
    var baseUrl   = '<?php echo admin_url('hr_module/holidays'); ?>';

    document.addEventListener('DOMContentLoaded', function(){
        var el = document.getElementById('hr-team-calendar');
        if (!el || typeof FullCalendar === 'undefined') return;

        var calendar = new FullCalendar.Calendar(el, {
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
            height: 'auto',
            dayMaxEventRows: 3,
            eventDisplay: 'block',
            firstDay: parseInt(app.options.calendar_first_day),
            dayCellClassNames: function(arg) {
                return weeklyOff.indexOf(arg.date.getDay()) !== -1 ? ['hr-cal-weekly-off'] : [];
            },
            events: function(info, successCallback, failureCallback) {
                $.getJSON(baseUrl + '/calendar', { start: info.startStr, end: info.endStr })
                    .done(successCallback)
                    .fail(function(){ failureCallback(); });
            },
        });
        calendar.render();
    });
})();
</script>
