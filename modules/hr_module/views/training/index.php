<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_training_list'); ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <input type="date" id="f-from" class="form-control input-sm" style="width:135px" placeholder="From">
            <input type="date" id="f-to"   class="form-control input-sm" style="width:135px" placeholder="To">
            <select id="f-status" class="selectpicker" data-width="140px">
              <option value="">All Status</option>
              <option value="scheduled">Scheduled</option>
              <option value="ongoing">Ongoing</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <?php if (staff_can('create', 'hr_training')): ?>
            <a href="<?php echo admin_url('hr_module/training/add'); ?>" class="btn btn-primary btn-sm">
              <i class="fa fa-plus tw-mr-1"></i><?php echo _l('hr_training_add'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_training_title'), _l('hr_training_trainer'), _l('hr_training_venue'),
              _l('hr_training_start_date'), _l('hr_training_end_date'),
              _l('hr_training_cost'), _l('hr_training_enrolled'),
              _l('hr_status'),
            ], 'hr-training'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-training', window.location.href, [], [3,'desc']);
    function reload(){
        var url = window.location.href.split('?')[0]
            + '?status='    + $('#f-status').val()
            + '&from_date=' + $('#f-from').val()
            + '&to_date='   + $('#f-to').val();
        $('.table-hr-training').DataTable().ajax.url(url).load();
    }
    $('#f-status,#f-from,#f-to').on('change', reload);
});
</script>
