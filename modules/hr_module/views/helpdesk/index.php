<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_helpdesk_list'); ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <select id="f-dept" class="selectpicker" data-width="150px" data-live-search="true">
              <option value=""><?php echo _l('hr_all'); ?> Dept</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="f-priority" class="selectpicker" data-width="130px">
              <option value="">All Priority</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
            <select id="f-status" class="selectpicker" data-width="150px">
              <option value="">All Status</option>
              <option value="open">Open</option>
              <option value="in_progress">In Progress</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>
            <?php if (staff_can('create', 'hr_helpdesk')): ?>
            <a href="<?php echo admin_url('hr_module/helpdesk/submit'); ?>" class="btn btn-primary">
              <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_helpdesk_add'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_helpdesk_subject'), _l('hr_employee'),
              _l('hr_helpdesk_category'), _l('hr_helpdesk_priority'),
              _l('hr_helpdesk_replies'), _l('hr_helpdesk_assigned_to'),
              _l('hr_status'), 'Submitted',
            ], 'hr-helpdesk'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-helpdesk', window.location.href, [], [7,'desc']);
    function reload(){
        var url = window.location.href.split('?')[0]
            + '?department_id=' + $('#f-dept').val()
            + '&priority='      + $('#f-priority').val()
            + '&status='        + $('#f-status').val();
        $('.table-hr-helpdesk').DataTable().ajax.url(url).load();
    }
    $('#f-dept,#f-priority,#f-status').on('change', reload);
});
</script>
