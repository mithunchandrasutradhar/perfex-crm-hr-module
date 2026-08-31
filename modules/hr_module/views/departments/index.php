<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-items-center tw-justify-between">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_department_list'); ?></h4>
          <a href="<?php echo admin_url('settings?group=ticket_departments'); ?>" class="btn btn-default" target="_blank">
            <i class="fa fa-external-link-alt tw-mr-1"></i> Manage in CRM Settings
          </a>
        </div>
        <div class="alert alert-info tw-mb-3">
          <i class="fa fa-info-circle tw-mr-1"></i>
          Departments are shared with the support ticket system.
          To add, edit, or delete departments, go to
          <a href="<?php echo admin_url('settings?group=ticket_departments'); ?>" target="_blank"><strong>Setup &rarr; Ticket Departments</strong></a>.
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_name'),
              _l('hr_employee'),
            ], 'hr-departments'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function () {
    initDataTable('.table-hr-departments', window.location.href, [], []);
});
</script>
