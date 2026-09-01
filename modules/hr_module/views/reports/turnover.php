<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>
  <div class="panel_s tw-mb-3"><div class="panel-body">
    <div class="row">
      <div class="col-md-3">
        <div class="form-group select-placeholder tw-mb-0">
          <select id="f-year" class="selectpicker" data-width="100%">
            <?php for($y=date('Y');$y>=date('Y')-4;$y--): ?>
            <option value="<?php echo $y; ?>" <?php if(($filters['year']??date('Y'))==$y) echo 'selected'; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group select-placeholder tw-mb-0">
          <select id="f-department" class="selectpicker" data-width="100%" data-live-search="true">
            <option value="">All Departments</option>
            <?php foreach($departments as $d): ?>
            <option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <a id="btn-csv" href="<?php echo admin_url('hr_module/reports/turnover?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a>
      </div>
    </div>
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
      <div style="font-size:1.6rem;font-weight:700;color:#059669" id="sum-joined"><?php echo $total_joined; ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Hired</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #dc2626;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#dc2626" id="sum-left"><?php echo $total_left; ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Attrition</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #d97706;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#d97706" id="sum-rate"><?php echo number_format($avg_rate,1); ?>%</div>
      <div style="font-size:0.78rem;color:#64748b">Avg Monthly Turnover Rate</div>
    </div></div>
  </div>

  <div class="panel_s tw-mb-3"><div class="panel-body">
    <canvas id="turnoverChart" height="80"></canvas>
  </div></div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <?php render_datatable([
      'Month', 'Hired', 'Left', 'Net', 'Headcount (End)', 'Turnover Rate',
    ], 'hr-report-turnover'); ?>
  </div></div>

</div></div>
</div></div>
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

  // Stored on window so the draw.dt handler below (registered inside the
  // jQuery-ready block further down) can update the same instance in place
  // instead of destroying/recreating the chart on every filter change.
  window.turnoverChart = new Chart(ctx, {
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
<script>
$(function(){
    initDataTable('.table-hr-report-turnover', window.location.href, [], [], undefined, [0, 'asc']);

    $('.table-hr-report-turnover').append(
        '<tfoot><tr class="active">' +
        '<td><strong>Total / Avg</strong></td>' +
        '<td class="text-right text-success"><strong id="tfoot-joined"></strong></td>' +
        '<td class="text-right text-danger"><strong id="tfoot-left"></strong></td>' +
        '<td class="text-right"><strong id="tfoot-net"></strong></td>' +
        '<td class="text-right">&mdash;</td>' +
        '<td class="text-right"><strong id="tfoot-rate"></strong></td>' +
        '</tr></tfoot>'
    );

    function reload() {
        var url = window.location.href.split('?')[0]
            + '?year=' + $('#f-year').val()
            + '&department_id=' + $('#f-department').val();
        $('#btn-csv').attr('href', window.location.pathname + '?year=' + $('#f-year').val()
            + '&department_id=' + $('#f-department').val() + '&export=csv');
        $('.table-hr-report-turnover').DataTable().ajax.url(url).load();
    }
    $('#f-year, #f-department').on('change changed.bs.select', reload);

    $('.table-hr-report-turnover').on('draw.dt', function(){
        var json = $(this).DataTable().ajax.json();
        var sums = json.sums;
        if (sums) {
            $('#sum-joined').text(sums.total_joined);
            $('#sum-left').text(sums.total_left);
            $('#sum-rate').text(sums.avg_rate + '%');
            $('#tfoot-joined').text(sums.total_joined);
            $('#tfoot-left').text(sums.total_left);
            $('#tfoot-net').text(sums.net);
            $('#tfoot-rate').text(sums.avg_rate + '%');
        }

        var chart = json.chart;
        if (chart && window.turnoverChart) {
            window.turnoverChart.data.labels = chart.labels;
            window.turnoverChart.data.datasets[0].data = chart.joined;
            window.turnoverChart.data.datasets[1].data = chart.left;
            window.turnoverChart.update();
        }
    });
});
</script>
<?php init_tail(); ?>
