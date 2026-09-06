<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-2">
          <li><a href="<?php echo admin_url('hr_module/loans'); ?>">Loan</a></li>
          <li class="active">Deduction Requests</li>
        </ol>
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">Monthly Loan Deduction Requests</h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <select id="f-status" class="selectpicker" data-width="130px">
              <option value="">All Statuses</option>
              <?php foreach (['pending','approved','rejected'] as $s): ?>
              <option value="<?php echo $s; ?>"><?php echo ucfirst($s); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="f-month" class="selectpicker" data-width="130px">
              <option value="">All Months</option>
              <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?php echo $m; ?>"><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
              <?php endfor; ?>
            </select>
            <?php $cur_year = (int) date('Y'); ?>
            <select id="f-year" class="selectpicker" data-width="100px">
              <option value="">All Years</option>
              <?php for ($y = $cur_year - 1; $y <= $cur_year + 1; $y++): ?>
              <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php
              $headings = [
                '#', 'Employee', 'Loan', 'Month', 'Requested', 'Default Installment',
                'Outstanding', _l('hr_status'), 'Reviewed By', 'Notes',
              ];
              if (staff_can('edit', 'hr_loans')) $headings[] = 'Actions';
              render_datatable($headings, 'hr-loan-deductions');
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-loan-deductions', window.location.href, [], [], [], [3,'desc']);
    function reload() {
        var url = window.location.href.split('?')[0]
            + '?status='    + $('#f-status').val()
            + '&pay_month=' + $('#f-month').val()
            + '&pay_year='  + $('#f-year').val();
        $('.table-hr-loan-deductions').DataTable().ajax.url(url).load();
    }
    $('#f-status,#f-month,#f-year').on('change changed.bs.select', reload);
});
</script>
