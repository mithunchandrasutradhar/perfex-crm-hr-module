<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_employee_list'); ?></h4>
          <div class="tw-flex tw-gap-2">
            <select id="filter-dept" class="form-control input-sm" style="width:160px">
              <option value=""><?php echo _l('hr_all') . ' ' . _l('hr_department'); ?></option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="filter-status" class="form-control input-sm" style="width:120px">
              <option value=""><?php echo _l('hr_all'); ?></option>
              <option value="1"><?php echo _l('hr_active'); ?></option>
              <option value="0"><?php echo _l('hr_inactive'); ?></option>
            </select>
            <?php if (staff_can('create', 'hr_employees')): ?>
            <a href="<?php echo admin_url('hr_module/employees/add'); ?>" class="btn btn-primary">
              <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_employee_add'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_employee_code'),
              _l('hr_employee_full_name'),
              _l('hr_department'),
              _l('hr_designation'),
              _l('hr_email'),
              _l('hr_employee_joining_date'),
              _l('hr_status'),
            ], 'hr-employees'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    var table = initDataTable('.table-hr-employees', window.location.href, [], []);

    function reloadWithFilters() {
        var url = window.location.href.split('?')[0]
            + '?department_id=' + $('#filter-dept').val()
            + '&status=' + $('#filter-status').val();
        $('.table-hr-employees').DataTable().ajax.url(url).load();
    }

    $('#filter-dept, #filter-status').on('change', function(){ reloadWithFilters(); });
});
</script>
