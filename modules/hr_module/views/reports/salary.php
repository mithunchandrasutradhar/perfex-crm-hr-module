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
            <option value="active" <?php if(($filters['status']??'')=='active') echo 'selected'; ?>>Active</option>
            <option value="inactive" <?php if(($filters['status']??'')=='inactive') echo 'selected'; ?>>Inactive</option>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <a id="btn-csv" href="<?php echo admin_url('hr_module/reports/salary?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a>
      </div>
    </div>
  </div></div>

  <?php
  $total_basic = array_sum(array_column((array)$rows,'basic_salary'));
  $total_gross = array_sum(array_column((array)$rows,'gross_salary'));
  $avg_gross   = count($rows) ? $total_gross/count($rows) : 0;
  ?>
  <div class="row tw-mb-3">
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #4f46e5;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#4f46e5" id="sum-basic"><?php echo number_format($total_basic,2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Basic Salary</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #059669;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#059669" id="sum-gross"><?php echo number_format($total_gross,2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Gross Salary</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #d97706;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#d97706" id="sum-avg"><?php echo number_format($avg_gross,2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Average Gross Salary</div>
    </div></div>
  </div>

  <div class="panel_s tw-mb-3"><div class="panel-body panel-table-full">
    <h4 class="tw-mb-2">Employee Salary Details</h4>
    <?php render_datatable([
      'Employee', 'Department', 'Designation', 'Basic', 'Allowances', 'Deductions', 'Gross',
    ], 'hr-report-salary'); ?>
  </div></div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <h4 class="tw-mb-2">Department Summary</h4>
    <?php render_datatable([
      'Department', 'Employees', 'Avg Salary', 'Min Salary', 'Max Salary',
    ], 'hr-report-salary-summary'); ?>
  </div></div>

</div></div>
</div></div>
<?php init_tail(); ?>
<script>
$(function(){
    function currentFilters() {
        return 'department_id=' + $('#f-department').val() + '&status=' + $('#f-status').val();
    }

    // Two independent DataTable instances share the same set of filters but hit
    // two different AJAX views off the one salary() action - the summary table's
    // url carries &table=summary so the controller knows which of the two table
    // partials (salary_table / salary_summary_table) to render for that request;
    // the detail table's url is left without that marker.
    initDataTable('.table-hr-report-salary', window.location.href, [], [3, 'desc']);
    initDataTable('.table-hr-report-salary-summary', window.location.href.split('?')[0] + '?' + currentFilters() + '&table=summary', [], [0, 'asc']);

    $('.table-hr-report-salary').append(
        '<tfoot><tr class="active">' +
        '<td colspan="3"><strong>Total</strong></td>' +
        '<td class="text-right"><strong id="tfoot-basic"></strong></td>' +
        '<td class="text-right"><strong id="tfoot-allowances"></strong></td>' +
        '<td class="text-right"><strong id="tfoot-deductions"></strong></td>' +
        '<td class="text-right"><strong id="tfoot-gross"></strong></td>' +
        '</tr></tfoot>'
    );

    function reload() {
        var url = window.location.href.split('?')[0] + '?' + currentFilters();
        $('#btn-csv').attr('href', window.location.pathname + '?' + currentFilters() + '&export=csv');
        $('.table-hr-report-salary').DataTable().ajax.url(url).load();
        $('.table-hr-report-salary-summary').DataTable().ajax.url(url + '&table=summary').load();
    }
    $('#f-department, #f-status').on('change changed.bs.select', reload);

    $('.table-hr-report-salary').on('draw.dt', function(){
        var sums = $(this).DataTable().ajax.json().sums;
        if (!sums) return;
        $('#sum-basic').text(sums.total_basic);
        $('#sum-gross').text(sums.total_gross);
        $('#sum-avg').text(sums.avg_gross);
        $('#tfoot-basic').text(sums.total_basic);
        $('#tfoot-allowances').text(sums.total_allowances);
        $('#tfoot-deductions').text(sums.total_deductions);
        $('#tfoot-gross').text(sums.total_gross);
    });
});
</script>
