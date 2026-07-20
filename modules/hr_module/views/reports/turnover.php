<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>
  <div class="panel_s tw-mb-3"><div class="panel-body">
    <?php echo form_open(admin_url('hr_module/reports/turnover'), ['method'=>'get']); ?>
    <div class="row">
      <div class="col-md-3"><select name="year" class="form-control input-sm">
        <?php for($y=date('Y');$y>=date('Y')-4;$y--): ?>
        <option value="<?php echo $y; ?>" <?php if(($filters['year']??date('Y'))==$y) echo 'selected'; ?>><?php echo $y; ?></option>
        <?php endfor; ?>
      </select></div>
      <div class="col-md-3"><select name="department_id" class="form-control input-sm"><option value="">All Departments</option><?php foreach($departments as $d): ?><option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button></div>
      <div class="col-md-2"><a href="<?php echo admin_url('hr_module/reports/turnover?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a></div>
    </div>
    <?php echo form_close(); ?>
  </div></div>

  <?php
  $total_joined  = array_sum(array_column((array)$rows,'joined'));
  $total_left    = array_sum(array_column((array)$rows,'left_count'));
  $avg_rate      = count($rows) ? array_sum(array_column((array)$rows,'turnover_rate'))/count($rows) : 0;
  $months_labels = array_map(fn($r)=>date('M', mktime(0,0,0,$r->month,1)), (array)$rows);
  $joined_data   = array_column((array)$rows,'joined');
  $left_data     = array_column((array)$rows,'left_count');
  ?>
  <div class="row tw-mb-3">
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #059669;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#059669"><?php echo $total_joined; ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Hired</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #dc2626;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#dc2626"><?php echo $total_left; ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Attrition</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #d97706;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#d97706"><?php echo number_format($avg_rate,1); ?>%</div>
      <div style="font-size:0.78rem;color:#64748b">Avg Monthly Turnover Rate</div>
    </div></div>
  </div>

  <?php if(!empty($rows)): ?>
  <div class="panel_s tw-mb-3"><div class="panel-body">
    <canvas id="turnoverChart" height="80"></canvas>
  </div></div>
  <?php endif; ?>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <table class="table table-hover">
      <thead><tr>
        <th>Month</th>
        <th class="text-right text-success">Hired</th>
        <th class="text-right text-danger">Left</th>
        <th class="text-right">Net</th>
        <th class="text-right">Headcount (End)</th>
        <th class="text-right">Turnover Rate</th>
      </tr></thead>
      <tbody>
      <?php if(empty($rows)): ?><tr><td colspan="6" class="text-center text-muted" style="padding:30px">No records.</td></tr>
      <?php else: foreach($rows as $r):
        $net = $r->joined - $r->left_count;
      ?>
      <tr>
        <td><?php echo date('F Y', mktime(0,0,0,$r->month,1,$r->year)); ?></td>
        <td class="text-right text-success"><strong><?php echo $r->joined; ?></strong></td>
        <td class="text-right text-danger"><strong><?php echo $r->left_count; ?></strong></td>
        <td class="text-right <?php echo $net >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ($net >= 0 ? '+' : '').$net; ?></td>
        <td class="text-right"><?php echo $r->headcount_end ?? '-'; ?></td>
        <td class="text-right">
          <?php $rate = $r->turnover_rate; ?>
          <span class="<?php echo $rate > 5 ? 'text-danger' : ($rate > 2 ? 'text-warning' : 'text-success'); ?>"><?php echo number_format($rate,1); ?>%</span>
        </td>
      </tr>
      <?php endforeach; ?>
      <tr class="active">
        <td><strong>Total / Avg</strong></td>
        <td class="text-right text-success"><strong><?php echo $total_joined; ?></strong></td>
        <td class="text-right text-danger"><strong><?php echo $total_left; ?></strong></td>
        <td class="text-right"><strong><?php echo ($total_joined-$total_left >= 0 ? '+' : '').($total_joined-$total_left); ?></strong></td>
        <td class="text-right">—</td>
        <td class="text-right"><strong><?php echo number_format($avg_rate,1); ?>%</strong></td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>

</div></div>
</div></div>
<?php if(!empty($rows)): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  // Deferred to DOMContentLoaded: the vendor bundle (Chart.js, jQuery) is appended
  // to the page after this view's own inline scripts, so building the chart (or
  // even referencing $) immediately here would fail or silently no-op.
  var labels  = <?php echo json_encode($months_labels); ?>;
  var joined  = <?php echo json_encode($joined_data); ?>;
  var left    = <?php echo json_encode($left_data); ?>;
  var ctx     = document.getElementById('turnoverChart');
  if(!ctx||typeof Chart==='undefined') return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {label:'Hired',  data:joined, backgroundColor:'rgba(5,150,105,.7)'},
        {label:'Left',   data:left,   backgroundColor:'rgba(220,38,38,.7)'}
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      legend: { position: 'top' },
      scales: {
        yAxes: [{ ticks: { beginAtZero: true, stepSize: 1 } }]
      }
    }
  });
});
</script>
<?php endif; ?>
<?php init_tail(); ?>
