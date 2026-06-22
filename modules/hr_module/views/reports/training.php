<?php defined('BASEPATH') or exit('No direct script access allowed');
$sbadge = ['scheduled'=>'info','ongoing'=>'primary','completed'=>'success','cancelled'=>'danger'];
?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>
  <div class="panel_s tw-mb-3"><div class="panel-body">
    <?php echo form_open(admin_url('hr_module/reports/training'), ['method'=>'get']); ?>
    <div class="row">
      <div class="col-md-3"><select name="year" class="form-control input-sm"><option value="">All Years</option><?php for($y=date('Y');$y>=date('Y')-3;$y--): ?><option value="<?php echo $y; ?>" <?php if(($filters['year']??'')==$y) echo 'selected'; ?>><?php echo $y; ?></option><?php endfor; ?></select></div>
      <div class="col-md-3"><select name="status" class="form-control input-sm"><option value="">All Status</option><?php foreach(['scheduled','ongoing','completed','cancelled'] as $s): ?><option value="<?php echo $s; ?>" <?php if(($filters['status']??'')==$s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button></div>
    </div>
    <?php echo form_close(); ?>
  </div></div>

  <?php
  $total_enrolled  = array_sum(array_column((array)$rows,'enrolled'));
  $total_completed = array_sum(array_column((array)$rows,'completed'));
  $completion_rate = $total_enrolled > 0 ? round($total_completed/$total_enrolled*100) : 0;
  ?>
  <div class="row tw-mb-3">
    <?php foreach([['Programs',count($rows),'#4f46e5'],['Total Enrolled',$total_enrolled,'#059669'],['Completed',$total_completed,'#0891b2'],['Completion Rate',$completion_rate.'%','#d97706']] as [$label,$val,$color]): ?>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid <?php echo $color; ?>;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:<?php echo $color; ?>"><?php echo $val; ?></div>
      <div style="font-size:0.78rem;color:#64748b"><?php echo $label; ?></div>
    </div></div>
    <?php endforeach; ?>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <table class="table table-hover">
      <thead><tr><th>Training</th><th>Trainer</th><th>Start</th><th>End</th><th class="text-right">Capacity</th><th class="text-right">Enrolled</th><th class="text-right">Completed</th><th>Rate</th><th>Status</th></tr></thead>
      <tbody>
      <?php if(empty($rows)): ?><tr><td colspan="9" class="text-center text-muted" style="padding:30px">No records.</td></tr>
      <?php else: foreach($rows as $r): ?>
      <?php $rate = $r->enrolled > 0 ? round($r->completed/$r->enrolled*100) : 0; ?>
      <tr>
        <td><a href="<?php echo admin_url('hr_module/training/view/'.$r->id); ?>"><?php echo htmlspecialchars($r->title); ?></a></td>
        <td><?php echo htmlspecialchars($r->trainer ?? '-'); ?></td>
        <td><?php echo date('d M Y', strtotime($r->start_date)); ?></td>
        <td><?php echo $r->end_date ? date('d M Y', strtotime($r->end_date)) : '-'; ?></td>
        <td class="text-right"><?php echo $r->capacity ?: '∞'; ?></td>
        <td class="text-right"><?php echo $r->enrolled; ?></td>
        <td class="text-right text-success"><?php echo $r->completed; ?></td>
        <td>
          <div style="background:#e2e8f0;border-radius:4px;height:8px;width:80px">
            <div style="background:#059669;border-radius:4px;height:8px;width:<?php echo $rate; ?>%"></div>
          </div>
          <small><?php echo $rate; ?>%</small>
        </td>
        <td><span class="label label-<?php echo $sbadge[$r->status] ?? 'default'; ?>"><?php echo ucfirst($r->status); ?></span></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
