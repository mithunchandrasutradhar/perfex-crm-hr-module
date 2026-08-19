<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <div class="tw-flex tw-items-center tw-gap-3">
            <a href="<?php echo admin_url('hr_module/leave'); ?>" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i></a>
            <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700 tw-mb-0"><?php echo _l('hr_leave_balances_list'); ?></h4>
          </div>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <select id="f-dept" class="selectpicker" data-width="160px">
              <option value=""><?php echo _l('hr_all') . ' ' . _l('hr_department'); ?></option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>" <?php if ($dept_id == $d->id) echo 'selected'; ?>>
                <?php echo htmlspecialchars($d->name); ?>
              </option>
              <?php endforeach; ?>
            </select>
            <select id="f-year" class="selectpicker" data-width="90px">
              <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
              <option value="<?php echo $y; ?>" <?php if ($year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
              <?php endfor; ?>
            </select>
            <?php if (is_admin() || staff_can('approve', 'hr_leave')): ?>
            <form method="post" action="<?php echo admin_url('hr_module/leave_balances/allocate'); ?>" onsubmit="return confirm('Allocate leave balances for <?php echo $year; ?>?')">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <input type="hidden" name="year" value="<?php echo $year; ?>">
              <button type="submit" class="btn btn-success btn-sm"><i class="fa fa-magic tw-mr-1"></i>Allocate <?php echo $year; ?></button>
            </form>
            <?php endif; ?>
          </div>
        </div>

        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_employee'), _l('hr_department'), _l('hr_leave_type'),
              _l('hr_leave_allocated'), _l('hr_leave_used'), _l('hr_leave_remaining'),
            ], 'hr-leave-balances'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    function tableUrl() {
        return window.location.href.split('?')[0]
            + '?dept_id=' + $('#f-dept').val()
            + '&year=' + $('#f-year').val();
    }
    initDataTable('.table-hr-leave-balances', tableUrl(), [], [0, 'asc']);
    $('#f-dept, #f-year').on('change changed.bs.select', function(){
        $('.table-hr-leave-balances').DataTable().ajax.url(tableUrl()).load();
    });
});
</script>
