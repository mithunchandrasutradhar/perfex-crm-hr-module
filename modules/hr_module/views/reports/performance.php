<?php defined('BASEPATH') or exit('No direct script access allowed');
$rbadge = ['excellent'=>'success','very_good'=>'info','good'=>'primary','average'=>'warning','poor'=>'danger'];
$sbadge = ['pending'=>'warning','in_progress'=>'info','completed'=>'success'];
?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>
  <div class="panel_s tw-mb-3"><div class="panel-body">
    <?php echo form_open(admin_url('hr_module/reports/performance'), ['method'=>'get']); ?>
    <div class="row">
      <div class="col-md-2"><select name="year" class="form-control input-sm"><?php for($y=date('Y');$y>=date('Y')-3;$y--): ?><option value="<?php echo $y; ?>" <?php if(($filters['year']??'')==$y) echo 'selected'; ?>><?php echo $y; ?></option><?php endfor; ?></select></div>
      <div class="col-md-2"><select name="department_id" class="form-control input-sm"><option value="">All Departments</option><?php foreach($departments as $d): ?><option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><select name="rating" class="form-control input-sm"><option value="">All Ratings</option><?php foreach(['excellent','very_good','good','average','poor'] as $r): ?><option value="<?php echo $r; ?>" <?php if(($filters['rating']??'')==$r) echo 'selected'; ?>><?php echo ucfirst(str_replace('_',' ',$r)); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><select name="status" class="form-control input-sm"><option value="">All Status</option><?php foreach(['pending','in_progress','completed'] as $s): ?><option value="<?php echo $s; ?>" <?php if(($filters['status']??'')==$s) echo 'selected'; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button></div>
      <div class="col-md-2"><a href="<?php echo admin_url('hr_module/reports/performance?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a></div>
    </div>
    <?php echo form_close(); ?>
  </div></div>

  <?php
  $completed = array_filter((array)$rows, fn($r)=>$r->status==='completed');
  $avg_score = count($completed) ? array_sum(array_column($completed,'final_score'))/count($completed) : 0;
  ?>
  <div class="row tw-mb-3">
    <?php foreach([['Total Reviews',count($rows),'#4f46e5'],['Completed',count($completed),'#059669'],['Avg Score',number_format($avg_score,1),'#d97706']] as [$label,$val,$color]): ?>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid <?php echo $color; ?>;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:<?php echo $color; ?>"><?php echo $val; ?></div>
      <div style="font-size:0.78rem;color:#64748b"><?php echo $label; ?></div>
    </div></div>
    <?php endforeach; ?>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <table class="table table-hover">
      <thead><tr><th>Employee</th><th>Department</th><th>Reviewer</th><th>Period</th><th class="text-right">Score</th><th>Rating</th><th>Status</th></tr></thead>
      <tbody>
      <?php if(empty($rows)): ?><tr><td colspan="7" class="text-center text-muted" style="padding:30px">No records.</td></tr>
      <?php else: foreach($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?><br><small class="text-muted"><?php echo $r->employee_code; ?></small></td>
        <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
        <td><?php echo htmlspecialchars($r->reviewer_name ?? '-'); ?></td>
        <td><?php echo date('M Y', strtotime($r->review_period_start ?? 'now')); ?></td>
        <td class="text-right"><?php echo $r->final_score !== null ? number_format($r->final_score,1) : '-'; ?></td>
        <td><?php if($r->rating): ?><span class="label label-<?php echo $rbadge[$r->rating] ?? 'default'; ?>"><?php echo ucfirst(str_replace('_',' ',$r->rating)); ?></span><?php else: ?>-<?php endif; ?></td>
        <td><span class="label label-<?php echo $sbadge[$r->status] ?? 'default'; ?>"><?php echo ucfirst(str_replace('_',' ',$r->status)); ?></span></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
