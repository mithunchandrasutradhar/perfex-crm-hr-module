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
            <option value="">All Years</option>
            <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
            <option value="<?php echo $y; ?>" <?php if(($filters['year']??'')==$y) echo 'selected'; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group select-placeholder tw-mb-0">
          <select id="f-status" class="selectpicker" data-width="100%">
            <option value="">All Status</option>
            <?php foreach(['scheduled','ongoing','completed','cancelled'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php if(($filters['status']??'')==$s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div></div>

  <div class="row tw-mb-3">
    <?php foreach([['Programs','stat-programs',0,'#4f46e5'],['Total Enrolled','stat-enrolled',0,'#059669'],['Completed','stat-completed',0,'#0891b2'],['Completion Rate','stat-rate','0%','#d97706']] as [$label,$id,$val,$color]): ?>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid <?php echo $color; ?>;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div id="<?php echo $id; ?>" style="font-size:1.3rem;font-weight:700;color:<?php echo $color; ?>"><?php echo $val; ?></div>
      <div style="font-size:0.78rem;color:#64748b"><?php echo $label; ?></div>
    </div></div>
    <?php endforeach; ?>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <?php render_datatable([
      'Training','Trainer','Start','End','Capacity','Enrolled','Present','Rate','Status',
    ], 'hr-report-training'); ?>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-report-training', window.location.href, [], [2,'desc']);

    function reload(){
        var url = window.location.href.split('?')[0]
            + '?year='   + $('#f-year').val()
            + '&status=' + $('#f-status').val();
        $('.table-hr-report-training').DataTable().ajax.url(url).load();
    }
    $('#f-year,#f-status').on('change changed.bs.select', reload);

    $('.table-hr-report-training').on('draw.dt', function(e, settings){
        var sums = (settings.json && settings.json.sums) || {};
        $('#stat-programs').text(sums.programs || 0);
        $('#stat-enrolled').text(sums.enrolled || 0);
        $('#stat-completed').text(sums.completed || 0);
        $('#stat-rate').text((sums.rate || 0) + '%');
    });
});
</script>
