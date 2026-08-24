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
          <select id="f-department" class="selectpicker" data-width="100%" data-live-search="true" required>
            <option value="">Select Department</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-group select-placeholder tw-mb-0">
          <select id="f-year" class="selectpicker" data-width="100%">
            <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
            <option value="<?php echo $y; ?>" <?php if(($filters['year']??date('Y'))==$y) echo 'selected'; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <a id="btn-csv" href="<?php echo admin_url('hr_module/reports/department?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a>
      </div>
    </div>
  </div></div>

  <?php if (empty($filters['department_id'])): ?>
  <div class="alert alert-info" id="dept-empty-note">Select a department above to view the report.</div>
  <?php endif; ?>

  <div class="row tw-mb-3">
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #4f46e5;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#4f46e5" id="sum-employees"><?php echo count($rows); ?></div>
      <div style="font-size:0.78rem;color:#64748b" id="sum-employees-label"><?php echo !empty($rows) ? htmlspecialchars($rows[0]->department_name ?? '') . ' — Employees' : 'Employees'; ?></div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #059669;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#059669" id="sum-leave"><?php echo array_sum(array_column((array)$rows,'total_leave_days')); ?> days</div>
      <div style="font-size:0.78rem;color:#64748b">Total Leave Days (<span id="sum-year"><?php echo $filters['year']??date('Y'); ?></span>)</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #d97706;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#d97706" id="sum-salary"><?php echo number_format(array_sum(array_column((array)$rows,'total_salary')),2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Payroll (<span id="sum-year2"><?php echo $filters['year']??date('Y'); ?></span>)</div>
    </div></div>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <?php render_datatable([
      'Employee', 'Designation', 'Employment', 'Join Date', 'Leave Days', 'Total Salary',
    ], 'hr-report-department'); ?>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-report-department', window.location.href, [], [0, 'asc']);

    $('.table-hr-report-department').append(
        '<tfoot><tr class="active">' +
        '<td colspan="4"><strong>Total</strong></td>' +
        '<td class="text-right"><strong id="tfoot-leave"></strong></td>' +
        '<td class="text-right"><strong id="tfoot-salary"></strong></td>' +
        '</tr></tfoot>'
    );

    function reload() {
        var url = window.location.href.split('?')[0]
            + '?department_id=' + $('#f-department').val()
            + '&year=' + $('#f-year').val();
        $('#btn-csv').attr('href', window.location.pathname + '?department_id=' + $('#f-department').val()
            + '&year=' + $('#f-year').val() + '&export=csv');
        $('.table-hr-report-department').DataTable().ajax.url(url).load();
        $('#dept-empty-note').toggle(!$('#f-department').val());
    }
    $('#f-department, #f-year').on('change changed.bs.select', reload);

    $('.table-hr-report-department').on('draw.dt', function(){
        var json = $(this).DataTable().ajax.json();
        var sums = json.sums;
        if (!sums) return;
        $('#sum-employees').text(sums.total_employees);
        $('#sum-employees-label').text(sums.department_name ? sums.department_name + ' — Employees' : 'Employees');
        $('#sum-leave').text(sums.total_leave_days + ' days');
        $('#sum-salary').text(sums.total_salary);
        $('#sum-year, #sum-year2').text($('#f-year').val());
        $('#tfoot-leave').text(sums.total_leave_days);
        $('#tfoot-salary').text(sums.total_salary);
    });
});
</script>
