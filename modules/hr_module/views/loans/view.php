<?php defined('BASEPATH') or exit('No direct script access allowed');
$badge = ['pending'=>'default','approved'=>'warning','active'=>'info','rejected'=>'danger','closed'=>'success'];
$pct   = $loan->amount > 0 ? min(100, round(($loan->total_repaid / $loan->amount) * 100)) : 0;

// Find current month's deduction request (if any)
$cur_month = (int) date('n');
$cur_year  = (int) date('Y');
$cur_req   = null;
foreach ($deduction_requests as $dr) {
    if ((int)$dr->pay_month === $cur_month && (int)$dr->pay_year === $cur_year) {
        $cur_req = $dr;
        break;
    }
}
$req_badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
if (!isset($can_manage_deductions)) $can_manage_deductions = staff_can('edit', 'hr_loans');
if (!isset($can_adjust))  $can_adjust  = false;
if (!isset($adjustments)) $adjustments = [];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/loans'); ?>"><?php echo _l('hr_loan_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_loan_view'); ?></li>
        </ol>
      </div>
    </div>

    <div class="row">
      <!-- Main details -->
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
              <div>
                <h4 class="tw-font-bold tw-mb-0"><?php echo _l('hr_loan_view'); ?> #<?php echo str_pad($loan->id,4,'0',STR_PAD_LEFT); ?></h4>
                <p class="text-muted">
                  <a href="<?php echo admin_url('hr_module/employees/view/'.$loan->employee_id); ?>">
                    <?php echo htmlspecialchars($loan->first_name.' '.$loan->last_name); ?>
                  </a>
                  &nbsp;<span class="label label-default"><?php echo $loan->employee_code; ?></span>
                  <?php if ($loan->department_name): ?>
                  &nbsp;&middot;&nbsp;<?php echo htmlspecialchars($loan->department_name); ?>
                  <?php endif; ?>
                </p>
              </div>
              <span class="label label-<?php echo $badge[$loan->status] ?? 'default'; ?> tw-text-sm">
                <?php echo ucfirst($loan->status); ?>
              </span>
            </div>

            <!-- Amount summary cards -->
            <div class="row tw-mb-4">
              <div class="col-md-3"><div class="panel_s" style="background:#f8fafc"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-font-bold tw-text-lg"><?php echo number_format($loan->amount,2); ?></div>
                <div class="tw-text-xs text-muted">Loan Amount</div>
                <?php if (!empty($loan->requested_amount) && (float) $loan->requested_amount !== (float) $loan->amount): ?>
                <div class="tw-text-xs text-muted" title="Amount originally requested, before adjustment">
                  Originally requested: <?php echo number_format($loan->requested_amount, 2); ?>
                </div>
                <?php endif; ?>
              </div></div></div>
              <div class="col-md-3"><div class="panel_s" style="background:#f0fdf4"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-font-bold tw-text-lg text-success"><?php echo number_format($loan->total_repaid,2); ?></div>
                <div class="tw-text-xs text-muted">Total Repaid</div>
              </div></div></div>
              <div class="col-md-3"><div class="panel_s" style="background:#fff7ed"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-font-bold tw-text-lg text-warning"><?php echo number_format($loan->outstanding,2); ?></div>
                <div class="tw-text-xs text-muted">Outstanding</div>
              </div></div></div>
              <div class="col-md-3"><div class="panel_s" style="background:#eff6ff"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-font-bold tw-text-lg text-primary"><?php echo number_format($loan->monthly_installment,2); ?></div>
                <div class="tw-text-xs text-muted">Default Installment</div>
              </div></div></div>
            </div>

            <!-- Repayment progress -->
            <h5 class="tw-font-semibold">Repayment Progress</h5>
            <div class="progress tw-my-0 progress-bar-mini">
              <div class="progress-bar progress-bar-success no-percent-text not-dynamic" role="progressbar"
                   aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"
                   style="width: <?php echo $pct; ?>%" data-percent="<?php echo $pct; ?>"></div>
            </div>
            <p class="text-muted tw-text-sm"><?php echo $pct; ?>% repaid (<?php echo $loan->repayment_months; ?> months total)</p>

            <?php if ($loan->reason): ?>
            <h5 class="tw-font-semibold tw-mt-3">Reason</h5>
            <p><?php echo nl2br(htmlspecialchars($loan->reason)); ?></p>
            <?php endif; ?>

            <?php if ($loan->status === 'rejected' && $loan->rejection_reason): ?>
            <div class="alert alert-danger"><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($loan->rejection_reason); ?></div>
            <?php endif; ?>

            <!-- Repayment history -->
            <h5 class="tw-font-semibold tw-mt-4"><?php echo _l('hr_loan_repayments'); ?></h5>
            <?php if (empty($repayments)): ?>
            <p class="text-muted">No repayments recorded yet.</p>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-condensed table-hover">
                <thead><tr><th>Date</th><th>Amount</th><th>Via Payroll</th><th>Notes</th></tr></thead>
                <tbody>
                  <?php foreach ($repayments as $r): ?>
                  <tr>
                    <td><?php echo date('d M Y', strtotime($r->repayment_date)); ?></td>
                    <td><?php echo number_format($r->amount, 2); ?></td>
                    <td><?php echo $r->pay_month ? date('F', mktime(0,0,0,$r->pay_month,1)) . ' ' . $r->pay_year : '-'; ?></td>
                    <td><?php echo $r->notes ? htmlspecialchars($r->notes) : '-'; ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>

            <!-- Deduction request history -->
            <?php if (!empty($deduction_requests)): ?>
            <h5 class="tw-font-semibold tw-mt-4">Monthly Deduction Request History</h5>
            <div class="table-responsive">
              <table class="table table-condensed table-hover">
                <thead><tr><th>Month</th><th>Requested Amount</th><th>Status</th><th>Reviewed By</th><th>Notes</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php foreach ($deduction_requests as $dr): ?>
                  <tr>
                    <td><?php echo date('F Y', mktime(0,0,0,$dr->pay_month,1,$dr->pay_year)); ?></td>
                    <td><?php echo $dr->is_skip ? 'Skip' : number_format($dr->amount, 2); ?></td>
                    <td><span class="label label-<?php echo $req_badge[$dr->status] ?? 'default'; ?>"><?php echo ucfirst($dr->status); ?></span></td>
                    <td><?php echo $dr->reviewed_by_name ? htmlspecialchars($dr->reviewed_by_name) : '-'; ?></td>
                    <td><?php echo $dr->notes ? htmlspecialchars($dr->notes) : '-'; ?></td>
                    <td>
                      <?php if ($dr->status === 'pending'): ?>
                        <?php if (staff_can('edit','hr_loans')): ?>
                        <a href="#" class="text-success" onclick="document.getElementById('histApp<?php echo $dr->id; ?>').submit();return false;" title="Approve"><i class="fa fa-check"></i></a>
                        <form id="histApp<?php echo $dr->id; ?>" method="post" action="<?php echo admin_url('hr_module/loans/approve_deduction/'.$dr->id); ?>" style="display:none">
                          <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                        </form>
                        <a href="#" class="text-danger" onclick="document.getElementById('histRej<?php echo $dr->id; ?>').submit();return false;" title="Reject"><i class="fa fa-times"></i></a>
                        <form id="histRej<?php echo $dr->id; ?>" method="post" action="<?php echo admin_url('hr_module/loans/reject_deduction/'.$dr->id); ?>" style="display:none">
                          <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                        </form>
                        <?php endif; ?>
                        <?php if ($can_manage_deductions): ?>
                        <a href="#" class="hr-edit-hist-request" title="Edit"
                           data-month="<?php echo $dr->pay_month; ?>" data-year="<?php echo $dr->pay_year; ?>"
                           data-is-skip="<?php echo $dr->is_skip; ?>" data-carry-option="<?php echo $dr->carry_option; ?>"
                           data-notes="<?php echo htmlspecialchars($dr->notes ?? '', ENT_QUOTES); ?>"><i class="fa fa-edit"></i></a>
                        <a href="<?php echo admin_url('hr_module/loans/delete_deduction_request/'.$dr->id); ?>" class="_delete text-danger" title="Delete"><i class="fa fa-trash"></i></a>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>

            <!-- Adjustment history -->
            <?php if (!empty($adjustments)): ?>
            <h5 class="tw-font-semibold tw-mt-4">Amount Adjustment History</h5>
            <div class="table-responsive">
              <table class="table table-condensed table-hover">
                <thead><tr><th>Date</th><th>Amount</th><th>Installment</th><th>Adjusted By</th><th>Reason</th></tr></thead>
                <tbody>
                  <?php foreach ($adjustments as $adj): ?>
                  <tr>
                    <td><?php echo date('d M Y', strtotime($adj->created_at)); ?></td>
                    <td><?php echo number_format($adj->previous_amount, 2); ?> &rarr; <?php echo number_format($adj->new_amount, 2); ?></td>
                    <td><?php echo number_format($adj->previous_monthly_installment, 2); ?> &rarr; <?php echo number_format($adj->new_monthly_installment, 2); ?></td>
                    <td><?php echo $adj->adjusted_by_name ? htmlspecialchars($adj->adjusted_by_name) : '-'; ?></td>
                    <td><?php echo $adj->reason ? htmlspecialchars($adj->reason) : '-'; ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="col-md-4">
        <!-- Loan details -->
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold">Loan Details</h5>
            <table class="table table-condensed">
              <tr><td>Applied On</td><td><?php echo date('d M Y', strtotime($loan->created_at)); ?></td></tr>
              <?php if ($loan->disbursement_date): ?>
              <tr><td>Disbursed On</td><td><?php echo date('d M Y', strtotime($loan->disbursement_date)); ?></td></tr>
              <?php endif; ?>
              <?php if ($loan->approved_by_name): ?>
              <tr><td>Approved By</td><td><?php echo htmlspecialchars($loan->approved_by_name); ?></td></tr>
              <?php endif; ?>
              <?php if ($loan->attachment): ?>
              <tr><td>Attachment</td><td><a href="<?php echo admin_url('hr_module/loans/download/'.$loan->id); ?>" target="_blank"><i class="fa fa-file tw-mr-1"></i>View</a></td></tr>
              <?php endif; ?>
            </table>
          </div>
        </div>

        <!-- Monthly deduction request (current month) -->
        <?php if (in_array($loan->status, ['approved','active']) && $loan->outstanding > 0): ?>
        <div class="panel_s" style="border:1px solid #e2e8f0">
          <div class="panel-body">
            <h5 class="tw-font-semibold tw-mb-3">
              Deduction for <?php echo date('F Y'); ?>
            </h5>

            <?php $carry_label = ['next_month' => 'added to next month\'s deduction', 'extend_term' => 'handled by extending the repayment term by 1 month']; ?>

            <?php if ($loan->carry_forward_amount > 0): ?>
            <div class="alert alert-warning tw-py-2 tw-mb-2">
              <i class="fa fa-exclamation-circle tw-mr-1"></i>
              <strong><?php echo number_format($loan->carry_forward_amount, 2); ?></strong> carried over from a
              previously skipped month — will be added on top of the next deduction request.
            </div>
            <?php endif; ?>

            <?php if ($cur_req && $cur_req->status === 'approved' && $cur_req->payroll_id): ?>
              <?php if ($cur_req->is_skip): ?>
              <div class="alert alert-info tw-py-2 tw-mb-2">
                <i class="fa fa-check-double tw-mr-1"></i>
                This month was skipped — <?php echo $carry_label[$cur_req->carry_option] ?? 'recorded'; ?>.
              </div>
              <?php else: ?>
              <div class="alert alert-info tw-py-2 tw-mb-2">
                <i class="fa fa-check-double tw-mr-1"></i>
                <strong><?php echo number_format($cur_req->amount, 2); ?></strong> already deducted on this month's payroll.
                <?php if ($cur_req->carry_option): ?> The remainder was <?php echo $carry_label[$cur_req->carry_option] ?? 'recorded'; ?>.<?php endif; ?>
              </div>
              <?php endif; ?>

            <?php elseif ($cur_req && $cur_req->status === 'approved'): ?>
              <?php if ($cur_req->is_skip): ?>
              <div class="alert alert-success tw-py-2 tw-mb-2">
                <i class="fa fa-check-circle tw-mr-1"></i>
                Skip approved — will be <?php echo $carry_label[$cur_req->carry_option] ?? 'recorded'; ?>.
              </div>
              <?php else: ?>
              <div class="alert alert-success tw-py-2 tw-mb-2">
                <i class="fa fa-check-circle tw-mr-1"></i>
                <strong><?php echo number_format($cur_req->amount, 2); ?></strong> approved — will deduct on payroll.
                <?php if ($cur_req->carry_option): ?> The remainder will be <?php echo $carry_label[$cur_req->carry_option] ?? 'recorded'; ?>.<?php endif; ?>
              </div>
              <?php endif; ?>
              <?php if ($can_manage_deductions): ?>
              <button class="btn btn-xs btn-default tw-mt-1" disabled title="Already approved">
                <i class="fa fa-lock tw-mr-1"></i>Locked
              </button>
              <?php endif; ?>

            <?php elseif ($cur_req && $cur_req->status === 'pending'): ?>
              <?php if ($cur_req->is_skip): ?>
              <div class="alert alert-warning tw-py-2 tw-mb-2">
                <i class="fa fa-clock-o tw-mr-1"></i>
                Requested to skip this month (<?php echo $carry_label[$cur_req->carry_option] ?? ''; ?>) — pending HR approval.
              </div>
              <?php else: ?>
              <div class="alert alert-warning tw-py-2 tw-mb-2">
                <i class="fa fa-clock-o tw-mr-1"></i>
                <strong><?php echo number_format($cur_req->amount, 2); ?></strong> — pending HR approval.
                <?php if ($cur_req->carry_option): ?> The remainder would be <?php echo $carry_label[$cur_req->carry_option] ?? 'recorded'; ?>.<?php endif; ?>
              </div>
              <?php endif; ?>
              <?php if (staff_can('edit','hr_loans')): ?>
              <button class="btn btn-xs btn-success tw-mr-1"
                      onclick="document.getElementById('appForm<?php echo $cur_req->id;?>').submit()">
                <i class="fa fa-check tw-mr-1"></i>Approve
              </button>
              <form id="appForm<?php echo $cur_req->id;?>" method="post"
                    action="<?php echo admin_url('hr_module/loans/approve_deduction/'.$cur_req->id); ?>" style="display:none">
                <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              </form>
              <button class="btn btn-xs btn-danger tw-mr-1"
                      onclick="document.getElementById('rejForm<?php echo $cur_req->id;?>').submit()">
                <i class="fa fa-times tw-mr-1"></i>Reject
              </button>
              <form id="rejForm<?php echo $cur_req->id;?>" method="post"
                    action="<?php echo admin_url('hr_module/loans/reject_deduction/'.$cur_req->id); ?>" style="display:none">
                <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              </form>
              <?php endif; ?>
              <?php if ($can_manage_deductions): ?>
              <button class="btn btn-xs btn-default tw-mt-1" data-toggle="modal" data-target="#deductModal">
                <i class="fa fa-edit tw-mr-1"></i>Edit Request
              </button>
              <a href="<?php echo admin_url('hr_module/loans/delete_deduction_request/'.$cur_req->id); ?>" class="btn btn-xs btn-default _delete tw-mt-1">
                <i class="fa fa-trash tw-mr-1"></i>Delete
              </a>
              <?php endif; ?>

            <?php elseif ($cur_req && $cur_req->status === 'rejected'): ?>
              <div class="alert alert-danger tw-py-2 tw-mb-2">
                <i class="fa fa-times-circle tw-mr-1"></i>
                <?php echo $cur_req->is_skip ? 'Skip request' : 'Request for <strong>' . number_format($cur_req->amount, 2) . '</strong>'; ?> was rejected.
              </div>
              <?php if ($can_manage_deductions): ?>
              <button class="btn btn-sm btn-primary btn-block" data-toggle="modal" data-target="#deductModal">
                <i class="fa fa-redo tw-mr-1"></i>Re-request Deduction
              </button>
              <?php endif; ?>

            <?php else: ?>
              <p class="text-muted tw-text-sm tw-mb-2">No deduction request for this month.<br>
                <small>Payroll will automatically deduct the standard installment of
                <strong><?php echo number_format($loan->monthly_installment + $loan->carry_forward_amount, 2); ?></strong>
                unless you submit a request to change the amount or skip this month.</small>
              </p>
              <?php if ($can_manage_deductions): ?>
              <button class="btn btn-sm btn-primary btn-block" data-toggle="modal" data-target="#deductModal">
                <i class="fa-regular fa-plus tw-mr-1"></i>Request Deduction
              </button>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <?php
          $can_approve_reject = $loan->status === 'pending' && staff_can('edit','hr_loans');
          $can_repay          = in_array($loan->status, ['approved','active']) && staff_can('edit','hr_loans');
          $can_view_deductions = staff_can('view','hr_loans');
          $can_delete_loan    = !in_array($loan->status, ['approved','active','closed']) && staff_can('delete','hr_loans');
        ?>
        <?php if ($can_approve_reject || $can_repay || $can_view_deductions || $can_delete_loan || $can_adjust): ?>
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold tw-mb-3">Actions</h5>

            <?php if ($can_approve_reject): ?>
            <div class="row tw-mb-2">
              <div class="col-xs-6">
                <button class="btn btn-success btn-block" data-toggle="modal" data-target="#approveModal">
                  <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_loan_approve'); ?>
                </button>
              </div>
              <div class="col-xs-6">
                <button class="btn btn-danger btn-block" data-toggle="modal" data-target="#rejectModal">
                  <i class="fa fa-times tw-mr-1"></i><?php echo _l('hr_loan_reject'); ?>
                </button>
              </div>
            </div>
            <?php endif; ?>

            <?php
              // Adjust and Delete are each independent - split evenly across
              // however many of these two apply, in their own row (kept
              // separate from Approve/Reject above so neither row's button
              // labels get squeezed).
              $row2_count = ($can_adjust ? 1 : 0) + ($can_delete_loan ? 1 : 0);
              $row2_col   = $row2_count === 2 ? 'col-xs-6' : 'col-xs-12';
            ?>
            <?php if ($can_adjust || $can_delete_loan): ?>
            <div class="row tw-mb-2">
              <?php if ($can_adjust): ?>
              <div class="<?php echo $row2_col; ?>">
                <button class="btn btn-default btn-block" data-toggle="modal" data-target="#adjustModal">
                  <i class="fa fa-sliders tw-mr-1"></i>Adjust
                </button>
              </div>
              <?php endif; ?>
              <?php if ($can_delete_loan): ?>
              <div class="<?php echo $row2_col; ?>">
                <a href="<?php echo admin_url('hr_module/loans/delete/'.$loan->id); ?>" class="btn btn-danger btn-block _delete">
                  <i class="fa fa-trash tw-mr-1"></i>Delete
                </a>
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($can_repay): ?>
            <button class="btn btn-primary btn-block tw-mb-2" data-toggle="modal" data-target="#repayModal">
              <i class="fa fa-money-bill-wave tw-mr-1"></i>Record Repayment
            </button>
            <?php endif; ?>

            <?php if ($can_view_deductions): ?>
            <a href="<?php echo admin_url('hr_module/loans/deduction_requests'); ?>" class="btn btn-default btn-block tw-mb-2">
              <i class="fa fa-list tw-mr-1"></i>All Deduction Requests
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Request Deduction Modal -->
<?php if (in_array($loan->status, ['approved','active']) && $can_manage_deductions): ?>
<div class="modal fade" id="deductModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title">Request Monthly Deduction</h4>
    </div>
    <?php echo form_open(admin_url('hr_module/loans/request_deduction/'.$loan->id)); ?>
    <div class="modal-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group select-placeholder">
            <label>Month <span class="text-danger">*</span></label>
            <select name="pay_month" class="selectpicker" data-width="100%" required>
              <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?php echo $m; ?>" <?php echo $m === $cur_month ? 'selected' : ''; ?>>
                <?php echo date('F', mktime(0,0,0,$m,1)); ?>
              </option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group select-placeholder">
            <label>Year <span class="text-danger">*</span></label>
            <select name="pay_year" class="selectpicker" data-width="100%" required>
              <?php for ($y = $cur_year - 1; $y <= $cur_year + 1; $y++): ?>
              <option value="<?php echo $y; ?>" <?php echo $y === $cur_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="form-group">
        <div class="checkbox checkbox-primary">
          <input type="checkbox" name="is_skip" id="skipCheck" value="1" <?php echo ($cur_req && $cur_req->is_skip) ? 'checked' : ''; ?>>
          <label for="skipCheck">Skip this month — don't deduct anything</label>
        </div>
      </div>
      <?php
        // Deduction amount must be a multiple of 500, same rule as the loan
        // application's amount/installment - the one exception is the exact
        // outstanding balance, so the loan can still be paid off in full even
        // when that isn't itself a clean multiple of 500.
        $deduct_default = $cur_req && !$cur_req->is_skip
            ? (float) $cur_req->amount
            : ((float) $loan->monthly_installment + (float) $loan->carry_forward_amount);
        $deduct_outstanding = (float) $loan->outstanding;
      ?>
      <div class="form-group" id="amountGroup">
        <label>Deduction Amount <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-addon"><?php echo get_option('currency_symbol') ?: 'BDT'; ?></span>
          <input type="number" step="500" min="500" name="amount" id="deductAmount" class="form-control"
                 value="<?php echo round($deduct_default, 2); ?>">
        </div>
        <p class="help-block tw-text-xs">
          Default installment: <strong><?php echo number_format($loan->monthly_installment, 2); ?></strong>
          <?php if ($loan->carry_forward_amount > 0): ?>
          + <strong><?php echo number_format($loan->carry_forward_amount, 2); ?></strong> carried over from a skipped month
          <?php endif; ?>
          &nbsp;&middot;&nbsp; Outstanding: <strong><?php echo number_format($loan->outstanding, 2); ?></strong>
          &nbsp;&middot;&nbsp; Must be a multiple of 500, or exactly the outstanding balance for a full payoff.
        </p>
      </div>
      <div class="form-group" id="carryOptionGroup" style="display:none">
        <label>What should happen to the remaining <span id="carryShortfallAmount">-</span>?</label>
        <div class="radio radio-primary">
          <input type="radio" name="carry_option" id="carryNext" value="next_month"
                 <?php echo (!$cur_req || $cur_req->carry_option !== 'extend_term') ? 'checked' : ''; ?>>
          <label for="carryNext">Add it to next month's deduction</label>
        </div>
        <div class="radio radio-primary">
          <input type="radio" name="carry_option" id="carryExtend" value="extend_term"
                 <?php echo ($cur_req && $cur_req->carry_option === 'extend_term') ? 'checked' : ''; ?>>
          <label for="carryExtend">Extend repayment period by 1 month instead</label>
        </div>
      </div>
      <div class="form-group">
        <label>Notes <small class="text-muted">(optional — reason for custom amount or skip)</small></label>
        <textarea name="notes" class="form-control" rows="2"
                  placeholder="e.g. Partial payment this month due to..."><?php echo $cur_req ? htmlspecialchars($cur_req->notes ?? '') : ''; ?></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane tw-mr-1"></i>Submit Request</button>
    </div>
    <?php echo form_close(); ?>
  </div></div>
</div>
<?php endif; ?>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title"><?php echo _l('hr_loan_approve'); ?></h4></div>
    <?php echo form_open(admin_url('hr_module/loans/approve/'.$loan->id)); ?>
    <div class="modal-body">
      <div class="form-group"><label>Disbursement Date</label>
        <div class="input-group date">
          <input type="text" name="disbursement_date" class="form-control datepicker" autocomplete="off" value="<?php echo _d(date('Y-m-d')); ?>">
          <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-success"><i class="fa fa-check tw-mr-1"></i>Approve</button>
    </div>
    <?php echo form_close(); ?>
  </div></div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title"><?php echo _l('hr_loan_reject'); ?></h4></div>
    <?php echo form_open(admin_url('hr_module/loans/reject/'.$loan->id)); ?>
    <div class="modal-body">
      <div class="form-group"><label>Reason for Rejection <span class="text-danger">*</span></label>
        <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-danger"><i class="fa fa-times tw-mr-1"></i>Reject</button>
    </div>
    <?php echo form_close(); ?>
  </div></div>
</div>

<!-- Repayment Modal -->
<div class="modal fade" id="repayModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title">Record Manual Repayment</h4></div>
    <?php echo form_open(admin_url('hr_module/loans/add_repayment/'.$loan->id)); ?>
    <?php
      // Same rule as the Deduction Request modal's Amount field above: must be
      // a multiple of 500, with one exception - the exact outstanding balance,
      // so the loan can still be paid off in full even when that isn't a clean
      // multiple of 500.
      $repay_default     = (float) $loan->monthly_installment;
      $repay_outstanding = (float) $loan->outstanding;
    ?>
    <div class="modal-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group"><label>Amount <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-addon"><?php echo get_option('currency_symbol') ?: 'BDT'; ?></span>
              <input type="number" step="500" min="500" max="<?php echo $repay_outstanding; ?>"
                     name="amount" id="repayAmount" class="form-control"
                     value="<?php echo round($repay_default, 2); ?>" required>
            </div>
            <p class="help-block tw-text-xs">
              Must be a multiple of 500, or exactly the outstanding balance
              (<strong><?php echo number_format($repay_outstanding, 2); ?></strong>) for a full payoff.
            </p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group"><label>Date <span class="text-danger">*</span></label>
            <div class="input-group date">
              <input type="text" name="repayment_date" class="form-control datepicker" autocomplete="off" value="<?php echo _d(date('Y-m-d')); ?>" required>
              <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
            </div>
          </div>
        </div>
      </div>
      <div class="form-group"><label>Notes</label>
        <input type="text" name="notes" class="form-control" placeholder="Optional note...">
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-primary">Save</button>
    </div>
    <?php echo form_close(); ?>
  </div></div>
</div>

<?php if ($can_adjust): ?>
<!-- Adjust Amount Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title">Adjust Loan Amount</h4></div>
    <?php echo form_open(admin_url('hr_module/loans/adjust/'.$loan->id)); ?>
    <div class="modal-body">
      <p class="text-muted tw-text-sm">
        Use this when less is actually being disbursed than was requested
        (e.g. requested <?php echo number_format($loan->amount, 2); ?>, but
        only a smaller amount can be given). The installment/term below is
        recalculated for the new amount, and the change is recorded in the
        Amount Adjustment History below.
      </p>
      <div class="form-group"><label>New Amount <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-addon"><?php echo get_option('currency_symbol') ?: 'BDT'; ?></span>
          <input type="number" step="500" min="500" name="new_amount" id="adjust_amount" class="form-control"
                 value="<?php echo $loan->amount; ?>" required>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>New Monthly Installment <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-addon"><?php echo get_option('currency_symbol') ?: 'BDT'; ?></span>
              <input type="number" step="500" min="500" name="monthly_installment" id="adjust_installment"
                     class="form-control" value="<?php echo $loan->monthly_installment; ?>" required>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label><?php echo _l('hr_loan_repayment_months'); ?></label>
            <div class="input-group">
              <input type="number" name="repayment_months" id="adjust_months" class="form-control" readonly value="<?php echo $loan->repayment_months; ?>">
              <span class="input-group-addon">mo</span>
            </div>
          </div>
        </div>
      </div>
      <p class="text-muted tw-text-sm" id="adjustCalcSummary" style="display:none"></p>
      <div class="form-group"><label>Reason</label>
        <textarea name="reason" class="form-control" rows="2" placeholder="Optional note, e.g. budget constraints..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-primary"><i class="fa fa-sliders tw-mr-1"></i>Save Adjustment</button>
    </div>
    <?php echo form_close(); ?>
  </div></div>
</div>
<?php endif; ?>
<?php init_tail(); ?>
<script>
$(function () {
    var totalDue = <?php echo (float) $loan->monthly_installment + (float) $loan->carry_forward_amount; ?>;
    var loanOutstanding = <?php echo (float) $loan->outstanding; ?>;

    // Shared by the Deduction Request and Manual Repayment amount fields -
    // must be a multiple of 500, unless it's exactly the outstanding balance
    // (a full payoff doesn't have to land on a round step).
    function isValidStepAmount(v) {
        return v > 0 && (Math.abs(Math.round(v / 500) * 500 - v) < 0.01 || Math.abs(v - loanOutstanding) < 0.01);
    }

    function toggleSkip() {
        var skip   = $('#skipCheck').is(':checked');
        var amount = parseFloat($('#deductAmount').val()) || 0;
        var shortfall = skip ? totalDue : Math.max(0, totalDue - amount);
        var showCarry = shortfall > 0;

        $('#amountGroup').toggle(!skip);
        $('#carryOptionGroup').toggle(showCarry);
        $('#deductAmount').prop('required', !skip);
        if (showCarry) $('#carryShortfallAmount').text(shortfall.toFixed(2));
    }
    $('#skipCheck').on('change', toggleSkip);
    $('#deductAmount').on('input change', toggleSkip);
    toggleSkip();

    $('#deductModal form').on('submit', function (e) {
        var skip   = $('#skipCheck').is(':checked');
        var amount = parseFloat($('#deductAmount').val()) || 0;
        if (!skip && !isValidStepAmount(amount)) {
            alert('Deduction amount must be a multiple of 500, or exactly the outstanding balance (' + loanOutstanding.toFixed(2) + ') for a full payoff.');
            e.preventDefault(); return;
        }
    });

    $('#repayModal form').on('submit', function (e) {
        var amount = parseFloat($('#repayAmount').val()) || 0;
        if (!isValidStepAmount(amount)) {
            alert('Repayment amount must be a multiple of 500, or exactly the outstanding balance (' + loanOutstanding.toFixed(2) + ') for a full payoff.');
            e.preventDefault(); return;
        }
    });

    // Editing a pending request from the history table (any month, not just the current
    // one) - point the shared modal at that month/year and restore its skip/carry choice.
    // The amount dropdown keeps the loan's current steps; pick again if it needs changing.
    $(document).on('click', '.hr-edit-hist-request', function (e) {
        e.preventDefault();
        var $el = $(this);
        $('select[name="pay_month"]').val($el.data('month')).selectpicker('refresh');
        $('select[name="pay_year"]').val($el.data('year')).selectpicker('refresh');
        $('#skipCheck').prop('checked', $el.data('is-skip') == 1);
        if ($el.data('carry-option') === 'extend_term') $('#carryExtend').prop('checked', true);
        else $('#carryNext').prop('checked', true);
        $('textarea[name="notes"]').val($el.data('notes') || '');
        toggleSkip();
        $('#deductModal').modal('show');
    });

    // Adjust modal - amount and installment must both be a multiple of 500,
    // typed directly (same rule as the Apply form's Loan Amount/Installment).
    var ADJUST_STEP = 500;
    var $adjustAmount      = $('#adjust_amount');
    var $adjustMonths      = $('#adjust_months');
    var $adjustInstallment = $('#adjust_installment');
    var $adjustSummary     = $('#adjustCalcSummary');

    function adjustFmt(n) { return parseFloat(n).toFixed(2); }

    function isAdjustStepValid(v) {
        return v > 0 && Math.abs(Math.round(v / ADJUST_STEP) * ADJUST_STEP - v) < 0.01;
    }

    function updateAdjustSummary(amount, months, install) {
        var lastInstallment = amount - (months - 1) * install;
        $adjustSummary.html(
            '<i class="fa fa-calculator tw-mr-1"></i>' +
            '<strong>' + adjustFmt(install) + '</strong> &times; <strong>' + months + ' months</strong>' +
            (lastInstallment < install
                ? ' (last month: <strong>' + adjustFmt(lastInstallment) + '</strong>)'
                : '')
        ).show();
    }

    function updateAdjustMonths() {
        var amount  = parseFloat($adjustAmount.val()) || 0;
        var install = parseFloat($adjustInstallment.val()) || 0;
        if (amount > 0 && install > 0) {
            var months = Math.ceil(amount / install);
            $adjustMonths.val(months);
            updateAdjustSummary(amount, months, install);
        } else {
            $adjustMonths.val('');
            $adjustSummary.hide();
        }
    }

    $adjustAmount.on('input', updateAdjustMonths);
    $adjustInstallment.on('input', updateAdjustMonths);
    updateAdjustMonths();

    $('#adjustModal form').on('submit', function (e) {
        var amount  = parseFloat($adjustAmount.val()) || 0;
        var install = parseFloat($adjustInstallment.val()) || 0;

        if (!isAdjustStepValid(amount)) {
            alert('New amount must be a multiple of ' + ADJUST_STEP + ' (e.g. 500, 1000, 1500 ...).');
            e.preventDefault(); return;
        }
        if (!isAdjustStepValid(install)) {
            alert('New monthly installment must be a multiple of ' + ADJUST_STEP + ' (e.g. 500, 1000, 1500 ...).');
            e.preventDefault(); return;
        }
    });
});
</script>
