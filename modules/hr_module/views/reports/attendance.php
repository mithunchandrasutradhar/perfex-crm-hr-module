<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>

  <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
    <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo $title; ?></h4>
    <?php echo form_open(admin_url('hr_module/reports/attendance'), ['method' => 'get', 'class' => 'tw-flex tw-flex-wrap tw-gap-2 tw-items-center']); ?>
      <div class="input-group date" style="width:150px">
        <input type="text" name="from_date" id="f-from-date" class="form-control datepicker" autocomplete="off"
               value="<?php echo _d($filters['from_date']); ?>" placeholder="From date">
        <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
      </div>
      <div class="input-group date" style="width:150px">
        <input type="text" name="to_date" id="f-to-date" class="form-control datepicker" autocomplete="off"
               value="<?php echo _d($filters['to_date']); ?>" placeholder="To date">
        <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
      </div>
      <select name="department_id" id="f-department" class="selectpicker" data-width="200px">
        <option value=""><?php echo _l('hr_all') . ' Dept'; ?></option>
        <?php foreach ($departments as $d): ?>
        <option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
        <?php endforeach; ?>
      </select>
      <select name="employee_id" id="f-employee" class="selectpicker" data-width="220px" data-live-search="true"
              data-none-selected-text="<?php echo _l('hr_all') . ' Employees'; ?>">
        <option value="">All Employees</option>
        <?php foreach ($employees as $id => $name): ?>
        <option value="<?php echo $id; ?>" <?php if(($filters['employee_id']??'')==$id) echo 'selected'; ?>><?php echo htmlspecialchars($name); ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-filter tw-mr-1"></i><?php echo _l('hr_filter'); ?>
      </button>
      <a id="btn-csv" href="<?php echo admin_url('hr_module/reports/attendance?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default">
        <i class="fa-solid fa-download tw-mr-1"></i>CSV
      </a>
    <?php echo form_close(); ?>
  </div>

  <div class="row tw-mb-3">
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #059669;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#059669" id="sum-present">0</div>
      <div style="font-size:0.78rem;color:#64748b">Present</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #d97706;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#d97706" id="sum-late">0</div>
      <div style="font-size:0.78rem;color:#64748b">Late</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #dc2626;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#dc2626" id="sum-absent">0</div>
      <div style="font-size:0.78rem;color:#64748b">Absent</div>
    </div></div>
  </div>

  <!-- Table -->
  <div class="panel_s"><div class="panel-body panel-table-full">
    <?php render_datatable([
      'Employee', 'Employee Code', 'Department', 'Present', 'Late', 'Absent',
    ], 'hr-report-attendance'); ?>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-report-attendance', window.location.href, [], [], undefined, [0, 'asc']);

    function currentFilters() {
        return 'from_date=' + encodeURIComponent($('#f-from-date').val())
            + '&to_date=' + encodeURIComponent($('#f-to-date').val())
            + '&department_id=' + $('#f-department').val()
            + '&employee_id=' + $('#f-employee').val();
    }
    function reload() {
        var url = window.location.href.split('?')[0] + '?' + currentFilters();
        $('#btn-csv').attr('href', window.location.pathname + '?' + currentFilters() + '&export=csv');
        $('.table-hr-report-attendance').DataTable().ajax.url(url).load();
    }
    $('#f-department, #f-employee').on('change changed.bs.select', reload);

    $('.table-hr-report-attendance').on('draw.dt', function(){
        var sums = $(this).DataTable().ajax.json().sums;
        if (!sums) return;
        $('#sum-present').text(sums.present);
        $('#sum-late').text(sums.late);
        $('#sum-absent').text(sums.absent);
    });
});
</script>
