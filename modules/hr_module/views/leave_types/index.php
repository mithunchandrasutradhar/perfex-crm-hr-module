<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-gap-3">
            <a href="<?php echo admin_url('hr_module/leave'); ?>" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i></a>
            <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700 tw-mb-0"><?php echo _l('hr_leave_types_list'); ?></h4>
          </div>
          <?php if (staff_can('create', 'hr_leave')): ?>
          <a href="<?php echo admin_url('hr_module/leave_types/add'); ?>" class="btn btn-primary btn-sm">
            <i class="fa fa-plus tw-mr-1"></i><?php echo _l('hr_leave_type_add'); ?>
          </a>
          <?php endif; ?>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_name'), _l('hr_leave_max_days'), _l('hr_leave_hours_per_day'),
              _l('hr_leave_carry_forward'), _l('hr_leave_requires_attachment'),
              _l('hr_leave_half_day'), _l('hr_leave_is_date_range'), _l('hr_status'),
            ], 'hr-ltypes'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-ltypes', window.location.href, [], []);
});
</script>
