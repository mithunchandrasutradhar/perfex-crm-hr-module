<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/attendance'); ?>"><?php echo _l('hr_attendance_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_attendance_import'); ?></li>
        </ol>

        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo _l('hr_attendance_import'); ?></h4>

            <!-- Format instructions -->
            <div class="alert alert-info">
              <p class="tw-font-semibold tw-mb-2"><i class="fa fa-info-circle tw-mr-1"></i>CSV Format Instructions</p>
              <ul class="tw-pl-5 tw-mb-2">
                <li>First row must be a header row (it will be skipped)</li>
                <li>Columns (in order): <code>employee_code</code>, <code>date</code>, <code>in_time</code>, <code>out_time</code>, <code>status</code></li>
                <li>Date format: <code>YYYY-MM-DD</code> (e.g. <code>2026-06-01</code>)</li>
                <li>Time format: <code>HH:MM</code> (24-hour, e.g. <code>09:00</code>). Leave blank if not available.</li>
                <li>Status values: <code>present</code>, <code>late</code>, <code>absent</code>, <code>half_day</code>. Defaults to <code>present</code> if blank.</li>
                <li>Duplicate records (same employee + date) will be skipped automatically.</li>
                <li>Employee codes not found in the system will be skipped.</li>
              </ul>
            </div>

            <!-- Sample CSV -->
            <div class="tw-mb-4">
              <p class="text-muted tw-text-sm tw-font-semibold">Sample CSV:</p>
              <pre style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px;font-size:0.8rem;overflow-x:auto">employee_code,date,in_time,out_time,status
EMP0001,2026-06-01,09:00,18:00,present
EMP0002,2026-06-01,09:45,18:00,late
EMP0003,2026-06-01,,,absent
EMP0004,2026-06-01,09:00,13:00,half_day</pre>
              <a href="<?php echo admin_url('hr_module/attendance/import?download_template=1'); ?>" class="btn btn-default btn-sm tw-mt-2">
                <i class="fa fa-download tw-mr-1"></i>Download Template CSV
              </a>
            </div>

            <!-- Upload form -->
            <?php echo form_open_multipart(admin_url('hr_module/attendance/import'), ['id' => 'importForm']); ?>
              <div class="form-group">
                <label class="tw-font-semibold">Select CSV File <span class="text-danger">*</span></label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control" required>
                <p class="help-block">Max size: 2 MB. Only .csv files accepted.</p>
              </div>
              <div class="tw-flex tw-gap-2">
                <button type="submit" class="btn btn-primary" id="import-btn">
                  <i class="fa fa-upload tw-mr-1"></i>Import Attendance
                </button>
                <a href="<?php echo admin_url('hr_module/attendance'); ?>" class="btn btn-default">
                  <i class="fa fa-arrow-left tw-mr-1"></i>Back
                </a>
              </div>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    $('#importForm').on('submit', function(){
        $('#import-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin tw-mr-1"></i>Importing...');
    });
});
</script>
