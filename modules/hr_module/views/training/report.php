<?php defined('BASEPATH') or exit('No direct script access allowed');
$badge     = ['scheduled'=>'#64748b','ongoing'=>'#d97706','completed'=>'#16a34a','cancelled'=>'#dc2626'];
$att_badge = ['pending'=>'#64748b','present'=>'#16a34a','absent'=>'#dc2626','partial'=>'#d97706'];
$instructor_label = $training->instructor_name ?: $training->trainer;
$sessions_by_date = array_column($sessions, null, 'session_date');

$status_counts = ['present' => 0, 'absent' => 0, 'pending' => 0, 'partial' => 0];
foreach ($participants as $p) {
    if (isset($status_counts[$p->attendance_status])) $status_counts[$p->attendance_status]++;
}
$enrolled_count = count($participants);
// Attendance rate is measured per day, not per participant, so a partially
// attended multi-day training still shows an accurate overall rate.
$completion_rate = $training->total_day_marks > 0
    ? round($training->present_day_marks / $training->total_day_marks * 100) : 0;

$company_name = get_option('companyname') ?: 'Company Name';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo _l('hr_training_report'); ?> — <?php echo htmlspecialchars($training->title); ?></title>
<style>
  body{font-family:Arial,sans-serif;font-size:13px;color:#222;margin:0;padding:20px}
  .report{max-width:1000px;margin:0 auto;border:1px solid #ccc;padding:24px}
  .header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #333;padding-bottom:12px;margin-bottom:16px}
  .company h2{margin:0;font-size:18px} .company p{margin:2px 0;font-size:11px;color:#555}
  .report-title{text-align:right} .report-title h3{margin:0;font-size:15px} .report-title p{margin:2px 0;font-size:11px}
  .info-box{background:#f8f8f8;border:1px solid #e2e2e2;padding:10px 14px;margin-bottom:14px;border-radius:4px}
  .info-box table{width:100%;border-collapse:collapse} .info-box td{padding:2px 8px;font-size:12px;vertical-align:top}
  .info-box td:nth-child(odd){font-weight:600;white-space:nowrap}
  .stats{display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap}
  .stat-box{flex:1;min-width:100px;background:#f8fafc;border-radius:6px;padding:10px;text-align:center}
  .stat-box .val{font-size:1.3rem;font-weight:700} .stat-box .lbl{font-size:0.75rem;color:#64748b}
  h5.section{margin:16px 0 8px;font-size:13px;font-weight:bold;border-bottom:1px solid #ddd;padding-bottom:4px}
  table.data{width:100%;border-collapse:collapse}
  table.data td,table.data th{padding:6px 8px;border:1px solid #ddd;font-size:11.5px;vertical-align:middle}
  table.data th{background:#f0f0f0;font-weight:600}
  .badge{display:inline-block;padding:2px 8px;border-radius:3px;color:#fff;font-size:11px}
  .note-box{background:#fbfbfb;border:1px solid #e2e2e2;border-radius:4px;padding:10px 14px;white-space:pre-wrap;font-size:12px}
  .footer{margin-top:20px;border-top:1px solid #ccc;padding-top:10px;display:flex;justify-content:space-between;font-size:11px;color:#888}
  @media print{body{padding:0} .no-print{display:none}}
</style>
</head>
<body>
<div class="no-print" style="text-align:center;margin-bottom:16px">
  <button onclick="window.print()" style="padding:8px 20px;font-size:14px;cursor:pointer">🖨 Print / Save PDF</button>
</div>
<div class="report">
  <div class="header">
    <div class="company">
      <h2><?php echo htmlspecialchars($company_name); ?></h2>
      <p><?php echo get_option('company_city'); ?> <?php echo get_option('company_country'); ?></p>
    </div>
    <div class="report-title">
      <h3><?php echo strtoupper(_l('hr_training_report')); ?></h3>
      <p>Generated: <?php echo date('d M Y'); ?></p>
    </div>
  </div>

  <h4 style="margin:0 0 8px"><?php echo htmlspecialchars($training->title); ?>
    <span class="badge" style="background:<?php echo $badge[$training->status] ?? '#64748b'; ?>"><?php echo ucfirst($training->status); ?></span>
  </h4>

  <div class="info-box">
    <table>
      <tr>
        <td>Instructor:</td><td><?php echo htmlspecialchars($instructor_label ?: '-'); ?></td>
        <td>Venue:</td><td><?php echo htmlspecialchars($training->venue ?: '-'); ?></td>
      </tr>
      <tr>
        <td>Cost:</td><td><?php echo number_format($training->cost, 2); ?></td>
        <td>Capacity:</td><td><?php echo $training->capacity ?: 'Unlimited'; ?></td>
      </tr>
    </table>
  </div>

  <?php if (!empty($sessions)): ?>
  <h5 class="section"><?php echo _l('hr_training_sessions'); ?></h5>
  <p>
    <?php foreach ($sessions as $s): ?>
    <span style="display:inline-block;background:#f0f0f0;border-radius:3px;padding:3px 8px;margin:2px;font-size:11.5px">
      <?php echo date('d M Y', strtotime($s->session_date)); ?>
      <?php if ($s->start_time || $s->end_time): ?>
      (<?php echo $s->start_time ? date('g:i A', strtotime($s->start_time)) : '?'; ?>&ndash;<?php echo $s->end_time ? date('g:i A', strtotime($s->end_time)) : '?'; ?>)
      <?php endif; ?>
    </span>
    <?php endforeach; ?>
  </p>
  <?php endif; ?>

  <?php if ($training->description): ?>
  <h5 class="section">Description / Objectives</h5>
  <!-- Authored via the tinymce editor on Add/Edit (real HTML), so rendered
       as-is here rather than escaped/nl2br'd plain text. -->
  <div><?php echo $training->description; ?></div>
  <?php endif; ?>

  <div class="stats">
    <div class="stat-box"><div class="val"><?php echo $enrolled_count; ?></div><div class="lbl"><?php echo _l('hr_training_enrolled'); ?></div></div>
    <div class="stat-box"><div class="val"><?php echo $status_counts['present']; ?></div><div class="lbl">Present (all days)</div></div>
    <div class="stat-box"><div class="val"><?php echo $status_counts['partial']; ?></div><div class="lbl">Partial</div></div>
    <div class="stat-box"><div class="val"><?php echo $status_counts['absent']; ?></div><div class="lbl">Absent (all days)</div></div>
    <div class="stat-box"><div class="val"><?php echo $status_counts['pending']; ?></div><div class="lbl">Pending</div></div>
    <div class="stat-box"><div class="val"><?php echo $completion_rate; ?>%</div><div class="lbl">Attendance Rate</div></div>
  </div>

  <h5 class="section"><?php echo _l('hr_training_participants'); ?></h5>
  <?php if (empty($participants)): ?>
  <p style="text-align:center;color:#888;padding:10px">No participants enrolled.</p>
  <?php else: ?>
  <table class="data">
    <thead><tr>
      <th><?php echo _l('hr_employee'); ?></th>
      <th><?php echo _l('hr_department'); ?></th>
      <th>Enrolled On</th>
      <th><?php echo _l('hr_training_attendance'); ?></th>
      <th><?php echo _l('hr_training_add_note'); ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($participants as $p): ?>
      <tr>
        <td><?php echo htmlspecialchars($p->first_name.' '.$p->last_name); ?> (<?php echo $p->employee_code; ?>)</td>
        <td><?php echo htmlspecialchars($p->department_name ?? '-'); ?></td>
        <td><?php echo date('d M Y', strtotime($p->enrolled_at)); ?></td>
        <td>
          <span class="badge" style="background:<?php echo $att_badge[$p->attendance_status] ?? '#64748b'; ?>"><?php echo ucfirst($p->attendance_status); ?></span>
          <?php if ($p->total_days > 0): ?>
          <span style="color:#888"> (<?php echo $p->present_days; ?>/<?php echo $p->total_days; ?> days present)</span>
          <?php endif; ?>
        </td>
        <td><?php echo $p->notes ? nl2br(htmlspecialchars($p->notes)) : '-'; ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php if (!empty($participants) && !empty($days)): ?>
  <h5 class="section"><?php echo _l('hr_training_daily_attendance'); ?></h5>
  <table class="data">
    <thead><tr>
      <th><?php echo _l('hr_employee'); ?></th>
      <?php foreach ($days as $day): ?>
      <?php $sess = $sessions_by_date[$day] ?? null; ?>
      <th style="text-align:center">
        <?php echo date('d M', strtotime($day)); ?>
        <?php if ($sess && ($sess->start_time || $sess->end_time)): ?>
        <br><span style="font-weight:normal;color:#888"><?php echo $sess->start_time ? date('g:i A', strtotime($sess->start_time)) : '?'; ?>&ndash;<?php echo $sess->end_time ? date('g:i A', strtotime($sess->end_time)) : '?'; ?></span>
        <?php endif; ?>
      </th>
      <?php endforeach; ?>
    </tr></thead>
    <tbody>
      <?php foreach ($participants as $p): ?>
      <tr>
        <td><?php echo htmlspecialchars($p->first_name.' '.$p->last_name); ?></td>
        <?php foreach ($days as $day): ?>
        <?php $day_status = $attendance_grid[$p->employee_id][$day] ?? 'pending'; ?>
        <td style="text-align:center">
          <span class="badge" style="background:<?php echo $att_badge[$day_status] ?? '#64748b'; ?>"><?php echo ucfirst($day_status); ?></span>
        </td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php $has_feedback = false; foreach ($participants as $p) { if (!empty($p->employee_feedback)) { $has_feedback = true; break; } } ?>
  <?php if ($has_feedback): ?>
  <h5 class="section"><?php echo _l('hr_training_employee_feedback'); ?></h5>
  <?php foreach ($participants as $p): if (empty($p->employee_feedback)) continue; ?>
  <div class="note-box" style="margin-bottom:8px">
    <strong><?php echo htmlspecialchars($p->first_name.' '.$p->last_name); ?>:</strong>
    <?php echo nl2br(htmlspecialchars($p->employee_feedback)); ?>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($training->completion_note): ?>
  <h5 class="section"><?php echo _l('hr_training_completion_note'); ?></h5>
  <div class="note-box"><?php echo nl2br(htmlspecialchars($training->completion_note)); ?></div>
  <?php endif; ?>

  <div class="footer">
    <div>Generated: <?php echo date('d M Y H:i'); ?></div>
    <div>This is a computer-generated training report and does not require a signature.</div>
  </div>
</div>
</body>
</html>
