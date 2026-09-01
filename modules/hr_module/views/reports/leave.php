<?php defined('BASEPATH') or exit('No direct script access allowed');
$sbadge = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','cancelled'=>'default'];
?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>

  <div class="panel_s tw-mb-3"><div class="panel-body">
    <div class="row">
      <div class="col-md-2">
        <div class="input-group date">
          <input type="text" id="f-from-date" name="from_date" class="form-control input-sm datepicker" autocomplete="off" value="<?php echo !empty($filters['from_date']) ? _d($filters['from_date']) : ''; ?>" placeholder="From Date">
          <span class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></span>
        </div>
      </div>
      <div class="col-md-2">
        <div class="input-group date">
          <input type="text" id="f-to-date" name="to_date" class="form-control input-sm datepicker" autocomplete="off" value="<?php echo !empty($filters['to_date']) ? _d($filters['to_date']) : ''; ?>" placeholder="To Date">
          <span class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></span>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-group select-placeholder tw-mb-0">
          <select id="f-department" class="selectpicker" data-width="100%" data-live-search="true">
            <option value="">All Departments</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-group select-placeholder tw-mb-0">
          <select id="f-leave-type" class="selectpicker" data-width="100%">
            <option value="">All Types</option>
            <?php foreach ($leave_types as $lt): ?>
            <option value="<?php echo $lt->id; ?>" <?php if(($filters['leave_type_id']??'')==$lt->id) echo 'selected'; ?>><?php echo htmlspecialchars($lt->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-group select-placeholder tw-mb-0">
          <select id="f-status" class="selectpicker" data-width="100%">
            <option value="">All Status</option>
            <?php foreach(['approved','pending','rejected','cancelled'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php if(($filters['status']??'')==$s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <a id="btn-csv" href="<?php echo admin_url('hr_module/reports/leave?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a>
      </div>
    </div>
  </div></div>

  <?php
  $counts = ['approved'=>0,'pending'=>0,'rejected'=>0,'total_days'=>0];
  foreach($rows as $r) {
    if(isset($counts[$r->status])) $counts[$r->status]++;
    if($r->status === 'approved') $counts['total_days'] += $r->days_requested;
  }
  ?>
  <div class="row tw-mb-3">
    <div class="col-md-2"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #059669;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#059669" id="sum-approved"><?php echo $counts['approved']; ?></div>
      <div style="font-size:0.78rem;color:#64748b">Approved</div>
    </div></div>
    <div class="col-md-2"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #d97706;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#d97706" id="sum-pending"><?php echo $counts['pending']; ?></div>
      <div style="font-size:0.78rem;color:#64748b">Pending</div>
    </div></div>
    <div class="col-md-2"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #dc2626;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#dc2626" id="sum-rejected"><?php echo $counts['rejected']; ?></div>
      <div style="font-size:0.78rem;color:#64748b">Rejected</div>
    </div></div>
    <div class="col-md-2"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #4f46e5;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#4f46e5" id="sum-total-days"><?php echo $counts['total_days']; ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Days (Approved)</div>
    </div></div>
    <div class="col-md-2"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #0891b2;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#0891b2" id="sum-total-records"><?php echo count($rows); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Records</div>
    </div></div>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <?php render_datatable([
      'Employee', 'Department', 'Leave Type', 'From', 'To', 'Days', 'Status',
    ], 'hr-report-leave'); ?>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-report-leave', window.location.href, [], [], undefined, [3, 'desc']);

    function currentFilters() {
        return 'department_id=' + $('#f-department').val()
            + '&leave_type_id=' + $('#f-leave-type').val()
            + '&status=' + $('#f-status').val()
            + '&from_date=' + $('#f-from-date').val()
            + '&to_date=' + $('#f-to-date').val();
    }
    function reload() {
        $('.table-hr-report-leave').DataTable().ajax.url(window.location.href.split('?')[0] + '?' + currentFilters()).load();
        $('#btn-csv').attr('href', window.location.pathname + '?' + currentFilters() + '&export=csv');
    }
    $('#f-department, #f-leave-type, #f-status').on('change changed.bs.select', reload);
    $('#f-from-date, #f-to-date').on('change', reload);

    $('.table-hr-report-leave').on('draw.dt', function(){
        var sums = $(this).DataTable().ajax.json().sums;
        if (!sums) return;
        $('#sum-approved').text(sums.approved);
        $('#sum-pending').text(sums.pending);
        $('#sum-rejected').text(sums.rejected);
        $('#sum-total-days').text(sums.total_days);
        $('#sum-total-records').text(sums.total_records);
    });
});
</script>
