<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">

        <?php if (!empty($expiring_soon)): ?>
        <div class="alert alert-warning">
          <i class="fa-regular fa-clock tw-mr-1"></i>
          <strong><?php echo count($expiring_soon); ?> contract(s)</strong> expiring within 30 days:
          <?php foreach ($expiring_soon as $ec): ?>
            <a href="<?php echo admin_url('hr_module/hr_contracts/view/'.$ec->id); ?>">
              <?php echo htmlspecialchars($ec->first_name.' '.$ec->last_name); ?> (<?php echo date('d M Y', strtotime($ec->end_date)); ?>)
            </a><?php echo !($ec === end($expiring_soon)) ? ', ' : ''; ?>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo $title; ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <select id="f-emp" class="selectpicker" data-width="160px" data-live-search="true">
              <option value="">All Employees</option>
              <?php foreach ($employees as $id => $name): ?>
              <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="f-dept" class="selectpicker" data-width="150px" data-live-search="true">
              <option value="">All Departments</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="f-type" class="selectpicker" data-width="140px">
              <option value="">All Types</option>
              <option value="permanent">Permanent</option>
              <option value="fixed">Fixed Term</option>
              <option value="probation">Probation</option>
              <option value="internship">Internship</option>
              <option value="casual">Casual</option>
            </select>
            <select id="f-status" class="selectpicker" data-width="130px">
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="pending">Pending</option>
              <option value="expired">Expired</option>
              <option value="terminated">Terminated</option>
            </select>
            <button id="f-expiring" class="btn btn-warning btn-sm" type="button">
              <i class="fa-regular fa-clock tw-mr-1"></i>Expiring Soon
            </button>
            <?php if (staff_can('create', 'hr_contracts')): ?>
            <a href="<?php echo admin_url('hr_module/hr_contracts/add'); ?>" class="btn btn-primary">
              <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_contract_add'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>

        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_contract_title'),
              _l('hr_employee'),
              _l('hr_department'),
              _l('hr_contract_type'),
              _l('hr_start_date'),
              _l('hr_end_date'),
              _l('hr_contract_value'),
              _l('hr_status'),
              _l('hr_contract_signed'),
            ], 'hr-contracts'); ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    var expiringSoonFilter = false;
    initDataTable('.table-hr-contracts', window.location.href, [], [4,'desc']);

    function reload(){
        var url = window.location.href.split('?')[0]
            + '?employee_id='   + $('#f-emp').val()
            + '&department_id=' + $('#f-dept').val()
            + '&contract_type=' + $('#f-type').val()
            + '&status='        + $('#f-status').val()
            + (expiringSoonFilter ? '&expiring_soon=1' : '');
        $('.table-hr-contracts').DataTable().ajax.url(url).load();
    }

    $('#f-emp,#f-dept,#f-type,#f-status').on('change changed.bs.select', reload);

    $('#f-expiring').on('click', function(){
        expiringSoonFilter = !expiringSoonFilter;
        $(this).toggleClass('btn-warning btn-default');
        reload();
    });
});
</script>
