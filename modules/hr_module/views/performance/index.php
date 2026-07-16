<?php defined('BASEPATH') or exit('No direct script access allowed');
$can_assign = staff_can('create', 'hr_performance') || staff_can('edit', 'hr_performance');
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_performance_list'); ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2 tw-items-center">
            <select id="f-dept" class="selectpicker" data-width="150px" data-live-search="true">
              <option value=""><?php echo _l('hr_all'); ?> Dept</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="f-status" class="selectpicker" data-width="160px">
              <option value="">All Status</option>
              <option value="pending"><?php echo _l('hr_performance_status_pending'); ?></option>
              <option value="in_progress"><?php echo _l('hr_performance_status_in_progress'); ?></option>
              <option value="partially_completed"><?php echo _l('hr_performance_status_partial'); ?></option>
              <option value="completed"><?php echo _l('hr_performance_status_completed'); ?></option>
            </select>
            <?php if ($can_assign): ?>
            <select id="report-employee" class="selectpicker" data-width="200px" data-live-search="true"
                    data-none-selected-text="<?php echo _l('hr_employee'); ?>">
              <option value=""><?php echo _l('hr_select'); ?></option>
              <?php foreach ($employees as $id => $name): ?>
              <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" id="generate-report-btn" class="btn btn-default btn-sm">
              <i class="fa fa-file-text-o tw-mr-1"></i><?php echo _l('hr_performance_generate_report'); ?>
            </button>
            <a href="<?php echo admin_url('hr_module/performance/add'); ?>" class="btn btn-primary">
              <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_performance_assign'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_employee'), _l('hr_department'),
              _l('hr_performance_target_title'), _l('hr_performance_assigned_by'),
              _l('hr_performance_progress'), _l('hr_performance_due_date'),
            ], 'hr-performance'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-performance', window.location.href, [], [5,'desc']);
    function reload(){
        var url = window.location.href.split('?')[0]
            + '?department_id=' + $('#f-dept').val()
            + '&status='        + $('#f-status').val();
        $('.table-hr-performance').DataTable().ajax.url(url).load();
    }
    $('#f-dept,#f-status').on('change', reload);

    $('#generate-report-btn').on('click', function(){
        var empId = $('#report-employee').val();
        if (!empId) {
            alert_float('danger', '<?php echo _l('hr_performance_select_employee_first'); ?>');
            return;
        }
        window.open('<?php echo admin_url('hr_module/performance/employee_report/'); ?>' + empId, '_blank');
    });
});
</script>
