<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object $training        */
/** @var array  $participants    */
/** @var array  $sessions        */
/** @var array  $days            */
/** @var array  $attendance_grid */

// Same visual design as the printable report (training/report.php), rebuilt with
// tables + inline styles only - email clients (Outlook, Gmail, etc.) strip <style>
// blocks and don't support flexbox/grid, so this can't just reuse that markup.
$badge     = ['scheduled' => '#64748b', 'ongoing' => '#d97706', 'completed' => '#16a34a', 'cancelled' => '#dc2626'];
$att_badge = ['pending' => '#64748b', 'present' => '#16a34a', 'absent' => '#dc2626', 'partial' => '#d97706'];
$instructor_label = $training->instructor_name ?: $training->trainer;

$status_counts = ['present' => 0, 'absent' => 0, 'pending' => 0, 'partial' => 0];
foreach ($participants as $p) {
    if (isset($status_counts[$p->attendance_status])) $status_counts[$p->attendance_status]++;
}
$enrolled_count  = count($participants);
$completion_rate = $training->total_day_marks > 0
    ? round($training->present_day_marks / $training->total_day_marks * 100) : 0;

$company_name = get_option('companyname') ?: 'Company';
$status_color = $badge[$training->status] ?? '#64748b';

$stat_cells = [
    [$enrolled_count, 'Enrolled'],
    [$status_counts['present'], 'Present (all days)'],
    [$status_counts['partial'], 'Partial'],
    [$status_counts['absent'], 'Absent (all days)'],
    [$status_counts['pending'], 'Pending'],
    [$completion_rate . '%', 'Attendance Rate'],
];

if (!function_exists('hr_badge_span')) {
    function hr_badge_span($text, $color)
    {
        return '<span style="display:inline-block;padding:2px 9px;border-radius:3px;background:' . $color . ';color:#fff;font-size:10.5px;font-weight:600;line-height:1.6">' . htmlspecialchars($text) . '</span>';
    }
}
?>
<div style="max-width:680px;margin:0 auto;border:1px solid #e2e2e2;border-radius:6px;padding:22px;font-family:Arial,Helvetica,sans-serif;color:#222">

  <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:2px solid #333;padding-bottom:12px;margin-bottom:16px">
    <tr>
      <td style="font-size:18px;font-weight:700;color:#222;vertical-align:top"><?php echo htmlspecialchars($company_name); ?></td>
      <td style="text-align:right;vertical-align:top">
        <div style="font-size:14px;font-weight:700;letter-spacing:.5px;color:#222">TRAINING REPORT</div>
        <div style="font-size:11px;color:#666;margin-top:2px">Generated: <?php echo date('d M Y'); ?></div>
      </td>
    </tr>
  </table>

  <div style="margin-bottom:14px">
    <span style="font-size:16px;font-weight:700"><?php echo htmlspecialchars($training->title); ?></span>
    <span style="margin-left:8px"><?php echo hr_badge_span(ucfirst($training->status), $status_color); ?></span>
  </div>

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f8f8;border:1px solid #e2e2e2;border-radius:4px;margin-bottom:18px">
    <tr>
      <td style="padding:12px 16px;font-size:12px;width:50%;vertical-align:top">
        <strong>Instructor:</strong> <?php echo htmlspecialchars($instructor_label ?: '-'); ?><br>
        <strong>Cost:</strong> <?php echo number_format($training->cost, 2); ?>
      </td>
      <td style="padding:12px 16px;font-size:12px;width:50%;vertical-align:top">
        <strong>Venue:</strong> <?php echo htmlspecialchars($training->venue ?: '-'); ?><br>
        <strong>Capacity:</strong> <?php echo $training->capacity ?: 'Unlimited'; ?>
      </td>
    </tr>
  </table>

  <?php if (!empty($sessions)): ?>
  <div style="font-size:13px;font-weight:700;border-bottom:1px solid #ddd;padding-bottom:5px;margin-bottom:10px">Sessions</div>
  <div style="margin-bottom:18px;line-height:2.2">
    <?php foreach ($sessions as $s): ?>
    <span style="display:inline-block;background:#f0f0f0;border-radius:3px;padding:4px 10px;margin-right:5px;font-size:11.5px">
      <?php echo date('d M Y', strtotime($s->session_date)); ?>
      <?php if ($s->start_time || $s->end_time): ?>
      (<?php echo $s->start_time ? date('g:i A', strtotime($s->start_time)) : '?'; ?>&ndash;<?php echo $s->end_time ? date('g:i A', strtotime($s->end_time)) : '?'; ?>)
      <?php endif; ?>
    </span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px">
    <tr>
      <?php foreach ($stat_cells as $i => $cell): ?>
      <td style="background:#f8fafc;border-radius:6px;padding:12px 2px;text-align:center;width:16.6%">
        <div style="font-size:17px;font-weight:700;color:#222"><?php echo $cell[0]; ?></div>
        <div style="font-size:9.5px;color:#64748b;margin-top:2px"><?php echo $cell[1]; ?></div>
      </td>
      <?php if ($i < count($stat_cells) - 1): ?><td width="6"></td><?php endif; ?>
      <?php endforeach; ?>
    </tr>
  </table>

  <div style="font-size:13px;font-weight:700;border-bottom:1px solid #ddd;padding-bottom:5px;margin-bottom:10px">Participants</div>
  <?php if (empty($participants)): ?>
  <p style="text-align:center;color:#888;font-size:12px;padding:10px 0">No participants enrolled.</p>
  <?php else: ?>
  <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:18px;font-size:11.5px">
    <tr>
      <th style="background:#f0f0f0;border:1px solid #ddd;padding:7px 8px;text-align:left">Employee</th>
      <th style="background:#f0f0f0;border:1px solid #ddd;padding:7px 8px;text-align:left">Department</th>
      <th style="background:#f0f0f0;border:1px solid #ddd;padding:7px 8px;text-align:left">Enrolled On</th>
      <th style="background:#f0f0f0;border:1px solid #ddd;padding:7px 8px;text-align:left">Attendance</th>
    </tr>
    <?php foreach ($participants as $p): ?>
    <tr>
      <td style="border:1px solid #ddd;padding:7px 8px"><?php echo htmlspecialchars($p->first_name . ' ' . $p->last_name); ?> (<?php echo htmlspecialchars($p->employee_code); ?>)</td>
      <td style="border:1px solid #ddd;padding:7px 8px"><?php echo htmlspecialchars($p->department_name ?? '-'); ?></td>
      <td style="border:1px solid #ddd;padding:7px 8px"><?php echo date('d M Y', strtotime($p->enrolled_at)); ?></td>
      <td style="border:1px solid #ddd;padding:7px 8px">
        <?php echo hr_badge_span(ucfirst($p->attendance_status), $att_badge[$p->attendance_status] ?? '#64748b'); ?>
        <?php if ($p->total_days > 0): ?>
        <span style="color:#888;font-size:10.5px"> (<?php echo $p->present_days; ?>/<?php echo $p->total_days; ?> days)</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <?php if (!empty($participants) && !empty($days)): ?>
  <div style="font-size:13px;font-weight:700;border-bottom:1px solid #ddd;padding-bottom:5px;margin-bottom:10px">Daily Attendance</div>
  <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:18px;font-size:11.5px">
    <tr>
      <th style="background:#f0f0f0;border:1px solid #ddd;padding:7px 8px;text-align:left">Employee</th>
      <?php foreach ($days as $day): ?>
      <th style="background:#f0f0f0;border:1px solid #ddd;padding:7px 8px;text-align:center"><?php echo date('d M', strtotime($day)); ?></th>
      <?php endforeach; ?>
    </tr>
    <?php foreach ($participants as $p): ?>
    <tr>
      <td style="border:1px solid #ddd;padding:7px 8px"><?php echo htmlspecialchars($p->first_name . ' ' . $p->last_name); ?></td>
      <?php foreach ($days as $day): ?>
      <?php $day_status = $attendance_grid[$p->employee_id][$day] ?? 'pending'; ?>
      <td style="border:1px solid #ddd;padding:7px 8px;text-align:center">
        <?php echo hr_badge_span(ucfirst($day_status), $att_badge[$day_status] ?? '#64748b'); ?>
      </td>
      <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <div style="border-top:1px solid #ccc;padding-top:10px;font-size:11px;color:#888">
    Generated: <?php echo date('d M Y H:i'); ?> &mdash; This is a computer-generated training report and does not require a signature.
  </div>
</div>
