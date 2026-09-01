<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>

  <div class="row tw-mb-3">
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #4f46e5;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div id="stat-total" style="font-size:1.6rem;font-weight:700;color:#4f46e5">0</div>
      <div style="font-size:0.78rem;color:#64748b">Total Employees</div>
    </div></div>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #059669;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div id="stat-active" style="font-size:1.6rem;font-weight:700;color:#059669">0</div>
      <div style="font-size:0.78rem;color:#64748b">Active</div>
    </div></div>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #0891b2;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div id="stat-male" style="font-size:1.6rem;font-weight:700;color:#0891b2">0</div>
      <div style="font-size:0.78rem;color:#64748b">Male</div>
    </div></div>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #db2777;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div id="stat-female" style="font-size:1.6rem;font-weight:700;color:#db2777">0</div>
      <div style="font-size:0.78rem;color:#64748b">Female</div>
    </div></div>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <?php render_datatable([
      'Department', 'Total', 'Active', 'Inactive', 'Male', 'Female',
    ], 'hr-report-headcount'); ?>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-report-headcount', window.location.href, [], [], undefined, [0,'asc']);

    $('.table-hr-report-headcount').append(
        '<tfoot><tr class="active">'
        + '<td><strong>Total</strong></td>'
        + '<td class="text-right"><strong id="tf-total">0</strong></td>'
        + '<td class="text-right"><strong id="tf-active">0</strong></td>'
        + '<td class="text-right" id="tf-inactive">0</td>'
        + '<td class="text-right" id="tf-male">0</td>'
        + '<td class="text-right" id="tf-female">0</td>'
        + '</tr></tfoot>'
    );

    $('.table-hr-report-headcount').on('draw.dt', function(e, settings){
        var sums = (settings.json && settings.json.sums) || {};
        $('#stat-total').text(sums.total || 0);
        $('#stat-active').text(sums.active || 0);
        $('#stat-male').text(sums.male || 0);
        $('#stat-female').text(sums.female || 0);
        $('#tf-total').text(sums.total || 0);
        $('#tf-active').text(sums.active || 0);
        $('#tf-inactive').text(sums.inactive || 0);
        $('#tf-male').text(sums.male || 0);
        $('#tf-female').text(sums.female || 0);
    });
});
</script>
