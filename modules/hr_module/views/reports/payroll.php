<?php defined('BASEPATH') or exit('No direct script access allowed');
$months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
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
        <div class="form-group select-placeholder tw-mb-0">
          <select id="f-month" class="selectpicker" data-width="100%">
            <option value="">All Months</option>
            <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?php echo $m; ?>" <?php if(($filters['month']??'')==$m) echo 'selected'; ?>><?php echo $months[$m]; ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-group select-placeholder tw-mb-0">
          <select id="f-year" class="selectpicker" data-width="100%">
            <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
            <option value="<?php echo $y; ?>" <?php if(($filters['year']??'')==$y) echo 'selected'; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
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
        <div class="form-group select-placeholder tw-mb-0">
          <select id="f-status" class="selectpicker" data-width="100%">
            <option value="">All Status</option>
            <option value="draft" <?php if(($filters['status']??'')=='draft') echo 'selected'; ?>>Draft</option>
            <option value="paid" <?php if(($filters['status']??'')=='paid') echo 'selected'; ?>>Paid</option>
          </select>
        </div>
      </div>
      <div class="col-md-2"><a id="btn-csv" href="<?php echo admin_url('hr_module/reports/payroll?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download tw-mr-1"></i>CSV</a></div>
    </div>
  </div></div>

  <div class="row tw-mb-3">
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #059669;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#059669" id="sum-gross"><?php echo number_format($totals['gross'],2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Gross Earnings</div>
    </div></div>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #dc2626;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#dc2626" id="sum-deductions"><?php echo number_format($totals['deductions'],2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Deductions</div>
    </div></div>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #4f46e5;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#4f46e5" id="sum-net"><?php echo number_format($totals['net'],2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Net Payroll</div>
    </div></div>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #0891b2;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#0891b2" id="sum-records"><?php echo count($rows); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Records</div>
    </div></div>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <?php render_datatable([
      'Employee', 'Department', 'Period', 'Basic', 'Gross', 'Deductions', 'Net', 'Status',
    ], 'hr-report-payroll'); ?>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-report-payroll', window.location.href, [], [], undefined, [2, 'desc']);

    $('.table-hr-report-payroll').append(
        '<tfoot><tr class="active">' +
        '<td colspan="4"><strong>Totals</strong></td>' +
        '<td class="text-right"><strong id="tfoot-gross"></strong></td>' +
        '<td class="text-right text-danger"><strong id="tfoot-deductions"></strong></td>' +
        '<td class="text-right"><strong id="tfoot-net"></strong></td>' +
        '<td></td></tr></tfoot>'
    );

    function currentFilters() {
        return 'month=' + $('#f-month').val()
            + '&year=' + $('#f-year').val()
            + '&department_id=' + $('#f-department').val()
            + '&status=' + $('#f-status').val();
    }
    function reload() {
        $('.table-hr-report-payroll').DataTable().ajax.url(window.location.href.split('?')[0] + '?' + currentFilters()).load();
        $('#btn-csv').attr('href', window.location.pathname + '?' + currentFilters() + '&export=csv');
    }
    $('#f-month, #f-year, #f-department, #f-status').on('change changed.bs.select', reload);

    $('.table-hr-report-payroll').on('draw.dt', function(){
        var sums = $(this).DataTable().ajax.json().sums;
        if (!sums) return;
        $('#sum-gross').text(sums.gross);
        $('#sum-deductions').text(sums.deductions);
        $('#sum-net').text(sums.net);
        $('#sum-records').text(sums.records);
        $('#tfoot-gross').text(sums.gross);
        $('#tfoot-deductions').text(sums.deductions);
        $('#tfoot-net').text(sums.net);
    });
});
</script>
