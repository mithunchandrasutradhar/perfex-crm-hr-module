<?php defined('BASEPATH') or exit('No direct script access allowed');
$badge = ['pending'=>'default','approved'=>'warning','active'=>'info','rejected'=>'danger','closed'=>'success'];
$pct   = $loan->amount > 0 ? min(100, round(($loan->total_repaid / $loan->amount) * 100)) : 0;
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
                <div class="tw-text-xs text-muted">Monthly Installment</div>
              </div></div></div>
            </div>

            <!-- Repayment progress -->
            <h5 class="tw-font-semibold">Repayment Progress</h5>
            <div class="progress" style="height:10px">
              <div class="progress-bar progress-bar-success" style="width:<?php echo $pct; ?>%"></div>
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
              <tr><td>Attachment</td><td><a href="<?php echo base_url('uploads/hr_module/loans/'.$loan->attachment); ?>" target="_blank"><i class="fa fa-file tw-mr-1"></i>View</a></td></tr>
              <?php endif; ?>
            </table>
          </div>
        </div>

        <!-- Actions -->
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold tw-mb-3">Actions</h5>

            <?php if ($loan->status === 'pending' && staff_can('edit','hr_loans')): ?>
            <!-- Approve -->
            <button class="btn btn-success btn-block tw-mb-2" data-toggle="modal" data-target="#approveModal">
              <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_loan_approve'); ?>
            </button>
            <!-- Reject -->
            <button class="btn btn-danger btn-block tw-mb-2" data-toggle="modal" data-target="#rejectModal">
              <i class="fa fa-times tw-mr-1"></i><?php echo _l('hr_loan_reject'); ?>
            </button>
            <?php endif; ?>

            <?php if (in_array($loan->status, ['approved','active']) && staff_can('edit','hr_loans')): ?>
            <!-- Manual repayment -->
            <button class="btn btn-primary btn-block tw-mb-2" data-toggle="modal" data-target="#repayModal">
              <i class="fa fa-money-bill-wave tw-mr-1"></i>Record Repayment
            </button>
            <?php endif; ?>

            <?php if (!in_array($loan->status, ['active','closed']) && staff_can('delete','hr_loans')): ?>
            <a href="<?php echo admin_url('hr_module/loans/delete/'.$loan->id); ?>" class="btn btn-default btn-block _delete">
              <i class="fa fa-trash tw-mr-1"></i>Delete
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title"><?php echo _l('hr_loan_approve'); ?></h4></div>
    <?php echo form_open(admin_url('hr_module/loans/approve/'.$loan->id)); ?>
    <div class="modal-body">
      <div class="form-group"><label>Disbursement Date</label>
        <input type="date" name="disbursement_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
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
    <div class="modal-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group"><label>Amount <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" max="<?php echo $loan->outstanding; ?>"
                   name="amount" class="form-control" value="<?php echo $loan->monthly_installment; ?>" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group"><label>Date <span class="text-danger">*</span></label>
            <input type="date" name="repayment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
          </div>
        </div>
      </div>
      <div class="form-group"><label>Notes</label>
        <input type="text" name="notes" class="form-control" placeholder="Optional note...">
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-primary"><i class="fa fa-save tw-mr-1"></i>Save</button>
    </div>
    <?php echo form_close(); ?>
  </div></div>
</div>
<?php init_tail(); ?>
