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
          <select id="f-department" class="selectpicker" data-width="100%" data-live-search="true">
            <option value="">All Departments</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group select-placeholder tw-mb-0">
          <select id="f-status" class="selectpicker" data-width="100%">
            <option value="">All Status</option>
            <?php foreach(['pending','approved','active','closed','rejected'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php if(($filters['status']??'')==$s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <a id="btn-csv" href="<?php echo admin_url('hr_module/reports/loan?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a>
      </div>
    </div>
  </div></div>

  <div class="row tw-mb-3">
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #4f46e5;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#4f46e5" id="sum-total-loans"><?php echo count($rows); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Loans</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #059669;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#059669" id="sum-total-amount"><?php echo number_format($total_amount,2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Amount</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #dc2626;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#dc2626" id="sum-total-outstanding"><?php echo number_format($total_outstanding,2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Outstanding</div>
    </div></div>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <?php render_datatable([
      'Employee', 'Department', 'Amount', 'Installment/mo', 'Outstanding', 'Repaid', 'Status', 'Approved On',
    ], 'hr-report-loan'); ?>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-report-loan', window.location.href, [], [0, 'asc']);

    function reload() {
        var url = window.location.href.split('?')[0]
            + '?department_id=' + $('#f-department').val()
            + '&status=' + $('#f-status').val();
        $('#btn-csv').attr('href', window.location.pathname + '?department_id=' + $('#f-department').val()
            + '&status=' + $('#f-status').val() + '&export=csv');
        $('.table-hr-report-loan').DataTable().ajax.url(url).load();
    }
    $('#f-department, #f-status').on('change changed.bs.select', reload);

    $('.table-hr-report-loan').on('draw.dt', function(){
        var sums = $(this).DataTable().ajax.json().sums;
        if (!sums) return;
        $('#sum-total-loans').text(sums.total_loans);
        $('#sum-total-amount').text(sums.total_amount);
        $('#sum-total-outstanding').text(sums.total_outstanding);
    });
});
</script>
