<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_loan_list'); ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <select id="f-dept" class="form-control input-sm" style="width:150px">
              <option value=""><?php echo _l('hr_all'); ?> Dept</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="f-status" class="form-control input-sm" style="width:120px">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="active">Active</option>
              <option value="rejected">Rejected</option>
              <option value="closed">Closed</option>
            </select>
            <?php if (staff_can('create', 'hr_loans')): ?>
            <a href="<?php echo admin_url('hr_module/loans/apply'); ?>" class="btn btn-primary btn-sm">
              <i class="fa fa-plus tw-mr-1"></i><?php echo _l('hr_loan_add'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_employee'), _l('hr_department'),
              _l('hr_loan_amount'), _l('hr_loan_monthly_installment'),
              _l('hr_loan_outstanding'), 'Repaid', _l('hr_status'),
              _l('hr_loan_disbursement_date'), _l('hr_actions'),
            ], 'hr-loans'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-loans', window.location.href, [], [7,'desc']);
    function reload() {
        var url = window.location.href.split('?')[0]
            + '?department_id=' + $('#f-dept').val()
            + '&status='        + $('#f-status').val();
        $('.table-hr-loans').DataTable().ajax.url(url).load();
    }
    $('#f-dept,#f-status').on('change', reload);
});
</script>
