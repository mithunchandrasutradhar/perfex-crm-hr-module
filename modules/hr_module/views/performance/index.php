<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_performance_list'); ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <select id="f-dept" class="form-control input-sm" style="width:150px">
              <option value=""><?php echo _l('hr_all'); ?> Dept</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="f-year" class="form-control input-sm" style="width:90px">
              <option value="">Year</option>
              <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
              <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
              <?php endfor; ?>
            </select>
            <select id="f-status" class="form-control input-sm" style="width:130px">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
            <?php if (staff_can('create', 'hr_performance')): ?>
            <a href="<?php echo admin_url('hr_module/performance/add'); ?>" class="btn btn-primary btn-sm">
              <i class="fa fa-plus tw-mr-1"></i><?php echo _l('hr_performance_add'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_employee'), _l('hr_department'),
              _l('hr_performance_period'), _l('hr_performance_reviewer'),
              _l('hr_performance_score'), _l('hr_performance_rating'),
              _l('hr_status'),
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
    initDataTable('.table-hr-performance', window.location.href, [], [6,'desc']);
    function reload(){
        var url = window.location.href.split('?')[0]
            + '?department_id=' + $('#f-dept').val()
            + '&year='          + $('#f-year').val()
            + '&status='        + $('#f-status').val();
        $('.table-hr-performance').DataTable().ajax.url(url).load();
    }
    $('#f-dept,#f-year,#f-status').on('change', reload);
});
</script>
