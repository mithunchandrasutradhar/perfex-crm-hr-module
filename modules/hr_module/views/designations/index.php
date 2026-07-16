<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-items-center tw-justify-between">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_designation_list'); ?></h4>
          <?php if (staff_can('create', 'hr_departments')): ?>
          <a href="<?php echo admin_url('hr_module/designations/add'); ?>" class="btn btn-primary">
            <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_designation_add'); ?>
          </a>
          <?php endif; ?>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_name'),
              _l('hr_employee'),
              _l('hr_status'),
            ], 'hr-designations'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
$(function () {
    initDataTable('.table-hr-designations', window.location.href, [], []);
});
</script>
