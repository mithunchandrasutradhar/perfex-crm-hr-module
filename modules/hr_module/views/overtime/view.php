<?php defined('BASEPATH') or exit('No direct script access allowed');
$badge = ['pending'=>'default','approved'=>'success','rejected'=>'danger'];
$day_type_labels = [
    'weekend'            => _l('hr_overtime_weekend'),
    'government_holiday' => _l('hr_overtime_government_holiday'),
    'company_holiday'    => _l('hr_overtime_company_holiday'),
];
// The request's owner may edit/delete their own pending request even without the
// module-wide edit/delete capability - mirrors the list page's row-options.
$can_self_edit = (int) $overtime->employee_id === hr_get_own_employee_id() && staff_can('create', 'hr_overtime');
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/overtime'); ?>"><?php echo _l('hr_overtime_list'); ?></a></li>
          <li class="active">#<?php echo $overtime->id; ?></li>
        </ol>
      </div>
    </div>

    <div class="row">
      <div class="col-md-7">
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
              <div>
                <h4 class="tw-font-bold tw-mb-1"><?php echo _l('hr_overtime_view'); ?></h4>
                <p class="text-muted tw-mb-0">
                  <a href="<?php echo admin_url('hr_module/employees/view/'.$overtime->employee_id); ?>">
                    <?php echo htmlspecialchars($overtime->first_name.' '.$overtime->last_name); ?>
                  </a>
                  &nbsp;<span class="label label-default"><?php echo $overtime->employee_code; ?></span>
                </p>
                <p class="text-muted"><?php echo htmlspecialchars($overtime->department_name ?? '-'); ?>
                  <?php if($overtime->designation_name): ?> &middot; <?php echo htmlspecialchars($overtime->designation_name); ?><?php endif; ?>
                </p>
              </div>
              <span class="label label-<?php echo $badge[$overtime->status] ?? 'default'; ?> tw-text-sm">
                <?php echo ucfirst($overtime->status); ?>
              </span>
            </div>

            <div class="row">
              <div class="col-md-4 col-sm-6"><div class="panel_s" style="background:#eff6ff"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-font-bold tw-text-lg text-primary">
                  <?php echo $overtime->first_date === $overtime->last_date
                      ? date('d M Y', strtotime($overtime->first_date))
                      : date('d M', strtotime($overtime->first_date)) . ' - ' . date('d M Y', strtotime($overtime->last_date)); ?>
                </div>
                <div class="tw-text-xs text-muted">Period</div>
              </div></div></div>
              <div class="col-md-4 col-sm-6"><div class="panel_s" style="background:#f0fdf4"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-font-bold tw-text-lg text-success"><?php echo $overtime->day_count; ?> <?php echo $overtime->day_count == 1 ? 'day' : 'days'; ?></div>
                <div class="tw-text-xs text-muted"><?php echo implode(', ', array_map(function($t) use ($day_type_labels){ return $day_type_labels[$t] ?? $t; }, explode(',', $overtime->day_types))); ?></div>
              </div></div></div>
              <div class="col-md-4 col-sm-6"><div class="panel_s" style="background:#f8fafc"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-font-bold tw-text-lg"><?php echo number_format($overtime->total_amount, 2); ?></div>
                <div class="tw-text-xs text-muted">Total Amount</div>
              </div></div></div>
            </div>

            <h5 class="tw-font-semibold tw-mt-3">Overduty Dates</h5>
            <table class="table table-condensed tw-mb-0">
              <thead><tr><th>Date</th><th>Day Type</th><th>Rate</th><th class="text-right">Amount</th></tr></thead>
              <tbody>
              <?php foreach ($dates as $d): ?>
              <tr>
                <td><?php echo date('D, d M Y', strtotime($d->overtime_date)); ?></td>
                <td><?php echo $day_type_labels[$d->day_type] ?? '-'; ?><?php if ($d->holiday_name): ?> <small class="text-muted">(<?php echo htmlspecialchars($d->holiday_name); ?>)</small><?php endif; ?></td>
                <td><?php echo $d->rate_multiplier; ?>x</td>
                <td class="text-right"><?php echo number_format($d->total_amount, 2); ?></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>

            <?php if ($overtime->reason): ?>
            <h5 class="tw-font-semibold tw-mt-2">Reason</h5>
            <p><?php echo nl2br(htmlspecialchars($overtime->reason)); ?></p>
            <?php endif; ?>

            <?php if ($overtime->status === 'rejected' && $overtime->rejection_reason): ?>
            <div class="alert alert-danger tw-mt-3">
              <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($overtime->rejection_reason); ?>
            </div>
            <?php endif; ?>

            <hr>
            <div class="tw-flex tw-gap-4 tw-text-sm text-muted">
              <span>Submitted: <?php echo date('d M Y', strtotime($overtime->created_at)); ?></span>
              <?php if ($overtime->soft_approved_at): ?>
              <span>Soft <?php echo ucfirst($overtime->soft_status); ?>: <?php echo date('d M Y', strtotime($overtime->soft_approved_at)); ?>
                <?php if ($overtime->soft_approved_by_name): ?> by <?php echo htmlspecialchars($overtime->soft_approved_by_name); ?><?php endif; ?>
              </span>
              <?php endif; ?>
              <?php if ($overtime->approved_at): ?>
              <span>Processed: <?php echo date('d M Y', strtotime($overtime->approved_at)); ?>
                <?php if ($overtime->approved_by_name): ?> by <?php echo htmlspecialchars($overtime->approved_by_name); ?><?php endif; ?>
              </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions sidebar -->
      <div class="col-md-5">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold tw-mb-3">Actions</h5>

            <?php if ($overtime->status === 'pending'): ?>
              <?php if (!empty($can_soft_approve)): ?>
              <!-- Soft Approve/Reject: informational-only pre-approval, never blocks the real Approve/Reject below -->
              <?php if ($overtime->soft_approved_at): ?>
              <div class="alert alert-info tw-py-2 tw-mb-2 tw-text-sm">
                Soft <?php echo ucfirst($overtime->soft_status); ?> by <strong><?php echo htmlspecialchars($overtime->soft_approved_by_name ?: '-'); ?></strong>
                &mdash; <?php echo date('d M Y', strtotime($overtime->soft_approved_at)); ?>
              </div>
              <?php endif; ?>
              <a href="<?php echo admin_url('hr_module/overtime/soft_approve/'.$overtime->id); ?>"
                 class="btn btn-success btn-outline btn-block tw-mb-2"
                 onclick="return confirm('Soft approve this overduty request?')">
                <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_overtime_soft_approve'); ?>
              </a>
              <a href="<?php echo admin_url('hr_module/overtime/soft_reject/'.$overtime->id); ?>"
                 class="btn btn-danger btn-outline btn-block tw-mb-2"
                 onclick="return confirm('Soft reject this overduty request?')">
                <i class="fa fa-times tw-mr-1"></i><?php echo _l('hr_overtime_soft_reject'); ?>
              </a>
              <hr>
              <?php endif; ?>
              <?php if (staff_can('edit','hr_overtime')): ?>
              <a href="<?php echo admin_url('hr_module/overtime/approve/'.$overtime->id); ?>"
                 class="btn btn-success btn-block tw-mb-2"
                 onclick="return confirm('Approve this overduty request?')">
                <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_overtime_approve'); ?>
              </a>
              <button class="btn btn-danger btn-block tw-mb-2" data-toggle="modal" data-target="#rejectModal">
                <i class="fa fa-times tw-mr-1"></i><?php echo _l('hr_overtime_reject'); ?>
              </button>
              <?php endif; ?>
              <?php if (staff_can('edit','hr_overtime') || $can_self_edit): ?>
              <a href="<?php echo admin_url('hr_module/overtime/edit/'.$overtime->id); ?>" class="btn btn-default btn-block tw-mb-2">
                <i class="fa fa-pencil-alt tw-mr-1"></i><?php echo _l('hr_overtime_edit'); ?>
              </a>
              <?php endif; ?>
              <?php if (staff_can('delete','hr_overtime') || $can_self_edit): ?>
              <a href="<?php echo admin_url('hr_module/overtime/delete/'.$overtime->id); ?>" class="btn btn-default btn-block _delete">
                <i class="fa fa-trash tw-mr-1"></i>Delete
              </a>
              <?php endif; ?>
            <?php else: ?>
            <p class="text-muted">
              <?php echo $overtime->status === 'approved'
                ? '<i class="fa fa-check-circle text-success tw-mr-1"></i>This request has been approved and will be included in the next payroll.'
                : '<i class="fa fa-times-circle text-danger tw-mr-1"></i>This request has been rejected.'; ?>
            </p>
            <?php endif; ?>

            <a href="<?php echo admin_url('hr_module/overtime'); ?>" class="btn btn-default btn-block tw-mt-2">
              <i class="fa fa-arrow-left tw-mr-1"></i>Back to List
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title"><?php echo _l('hr_overtime_reject'); ?></h4>
    </div>
    <?php echo form_open(admin_url('hr_module/overtime/reject/'.$overtime->id)); ?>
    <div class="modal-body">
      <div class="form-group">
        <label>Reason <span class="text-danger">*</span></label>
        <textarea name="rejection_reason" class="form-control" rows="3" required
                  placeholder="Reason for rejecting this overduty request..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-danger"><i class="fa fa-times tw-mr-1"></i>Reject</button>
    </div>
    <?php echo form_close(); ?>
  </div></div>
</div>
<?php init_tail(); ?>
