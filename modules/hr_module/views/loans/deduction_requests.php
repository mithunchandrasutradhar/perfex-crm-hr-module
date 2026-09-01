<?php defined('BASEPATH') or exit('No direct script access allowed');
$req_badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
$cur_month = (int) date('n');
$cur_year  = (int) date('Y');
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/loans'); ?>">Loan</a></li>
          <li class="active">Deduction Requests</li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
              <h4 class="tw-font-semibold tw-mb-0">Monthly Loan Deduction Requests</h4>
            </div>

            <!-- Filters -->
            <form method="get" class="tw-mb-4">
              <div class="row">
                <div class="col-md-3">
                  <div class="select-placeholder">
                  <select name="status" class="selectpicker" data-width="100%">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','approved','rejected'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo ($filters['status'] ?? '') === $s ? 'selected' : ''; ?>>
                      <?php echo ucfirst($s); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="select-placeholder">
                  <select name="pay_month" class="selectpicker" data-width="100%">
                    <option value="">All Months</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo (int)($filters['pay_month'] ?? 0) === $m ? 'selected' : ''; ?>>
                      <?php echo date('F', mktime(0,0,0,$m,1)); ?>
                    </option>
                    <?php endfor; ?>
                  </select>
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="select-placeholder">
                  <select name="pay_year" class="selectpicker" data-width="100%">
                    <option value="">All Years</option>
                    <?php for ($y = $cur_year - 1; $y <= $cur_year + 1; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo (int)($filters['pay_year'] ?? 0) === $y ? 'selected' : ''; ?>>
                      <?php echo $y; ?>
                    </option>
                    <?php endfor; ?>
                  </select>
                  </div>
                </div>
                <div class="col-md-2">
                  <button type="submit" class="btn btn-default">
                    <i class="fa fa-filter tw-mr-1"></i>Filter
                  </button>
                  <a href="<?php echo admin_url('hr_module/loans/deduction_requests'); ?>" class="btn btn-link">Reset</a>
                </div>
              </div>
            </form>

            <?php if (empty($requests)): ?>
            <div class="alert alert-info">No deduction requests found.</div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover table-condensed">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Employee</th>
                    <th>Loan</th>
                    <th>Month</th>
                    <th>Requested</th>
                    <th>Default Installment</th>
                    <th>Outstanding</th>
                    <th>Status</th>
                    <th>Reviewed By</th>
                    <th>Notes</th>
                    <?php if (staff_can('edit','hr_loans')): ?>
                    <th>Actions</th>
                    <?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($requests as $r): ?>
                  <tr>
                    <td><?php echo $r->id; ?></td>
                    <td>
                      <a href="<?php echo admin_url('hr_module/loans/view/'.$r->loan_id); ?>">
                        <?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?>
                      </a>
                      <br><small class="text-muted"><?php echo $r->employee_code; ?></small>
                    </td>
                    <td>
                      <a href="<?php echo admin_url('hr_module/loans/view/'.$r->loan_id); ?>">
                        #<?php echo str_pad($r->loan_id, 4, '0', STR_PAD_LEFT); ?>
                      </a>
                    </td>
                    <td><?php echo date('M Y', mktime(0,0,0,$r->pay_month,1,$r->pay_year)); ?></td>
                    <td class="tw-font-semibold">
                      <?php if ($r->is_skip): ?>
                      <span class="label label-default">Skip</span>
                      <small class="text-muted"><?php echo $r->carry_option === 'extend_term' ? '(+1 month)' : '(→ next month)'; ?></small>
                      <?php else: ?>
                      <?php echo number_format($r->amount, 2); ?>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted"><?php echo number_format($r->monthly_installment, 2); ?></td>
                    <td class="text-warning"><?php echo number_format($r->outstanding, 2); ?></td>
                    <td>
                      <?php if ($r->status === 'approved' && $r->payroll_id): ?>
                      <span class="label label-info">Deducted</span>
                      <?php else: ?>
                      <span class="label label-<?php echo $req_badge[$r->status] ?? 'default'; ?>">
                        <?php echo ucfirst($r->status); ?>
                      </span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo $r->reviewed_by_name ? htmlspecialchars($r->reviewed_by_name) : '-'; ?></td>
                    <td><?php echo $r->notes ? '<span title="'.htmlspecialchars($r->notes).'"><i class="fa fa-comment-o"></i></span>' : '-'; ?></td>
                    <?php if (staff_can('edit','hr_loans')): ?>
                    <td>
                      <?php if ($r->status === 'pending'): ?>
                      <form method="post" action="<?php echo admin_url('hr_module/loans/approve_deduction/'.$r->id); ?>" style="display:inline">
                        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                        <button type="submit" class="btn btn-xs btn-success tw-mr-1"
                                onclick="return confirm('Approve deduction of <?php echo number_format($r->amount,2); ?> for <?php echo date("M Y", mktime(0,0,0,$r->pay_month,1,$r->pay_year)); ?>?')">
                          <i class="fa fa-check"></i> Approve
                        </button>
                      </form>
                      <form method="post" action="<?php echo admin_url('hr_module/loans/reject_deduction/'.$r->id); ?>" style="display:inline">
                        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                        <button type="submit" class="btn btn-xs btn-danger"
                                onclick="return confirm('Reject this deduction request?')">
                          <i class="fa fa-times"></i> Reject
                        </button>
                      </form>
                      <?php else: ?>
                      <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <?php endif; ?>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <!-- Summary counts -->
            <?php
            $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
            foreach ($requests as $r) { if (isset($counts[$r->status])) $counts[$r->status]++; }
            ?>
            <p class="text-muted tw-text-sm tw-mt-2">
              Total: <?php echo count($requests); ?> &nbsp;&middot;&nbsp;
              <span class="text-warning"><?php echo $counts['pending']; ?> pending</span> &nbsp;&middot;&nbsp;
              <span class="text-success"><?php echo $counts['approved']; ?> approved</span> &nbsp;&middot;&nbsp;
              <span class="text-danger"><?php echo $counts['rejected']; ?> rejected</span>
            </p>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
