<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_payroll_list'); ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <!-- Filters -->
            <?php if (!empty($show_dept_filter)): ?>
            <select id="f-dept" class="selectpicker" data-width="150px" data-live-search="true">
              <option value=""><?php echo _l('hr_all'); ?> Dept</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <?php if (!empty($show_dept_filter)): ?>
            <select id="f-emp" class="selectpicker" data-width="200px" data-live-search="true"
                    data-none-selected-text="<?php echo _l('hr_employee'); ?>">
              <option value=""><?php echo _l('hr_all') . ' Employees'; ?></option>
              <?php foreach ($employees as $id => $name): ?>
              <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
              <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <select id="f-month" class="selectpicker" data-width="110px">
              <option value="">Month</option>
              <?php for($m=1;$m<=12;$m++): ?>
              <option value="<?php echo $m; ?>"><?php echo date('F',mktime(0,0,0,$m,1)); ?></option>
              <?php endfor; ?>
            </select>
            <select id="f-year" class="selectpicker" data-width="90px">
              <option value="">Year</option>
              <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
              <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
              <?php endfor; ?>
            </select>
            <select id="f-status" class="selectpicker" data-width="110px">
              <option value="">All Status</option>
              <option value="draft">Draft</option>
              <option value="paid">Paid</option>
            </select>
            <?php if (staff_can('create', 'hr_payroll')): ?>
            <a href="<?php echo admin_url('hr_module/payroll/generate'); ?>" class="btn btn-primary">
              <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_payroll_generate'); ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($can_manage_items)): ?>
            <a href="<?php echo admin_url('hr_module/payroll_items'); ?>" class="btn btn-default btn-sm">
              <i class="fa fa-list tw-mr-1"></i><?php echo _l('hr_payroll_items_list'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php
              // One column per currently-active payroll item (Payroll Items list),
              // in the same order table.php builds each row's cells in.
              $CI = &get_instance();
              $CI->load->model('hr_module/Payroll_model');
              $payroll_item_headers = array_map(function ($item) {
                  return htmlspecialchars($item->name);
              }, $CI->Payroll_model->get_items(true));
            ?>
            <?php render_datatable(array_merge([
              _l('hr_employee'), _l('hr_department'), _l('hr_payroll_period'), _l('hr_payroll_basic_salary'),
            ], $payroll_item_headers, [
              _l('hr_payroll_overtime'), _l('hr_shift_type'), _l('hr_payroll_gross_salary'),
              _l('hr_payroll_loan_deduction'),
              _l('hr_payroll_net_salary'), _l('hr_status'), 'Payment Date',
            ]), 'hr-payroll'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Mark Paid Modal -->
<?php if (staff_can('edit', 'hr_payroll')): ?>
<div class="modal fade" id="markPaidModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title"><?php echo _l('hr_payroll_mark_paid'); ?></h4>
    </div>
    <form id="markPaidForm">
      <div class="modal-body">
        <input type="hidden" id="mp_id">
        <div class="form-group">
          <label><?php echo _l('hr_payroll_payment_method'); ?></label>
          <select name="payment_method" id="mp_method" class="form-control" required>
            <option value="bank_transfer"><?php echo _l('hr_payroll_bank_transfer'); ?></option>
            <option value="cash"><?php echo _l('hr_payroll_cash'); ?></option>
            <option value="cheque"><?php echo _l('hr_payroll_cheque'); ?></option>
          </select>
        </div>
        <div class="form-group">
          <label>Payment Date</label>
          <div class="input-group date">
            <input type="text" name="payment_date" id="mp_date" class="form-control datepicker" autocomplete="off" value="<?php echo _d(date('Y-m-d')); ?>" required>
            <span class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success" id="mp-submit-btn"><i class="fa fa-check tw-mr-1"></i>Confirm Payment</button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<?php init_tail(); ?>
<script>
$(function(){
    // Sort column index 2 = Pay Period (shifted from 3 now that Basic Salary/
    // item columns were inserted before it - keeps the same "most recent period
    // first" default sort).
    initDataTable('.table-hr-payroll', window.location.href, [], [2,'desc']);
    function reload() {
        var deptVal = $('#f-dept').length ? $('#f-dept').val() : '';
        var url = window.location.href.split('?')[0]
            + '?department_id=' + deptVal
            + '&employee_id='   + $('#f-emp').val()
            + '&pay_month='     + $('#f-month').val()
            + '&pay_year='      + $('#f-year').val()
            + '&status='        + $('#f-status').val();
        $('.table-hr-payroll').DataTable().ajax.url(url).load();
    }
    $('#f-dept,#f-emp,#f-month,#f-year,#f-status').on('change changed.bs.select', reload);

    // Pre-select the Employee filter when landing here with ?employee_id=
    // in the URL (e.g. the dashboard's "My Payslips" quick action) - the
    // table itself is already filtered server-side by the initial
    // window.location.href load above, this just reflects it in the UI.
    (function(){
        var params = new URLSearchParams(window.location.search);
        var empId = params.get('employee_id');
        if (empId) {
            $('#f-emp').val(empId).selectpicker('refresh');
        }
    })();

    $(document).on('click', '.hr-mark-paid', function(e){
        e.preventDefault();
        $('#markPaidForm')[0].reset();
        $('#mp_date').val('<?php echo _d(date('Y-m-d')); ?>');
        $('#mp_id').val($(this).data('id'));
        $('#markPaidModal').modal('show');
    });

    $('#markPaidForm').on('submit', function(e){
        e.preventDefault();
        var id  = $('#mp_id').val();
        var url = '<?php echo admin_url('hr_module/payroll/mark_paid/'); ?>' + id;
        var $btn = $('#mp-submit-btn').prop('disabled', true);
        $.post(url, $(this).serialize()+'&<?php echo $this->security->get_csrf_token_name(); ?>=<?php echo $this->security->get_csrf_hash(); ?>', function(r){
            if(r.success){ alert_float('success', r.message); $('#markPaidModal').modal('hide'); $('.table-hr-payroll').DataTable().ajax.reload(); }
            else alert_float('danger', r.message);
        }, 'json').always(function(){ $btn.prop('disabled', false); });
    });

    // Approve/reject an employee's own loan-level deduction/skip request, right from the
    // payroll list - reuses the Loans module's existing approve/reject endpoints.
    $(document).on('click', '.hr-approve-loan-request, .hr-reject-loan-request', function(e){
        e.preventDefault();
        var $link  = $(this);
        var id     = $link.data('id');
        var action = $link.hasClass('hr-approve-loan-request') ? 'approve_deduction' : 'reject_deduction';
        if (!confirm(action === 'approve_deduction' ? 'Approve this employee\'s deduction request?' : 'Reject this employee\'s deduction request?')) return;

        $.post('<?php echo admin_url('hr_module/loans/'); ?>' + action + '/' + id,
            { back: window.location.href, <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>' },
            function(r){
                if (r.success) { alert_float('success', r.message); $('.table-hr-payroll').DataTable().ajax.reload(); }
                else alert_float('danger', r.message);
            }, 'json');
    });
});
</script>
