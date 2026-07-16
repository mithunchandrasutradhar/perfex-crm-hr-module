<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_leave_list'); ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <select id="f-status" class="form-control input-sm" style="width:130px">
              <option value=""><?php echo _l('hr_all'); ?> Status</option>
              <option value="pending"><?php echo _l('hr_leave_status_pending'); ?></option>
              <option value="approved"><?php echo _l('hr_leave_status_approved'); ?></option>
              <option value="rejected"><?php echo _l('hr_leave_status_rejected'); ?></option>
              <option value="cancelled"><?php echo _l('hr_leave_status_cancelled'); ?></option>
            </select>
            <select id="f-type" class="form-control input-sm" style="width:150px">
              <option value=""><?php echo _l('hr_all') . ' ' . _l('hr_leave_type'); ?></option>
              <?php foreach ($leave_types as $t): ?>
              <option value="<?php echo $t->id; ?>"><?php echo htmlspecialchars($t->name); ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (staff_can('create', 'hr_leave')): ?>
            <a href="<?php echo admin_url('hr_module/leave/apply'); ?>" class="btn btn-primary">
              <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_leave_add'); ?>
            </a>
            <?php endif; ?>
            <a href="<?php echo admin_url('hr_module/leave_types'); ?>" class="btn btn-default btn-sm">
              <i class="fa fa-list tw-mr-1"></i><?php echo _l('hr_leave_types_list'); ?>
            </a>
            <a href="<?php echo admin_url('hr_module/leave_balances'); ?>" class="btn btn-default btn-sm">
              <i class="fa fa-balance-scale tw-mr-1"></i><?php echo _l('hr_leave_balances_list'); ?>
            </a>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              '#', _l('hr_employee'), _l('hr_leave_type'),
              _l('hr_from_date'), _l('hr_to_date'), _l('hr_leave_days'),
              _l('hr_status'), _l('hr_created_at'), _l('hr_actions'),
            ], 'hr-leave'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-leave', window.location.href, [], [7, 'desc']);
    function reload() {
        var url = window.location.href.split('?')[0]
            + '?status=' + $('#f-status').val()
            + '&leave_type_id=' + $('#f-type').val();
        $('.table-hr-leave').DataTable().ajax.url(url).load();
    }
    $('#f-status, #f-type').on('change', reload);
});
</script>
