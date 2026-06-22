<?php defined('BASEPATH') or exit('No direct script access allowed');
$allowances = array_filter((array) $details, fn($d) => $d->item_type === 'allowance');
$deductions  = array_filter((array) $details, fn($d) => $d->item_type === 'deduction');
$status_badge = ['draft'=>'default','approved'=>'warning','paid'=>'success'];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/payroll'); ?>"><?php echo _l('hr_payroll_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_payroll_view'); ?></li>
        </ol>
      </div>
    </div>

    <div class="row">
      <!-- Main payroll info -->
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
              <div>
                <h4 class="tw-font-bold"><?php echo date('F Y', mktime(0,0,0,$payroll->pay_month,1,$payroll->pay_year)); ?> Payroll</h4>
                <p class="text-muted tw-mb-0"><?php echo htmlspecialchars($payroll->first_name.' '.$payroll->last_name); ?>
                  &nbsp;<span class="label label-default"><?php echo $payroll->employee_code; ?></span>
                </p>
                <p class="text-muted"><?php echo htmlspecialchars($payroll->department_name ?? ''); ?>
                  <?php if($payroll->designation_name): ?> &middot; <?php echo htmlspecialchars($payroll->designation_name); ?><?php endif; ?>
                </p>
              </div>
              <span class="label label-<?php echo $status_badge[$payroll->status]; ?> tw-text-base tw-px-3 tw-py-1">
                <?php echo ucfirst($payroll->status); ?>
              </span>
            </div>

            <div class="row tw-mb-4">
              <div class="col-md-3 col-sm-6"><div class="panel_s" style="background:#f8fafc"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-text-lg tw-font-bold"><?php echo number_format($payroll->basic_salary,2); ?></div>
                <div class="tw-text-xs text-muted">Basic Salary</div>
              </div></div></div>
              <div class="col-md-3 col-sm-6"><div class="panel_s" style="background:#f0fdf4"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-text-lg tw-font-bold text-success"><?php echo number_format($payroll->gross_salary,2); ?></div>
                <div class="tw-text-xs text-muted">Gross Salary</div>
              </div></div></div>
              <div class="col-md-3 col-sm-6"><div class="panel_s" style="background:#fff7ed"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-text-lg tw-font-bold text-warning"><?php echo number_format($payroll->total_deductions + $payroll->tax + $payroll->loan_deduction,2); ?></div>
                <div class="tw-text-xs text-muted">Total Deductions</div>
              </div></div></div>
              <div class="col-md-3 col-sm-6"><div class="panel_s" style="background:#eff6ff"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-text-lg tw-font-bold text-primary"><?php echo number_format($payroll->net_salary,2); ?></div>
                <div class="tw-text-xs text-muted">Net Salary</div>
              </div></div></div>
            </div>

            <!-- Earnings -->
            <h5 class="tw-font-semibold tw-mb-2">Earnings</h5>
            <table class="table table-condensed tw-mb-4">
              <tr><td>Basic Salary</td><td class="tw-text-right"><?php echo number_format($payroll->basic_salary,2); ?></td></tr>
              <?php foreach ($allowances as $d): ?>
              <tr><td><?php echo htmlspecialchars($d->item_name); ?></td><td class="tw-text-right"><?php echo number_format($d->amount,2); ?></td></tr>
              <?php endforeach; ?>
              <?php if($payroll->overtime_amount > 0): ?>
              <tr><td><?php echo _l('hr_payroll_overtime_pay'); ?></td><td class="tw-text-right"><?php echo number_format($payroll->overtime_amount,2); ?></td></tr>
              <?php endif; ?>
              <?php if($payroll->bonus > 0): ?>
              <tr><td><?php echo _l('hr_payroll_bonus'); ?></td><td class="tw-text-right"><?php echo number_format($payroll->bonus,2); ?></td></tr>
              <?php endif; ?>
              <tr class="tw-font-bold" style="border-top:2px solid #e2e8f0"><td>Total Earnings</td><td class="tw-text-right text-success"><?php echo number_format($payroll->gross_salary,2); ?></td></tr>
            </table>

            <!-- Deductions -->
            <h5 class="tw-font-semibold tw-mb-2">Deductions</h5>
            <table class="table table-condensed">
              <?php foreach ($deductions as $d): ?>
              <tr><td><?php echo htmlspecialchars($d->item_name); ?></td><td class="tw-text-right text-danger"><?php echo number_format($d->amount,2); ?></td></tr>
              <?php endforeach; ?>
              <?php if($payroll->tax > 0): ?>
              <tr><td><?php echo _l('hr_payroll_tax'); ?></td><td class="tw-text-right text-danger"><?php echo number_format($payroll->tax,2); ?></td></tr>
              <?php endif; ?>
              <?php if($payroll->loan_deduction > 0): ?>
              <tr><td><?php echo _l('hr_payroll_loan_deduction'); ?></td><td class="tw-text-right text-danger"><?php echo number_format($payroll->loan_deduction,2); ?></td></tr>
              <?php endif; ?>
              <tr class="tw-font-bold" style="border-top:2px solid #e2e8f0"><td>Total Deductions</td>
                <td class="tw-text-right text-danger"><?php echo number_format($payroll->total_deductions + $payroll->tax + $payroll->loan_deduction,2); ?></td></tr>
              <tr style="background:#eff6ff"><td class="tw-font-bold tw-text-lg">Net Salary</td>
                <td class="tw-text-right tw-font-bold tw-text-lg text-primary"><?php echo number_format($payroll->net_salary,2); ?></td></tr>
            </table>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="col-md-4">
        <!-- Attendance summary -->
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold">Attendance</h5>
            <table class="table table-condensed">
              <tr><td>Working Days</td><td class="tw-text-right"><?php echo $payroll->working_days ?? '-'; ?></td></tr>
              <tr><td>Present Days</td><td class="tw-text-right text-success"><?php echo $payroll->present_days ?? '-'; ?></td></tr>
              <tr><td>Absent Days</td><td class="tw-text-right text-danger"><?php echo $payroll->absent_days ?? '-'; ?></td></tr>
            </table>
          </div>
        </div>

        <!-- Actions -->
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold tw-mb-3">Actions</h5>
            <a href="<?php echo admin_url('hr_module/payroll/slip/'.$payroll->id); ?>" target="_blank" class="btn btn-default btn-block tw-mb-2">
              <i class="fa fa-print tw-mr-1"></i>Print Pay Slip
            </a>
            <?php if ($payroll->status === 'draft' && staff_can('edit','hr_payroll')): ?>
            <a href="<?php echo admin_url('hr_module/payroll/approve/'.$payroll->id); ?>"
               class="btn btn-warning btn-block tw-mb-2"
               onclick="return confirm('Approve this payroll?')">
              <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_payroll_approve'); ?>
            </a>
            <?php endif; ?>
            <?php if ($payroll->status === 'approved' && staff_can('edit','hr_payroll')): ?>
            <button class="btn btn-success btn-block tw-mb-2" data-toggle="modal" data-target="#markPaidModal">
              <i class="fa fa-money-bill-wave tw-mr-1"></i><?php echo _l('hr_payroll_mark_paid'); ?>
            </button>
            <?php endif; ?>
            <?php if ($payroll->status !== 'paid' && staff_can('delete','hr_payroll')): ?>
            <a href="<?php echo admin_url('hr_module/payroll/delete/'.$payroll->id); ?>"
               class="btn btn-danger btn-block _delete">
              <i class="fa fa-trash tw-mr-1"></i>Delete
            </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Meta -->
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold">Details</h5>
            <table class="table table-condensed">
              <tr><td>Generated</td><td><?php echo date('d M Y', strtotime($payroll->created_at)); ?></td></tr>
              <?php if ($payroll->approved_at): ?>
              <tr><td>Approved</td><td><?php echo date('d M Y', strtotime($payroll->approved_at)); ?>
                <?php if ($payroll->approved_by_name): ?><br><small><?php echo htmlspecialchars($payroll->approved_by_name); ?></small><?php endif; ?>
              </td></tr>
              <?php endif; ?>
              <?php if ($payroll->payment_date): ?>
              <tr><td>Paid On</td><td><?php echo date('d M Y', strtotime($payroll->payment_date)); ?></td></tr>
              <tr><td>Method</td><td><?php echo ucfirst(str_replace('_',' ',$payroll->payment_method)); ?></td></tr>
              <?php endif; ?>
              <?php if ($payroll->notes): ?>
              <tr><td colspan="2"><em class="text-muted"><?php echo nl2br(htmlspecialchars($payroll->notes)); ?></em></td></tr>
              <?php endif; ?>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Mark Paid Modal -->
<div class="modal fade" id="markPaidModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title"><?php echo _l('hr_payroll_mark_paid'); ?></h4>
    </div>
    <?php echo form_open(admin_url('hr_module/payroll/mark_paid/'.$payroll->id)); ?>
    <div class="modal-body">
      <div class="form-group">
        <label><?php echo _l('hr_payroll_payment_method'); ?></label>
        <select name="payment_method" class="form-control" required>
          <option value="bank_transfer"><?php echo _l('hr_payroll_bank_transfer'); ?></option>
          <option value="cash"><?php echo _l('hr_payroll_cash'); ?></option>
          <option value="cheque"><?php echo _l('hr_payroll_cheque'); ?></option>
        </select>
      </div>
      <div class="form-group">
        <label>Payment Date</label>
        <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-success"><i class="fa fa-check tw-mr-1"></i>Confirm Payment</button>
    </div>
    <?php echo form_close(); ?>
  </div></div>
</div>
<?php init_tail(); ?>
