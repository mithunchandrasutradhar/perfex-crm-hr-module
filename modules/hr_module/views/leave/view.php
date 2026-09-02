<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object      $request */
/** @var array       $days    */
/** @var object|null $balance */
if (!isset($request)) $request = (object)['id'=>0,'status'=>'pending','leave_type_name'=>'','employee_name'=>'','employee_code'=>'','from_date'=>null,'to_date'=>null,'total_days'=>0,'is_half_day'=>0,'reason'=>'','rejection_reason'=>null,'attachment'=>null,'created_at'=>null,'approved_by'=>null,'approved_by_name'=>'','approved_at'=>null,'cancellation_status'=>null,'cancellation_reason'=>null,'cancellation_requested_at'=>null,'soft_status'=>null,'soft_approved_by'=>null,'soft_approved_by_name'=>'','soft_approved_at'=>null];
if (!isset($days)) $days = [];
if (!isset($balance)) $balance = null;
$r = $request;
$badge_map = ['pending'=>'label-warning','approved'=>'label-success','rejected'=>'label-danger','cancelled'=>'label-default'];
$badge = '<span class="label ' . ($badge_map[$r->status] ?? 'label-default') . ' label-tag">' . ucfirst($r->status) . '</span>';
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/leave'); ?>"><?php echo _l('hr_leave_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_leave_view'); ?> #<?php echo $r->id; ?></li>
        </ol>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
              <div>
                <h5 class="tw-font-bold"><?php echo htmlspecialchars($r->leave_type_name); ?> &mdash; <?php echo htmlspecialchars($r->employee_name); ?></h5>
                <p class="text-muted tw-text-sm"><?php echo _l('hr_employee_code'); ?>: <?php echo $r->employee_code; ?></p>
              </div>
              <?php echo $badge; ?>
            </div>

            <table class="table table-condensed">
              <tr><th style="width:35%"><?php echo _l('hr_from_date'); ?> — <?php echo _l('hr_to_date'); ?></th>
                <td>
                  <?php if ($r->from_date && $r->from_date !== '0000-00-00'): ?>
                    <?php echo _d($r->from_date); ?><?php if ($r->to_date && $r->to_date !== $r->from_date): ?> — <?php echo _d($r->to_date); ?><?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td></tr>
              <tr><th><?php echo _l('hr_leave_days'); ?></th>
                <td>
                  <strong><?php echo $r->total_days; ?></strong>
                  <?php if (count($days) === 1): ?>
                  <span class="text-muted">(<?php echo htmlspecialchars(hr_leave_day_type_label($days[0]->day_type)); ?>)</span>
                  <?php endif; ?>
                </td></tr>
              <tr><th><?php echo _l('hr_leave_reason'); ?></th><td><?php echo nl2br(htmlspecialchars($r->reason ?? '-')); ?></td></tr>
              <?php if ($r->rejection_reason): ?>
              <tr><th><?php echo _l('hr_remarks'); ?></th><td><?php echo nl2br(htmlspecialchars($r->rejection_reason)); ?></td></tr>
              <?php endif; ?>
              <?php if ($r->attachment): ?>
              <tr><th><?php echo _l('hr_attachments'); ?></th>
                <td><a href="<?php echo admin_url('hr_module/leave/download/' . $r->id); ?>" target="_blank"><i class="fa fa-paperclip"></i> View Attachment</a></td></tr>
              <?php endif; ?>
              <tr><th><?php echo _l('hr_created_at'); ?></th><td><?php echo _dt($r->created_at); ?></td></tr>
              <?php if (!empty($r->soft_approved_by)): ?>
              <tr><th><?php echo ($r->soft_status == 'approved' ? _l('hr_leave_soft_approve') : _l('hr_leave_soft_reject')) . 'd by'; ?></th>
                <td><?php echo htmlspecialchars($r->soft_approved_by_name); ?> &mdash; <?php echo _dt($r->soft_approved_at); ?></td></tr>
              <?php endif; ?>
              <?php if ($r->approved_by): ?>
              <tr><th><?php echo ($r->status == 'approved' ? _l('hr_leave_approve') : _l('hr_leave_reject')) . 'd by'; ?></th>
                <td><?php echo htmlspecialchars($r->approved_by_name); ?> &mdash; <?php echo _dt($r->approved_at); ?></td></tr>
              <?php endif; ?>
            </table>

            <?php if (!empty($days) && count($days) <= 31): ?>
            <h6 class="tw-font-semibold tw-mb-2 tw-mt-4"><?php echo _l('hr_leave_days_breakdown'); ?></h6>
            <table class="table table-condensed table-bordered">
              <thead>
                <tr>
                  <th><?php echo _l('hr_date'); ?></th>
                  <th><?php echo _l('hr_type'); ?></th>
                  <th><?php echo _l('hr_leave_value_days'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($days as $d): ?>
                <tr <?php if ($d->day_type === 'bridge') echo 'class="tw-bg-neutral-50"'; ?>>
                  <td><?php echo _d($d->leave_date); ?></td>
                  <td>
                    <?php echo htmlspecialchars(hr_leave_day_type_label($d->day_type)); ?>
                    <?php if ($d->day_type === 'hourly' && $d->hour_start && $d->hour_end): ?>
                    <span class="text-muted tw-text-sm">(<?php echo substr($d->hour_start, 0, 5); ?> — <?php echo substr($d->hour_end, 0, 5); ?>)</span>
                    <?php endif; ?>
                    <?php if (!empty($d->note)): ?>
                    <br><span class="text-muted tw-text-sm"><i class="fa fa-info-circle tw-mr-1"></i><?php echo htmlspecialchars($d->note); ?><?php if ($d->day_type === 'bridge') echo ' — ' . _l('hr_leave_bridge_hint'); ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo $d->day_value; ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php endif; ?>

            <?php if ($balance): ?>
            <div class="alert alert-info tw-mt-2">
              <strong><?php echo _l('hr_leave_balance'); ?>:</strong>
              <?php echo _l('hr_leave_allocated'); ?>: <?php echo $balance->allocated_days + $balance->carry_forward_days; ?> |
              <?php echo _l('hr_leave_used'); ?>: <?php echo $balance->used_days; ?> |
              <?php echo _l('hr_leave_remaining'); ?>: <strong><?php echo ($balance->allocated_days + $balance->carry_forward_days - $balance->used_days); ?></strong>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Actions sidebar -->
      <?php
        $show_soft    = $r->status === 'pending' && staff_can('soft_approve', 'hr_leave') && empty($r->soft_approved_by);
        $show_approve = $r->status === 'pending' && (staff_can('approve', 'hr_leave') || is_admin());
        // Matches Leave::cancel()'s own authorization exactly: full 'view',
        // or 'view_own' limited to the requester's own record - a
        // view_department-tier viewer could see this page but was never
        // actually allowed to cancel, so the button shouldn't show for them.
        $show_cancel  = $r->status === 'pending' && (is_admin() || staff_can('view', 'hr_leave')
            || (staff_can('view_own', 'hr_leave') && (int) $r->employee_id === hr_get_own_employee_id()));
        // The panel spans several status-dependent branches (pending, approved
        // with/without a cancellation request pending) - buffering the body is
        // simpler and less error-prone than re-deriving every branch's own
        // "is there anything to show" condition up front.
        ob_start();
      ?>

            <?php if (!empty($r->soft_approved_by)): ?>
            <div class="alert alert-info tw-py-2 tw-mb-3 tw-text-sm">
              <?php echo ($r->soft_status == 'approved' ? _l('hr_leave_soft_approve') : _l('hr_leave_soft_reject')) . 'd by'; ?>
              <strong><?php echo htmlspecialchars($r->soft_approved_by_name); ?></strong> &mdash; <?php echo _dt($r->soft_approved_at); ?>
            </div>
            <?php endif; ?>

            <?php if ($show_approve || $show_cancel): ?>
            <!-- Single shared note/reason field - copied into whichever form
                 below actually gets submitted (Approve uses it as "notes",
                 Reject/Cancel use it as "reason"), so there's one visible
                 textarea instead of one per action. -->
            <div class="form-group">
              <textarea id="leave-action-note" class="form-control" rows="3" placeholder="Notes / reason (optional)..."></textarea>
            </div>
            <?php endif; ?>

            <?php if ($show_approve): ?>
            <!-- Approve / Reject: the real, final decision -->
            <div class="row tw-mb-2">
              <div class="col-xs-6">
                <form action="<?php echo admin_url('hr_module/leave/approve/' . $r->id); ?>" method="post" onsubmit="hrCopyLeaveNote(this)">
                  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <input type="hidden" name="notes">
                  <button type="submit" class="btn btn-success btn-block">
                    <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_leave_approve'); ?>
                  </button>
                </form>
              </div>
              <div class="col-xs-6">
                <form action="<?php echo admin_url('hr_module/leave/reject/' . $r->id); ?>" method="post" onsubmit="hrCopyLeaveNote(this)">
                  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <input type="hidden" name="reason">
                  <button type="submit" class="btn btn-danger btn-block">
                    <i class="fa fa-times tw-mr-1"></i><?php echo _l('hr_leave_reject'); ?>
                  </button>
                </form>
              </div>
            </div>
            <?php endif; ?>

            <?php if ($show_soft): ?>
            <!-- Soft Approve / Soft Reject: informational-only pre-approval, never blocks the real Approve/Reject above -->
            <div class="row tw-mb-2">
              <div class="col-xs-6">
                <form action="<?php echo admin_url('hr_module/leave/soft_approve/' . $r->id); ?>" method="post">
                  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <button type="submit" class="btn btn-success btn-block">
                    <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_leave_soft_approve'); ?>
                  </button>
                </form>
              </div>
              <div class="col-xs-6">
                <form action="<?php echo admin_url('hr_module/leave/soft_reject/' . $r->id); ?>" method="post">
                  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <button type="submit" class="btn btn-danger btn-block">
                    <i class="fa fa-times tw-mr-1"></i><?php echo _l('hr_leave_soft_reject'); ?>
                  </button>
                </form>
              </div>
            </div>
            <?php endif; ?>

            <?php if ($show_cancel): ?>
            <!-- Cancel Leave: a withdrawal, separate from the approve/reject decision -->
            <form action="<?php echo admin_url('hr_module/leave/cancel/' . $r->id); ?>" method="post" class="tw-mb-2"
              onsubmit="hrCopyLeaveNote(this); return confirm('Cancel this leave request?')">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <input type="hidden" name="reason">
              <button type="submit" class="btn btn-warning btn-block">
                <i class="fa fa-ban tw-mr-1"></i><?php echo _l('hr_leave_cancel'); ?>
              </button>
            </form>
            <?php endif; ?>

            <?php if ($show_soft || $show_approve || $show_cancel): ?>
            <hr>
            <?php endif; ?>

            <?php if ($r->status === 'approved' && $r->cancellation_status === 'pending'): ?>
            <div class="alert alert-warning tw-mb-3">
              <strong><?php echo _l('hr_leave_cancellation_pending'); ?></strong>
              <?php if ($r->cancellation_reason): ?>
              <div class="tw-text-sm tw-mt-1"><?php echo nl2br(htmlspecialchars($r->cancellation_reason)); ?></div>
              <?php endif; ?>
            </div>
            <?php if (staff_can('approve', 'hr_leave') || is_admin()): ?>
            <form action="<?php echo admin_url('hr_module/leave/approve_cancellation/' . $r->id); ?>" method="post" class="tw-mb-3">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <button type="submit" class="btn btn-success btn-block btn-sm">
                <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_leave_approve_cancellation'); ?>
              </button>
            </form>
            <form action="<?php echo admin_url('hr_module/leave/reject_cancellation/' . $r->id); ?>" method="post" class="tw-mb-3">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <button type="submit" class="btn btn-danger btn-block btn-sm">
                <i class="fa fa-times tw-mr-1"></i><?php echo _l('hr_leave_reject_cancellation'); ?>
              </button>
            </form>
            <?php endif; ?>
            <?php elseif ($r->status === 'approved'): ?>
            <form action="<?php echo admin_url('hr_module/leave/request_cancellation/' . $r->id); ?>" method="post" class="tw-mb-3"
              onsubmit="return confirm('Request cancellation of this leave?')">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <div class="form-group">
                <label><?php echo _l('hr_leave_cancellation_reason'); ?></label>
                <textarea name="reason" class="form-control input-sm" rows="2" placeholder="Why are you requesting this cancellation?" required></textarea>
              </div>
              <button type="submit" class="btn btn-warning btn-block btn-sm">
                <i class="fa fa-ban tw-mr-1"></i><?php echo _l('hr_leave_request_cancellation'); ?>
              </button>
            </form>
            <?php endif; ?>
      <?php
        $actions_body = ob_get_clean();
      ?>
      <?php if (trim($actions_body) !== ''): ?>
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h6 class="tw-font-semibold tw-mb-3"><?php echo _l('hr_actions'); ?></h6>
            <?php echo $actions_body; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
function hrCopyLeaveNote(form) {
    var note = document.getElementById('leave-action-note');
    var target = form.querySelector('input[type="hidden"][name="notes"], input[type="hidden"][name="reason"]');
    if (note && target) target.value = note.value;
}
</script>
