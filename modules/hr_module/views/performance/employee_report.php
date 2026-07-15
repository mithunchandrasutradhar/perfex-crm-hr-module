<?php defined('BASEPATH') or exit('No direct script access allowed');
$status_labels = [
    'pending'              => _l('hr_performance_status_pending'),
    'in_progress'          => _l('hr_performance_status_in_progress'),
    'partially_completed'  => _l('hr_performance_status_partial'),
    'completed'            => _l('hr_performance_status_completed'),
];
$status_colors = ['pending'=>'#64748b','in_progress'=>'#d97706','partially_completed'=>'#2563eb','completed'=>'#16a34a'];
$rating_score  = ['Excellent'=>5,'Very Good'=>4,'Good'=>3,'Average'=>2,'Poor'=>1];

$all_sub_targets = [];
foreach ($targets as $t) {
    foreach ($t->sub_targets as $st) $all_sub_targets[] = $st;
}

$total     = count($all_sub_targets);
$pct_sum   = 0.0; $pct_count = 0;
$rating_sum = 0;  $rating_count = 0;
$status_counts = ['pending'=>0,'in_progress'=>0,'partially_completed'=>0,'completed'=>0];

foreach ($all_sub_targets as $st) {
    if (isset($status_counts[$st->status])) $status_counts[$st->status]++;
    if ($st->completion_percentage !== null) {
        $pct_sum += (float) $st->completion_percentage;
        $pct_count++;
    }
    foreach ($st->feedback as $f) {
        if ($f->rating && isset($rating_score[$f->rating])) {
            $rating_sum += $rating_score[$f->rating];
            $rating_count++;
        }
    }
}
$avg_completion = $pct_count ? round($pct_sum / $pct_count, 1) : null;
$avg_rating     = $rating_count ? round($rating_sum / $rating_count, 2) : null;

$company_name = get_option('companyname') ?: 'Company Name';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo _l('hr_performance_employee_report'); ?> — <?php echo htmlspecialchars($employee->first_name.' '.$employee->last_name); ?></title>
<style>
  body{font-family:Arial,sans-serif;font-size:13px;color:#222;margin:0;padding:20px}
  .report{max-width:900px;margin:0 auto;border:1px solid #ccc;padding:24px}
  .header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #333;padding-bottom:12px;margin-bottom:16px}
  .company h2{margin:0;font-size:18px} .company p{margin:2px 0;font-size:11px;color:#555}
  .report-title{text-align:right} .report-title h3{margin:0;font-size:15px} .report-title p{margin:2px 0;font-size:11px}
  .emp-info{background:#f8f8f8;border:1px solid #e2e2e2;padding:10px 14px;margin-bottom:14px;border-radius:4px}
  .emp-info table{width:100%;border-collapse:collapse} .emp-info td{padding:2px 8px;font-size:12px}
  .emp-info td:nth-child(even){font-weight:600}
  .stats{display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap}
  .stat-box{flex:1;min-width:90px;background:#f8fafc;border-radius:6px;padding:10px;text-align:center}
  .stat-box .val{font-size:1.3rem;font-weight:700} .stat-box .lbl{font-size:0.75rem;color:#64748b}
  .target-block{margin:16px 0;border:1px solid #e2e2e2;border-radius:6px;overflow:hidden}
  .target-title{background:#333;color:#fff;padding:6px 12px;font-size:13px;font-weight:bold}
  table.sub-table{width:100%;border-collapse:collapse}
  table.sub-table td,table.sub-table th{padding:6px 10px;border:1px solid #ddd;font-size:12px;vertical-align:top}
  table.sub-table th{background:#f0f0f0;font-weight:600}
  .badge{display:inline-block;padding:2px 8px;border-radius:3px;color:#fff;font-size:11px}
  .feedback-item{margin:4px 0;padding:6px 8px;background:#f8fafc;border-radius:4px;font-size:11.5px}
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
      <h3><?php echo strtoupper(_l('hr_performance_employee_report')); ?></h3>
      <p>Generated: <?php echo date('d M Y'); ?></p>
    </div>
  </div>

  <div class="emp-info">
    <table>
      <tr>
        <td>Employee Name:</td><td><?php echo htmlspecialchars($employee->first_name.' '.$employee->last_name); ?></td>
        <td>Employee Code:</td><td><?php echo $employee->employee_code; ?></td>
      </tr>
      <tr>
        <td>Department:</td><td><?php echo htmlspecialchars($employee->department_name ?? '-'); ?></td>
        <td>Designation:</td><td><?php echo htmlspecialchars($employee->designation_name ?? '-'); ?></td>
      </tr>
      <tr>
        <td>Joining Date:</td><td><?php echo $employee->joining_date ? date('d M Y',strtotime($employee->joining_date)) : '-'; ?></td>
        <td>Email:</td><td><?php echo htmlspecialchars($employee->email ?? '-'); ?></td>
      </tr>
    </table>
  </div>

  <div class="stats">
    <div class="stat-box"><div class="val"><?php echo count($targets); ?></div><div class="lbl"><?php echo _l('hr_performance_list'); ?></div></div>
    <div class="stat-box"><div class="val"><?php echo $total; ?></div><div class="lbl"><?php echo _l('hr_performance_total_tasks'); ?></div></div>
    <div class="stat-box"><div class="val"><?php echo $status_counts['completed']; ?></div><div class="lbl"><?php echo _l('hr_performance_status_completed'); ?></div></div>
    <div class="stat-box"><div class="val"><?php echo $status_counts['in_progress']; ?></div><div class="lbl"><?php echo _l('hr_performance_status_in_progress'); ?></div></div>
    <div class="stat-box"><div class="val"><?php echo $status_counts['partially_completed']; ?></div><div class="lbl"><?php echo _l('hr_performance_status_partial'); ?></div></div>
    <div class="stat-box"><div class="val"><?php echo $status_counts['pending']; ?></div><div class="lbl"><?php echo _l('hr_performance_status_pending'); ?></div></div>
    <div class="stat-box"><div class="val"><?php echo $avg_completion !== null ? $avg_completion.'%' : '-'; ?></div><div class="lbl"><?php echo _l('hr_performance_avg_completion'); ?></div></div>
    <div class="stat-box"><div class="val"><?php echo $avg_rating !== null ? $avg_rating.' / 5' : '-'; ?></div><div class="lbl"><?php echo _l('hr_performance_avg_rating'); ?></div></div>
  </div>

  <?php if (empty($targets)): ?>
  <p style="text-align:center;color:#888;padding:20px">No targets assigned yet.</p>
  <?php else: foreach ($targets as $t): ?>
  <div class="target-block">
    <div class="target-title"><?php echo htmlspecialchars($t->title); ?><?php if ($t->due_date): ?> — Due <?php echo date('d M Y', strtotime($t->due_date)); ?><?php endif; ?></div>
    <table class="sub-table">
      <thead><tr>
        <th><?php echo _l('hr_performance_sub_target_title'); ?></th>
        <th><?php echo _l('hr_performance_evaluators'); ?></th>
        <th><?php echo _l('hr_performance_due_date'); ?></th>
        <th><?php echo _l('hr_status'); ?></th>
        <th class="text-right">%</th>
      </tr></thead>
      <tbody>
      <?php if (empty($t->sub_targets)): ?>
      <tr><td colspan="5" style="text-align:center;color:#888">No sub-targets.</td></tr>
      <?php else: foreach ($t->sub_targets as $st): ?>
      <tr>
        <td><?php echo htmlspecialchars($st->title); ?></td>
        <td><?php echo $st->evaluators ? htmlspecialchars(implode(', ', array_map(fn($e)=>$e->name, $st->evaluators))) : '-'; ?></td>
        <td><?php echo $st->due_date ? date('d M Y', strtotime($st->due_date)) : '-'; ?></td>
        <td><span class="badge" style="background:<?php echo $status_colors[$st->status] ?? '#64748b'; ?>"><?php echo $status_labels[$st->status] ?? ucfirst($st->status); ?></span></td>
        <td class="text-right"><?php echo $st->completion_percentage !== null ? rtrim(rtrim(number_format($st->completion_percentage,2),'0'),'.').'%' : '-'; ?></td>
      </tr>
      <?php if ($st->feedback): ?>
      <tr><td colspan="5" style="background:#fbfbfb">
        <?php foreach ($st->feedback as $f): ?>
        <div class="feedback-item">
          <strong><?php echo htmlspecialchars($f->evaluator_name ?? '-'); ?></strong>
          <?php if ($f->rating): ?> — <?php echo htmlspecialchars($f->rating); ?><?php endif; ?>:
          <?php echo nl2br(htmlspecialchars($f->feedback)); ?>
          <span style="color:#999"> (<?php echo date('d M Y', strtotime($f->created_at)); ?>)</span>
        </div>
        <?php endforeach; ?>
      </td></tr>
      <?php endif; ?>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php endforeach; endif; ?>

  <div class="footer">
    <div>Generated: <?php echo date('d M Y H:i'); ?></div>
    <div>This is a computer-generated performance report and does not require a signature.</div>
  </div>
</div>
</body>
</html>
