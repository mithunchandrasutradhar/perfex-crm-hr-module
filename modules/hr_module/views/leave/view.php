<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object      $request */
/** @var object|null $balance */
if (!isset($request)) $request = (object)['id'=>0,'status'=>'pending','leave_type_name'=>'','employee_name'=>'','employee_code'=>'','from_date'=>null,'to_date'=>null,'total_days'=>0,'is_half_day'=>0,'reason'=>'','rejection_reason'=>null,'attachment'=>null,'created_at'=>null,'approved_by'=>null,'approved_by_name'=>'','approved_at'=>null];
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
              <tr><th style="width:35%"><?php echo _l('hr_from_date'); ?></th>
                <td><?php echo ($r->from_date && $r->from_date !== '0000-00-00') ? _d($r->from_date) : '<span class="text-muted">—</span>'; ?></td></tr>
              <tr><th><?php echo _l('hr_to_date'); ?></th>
                <td><?php echo ($r->to_date && $r->to_date !== '0000-00-00') ? _d($r->to_date) : '<span class="text-muted">—</span>'; ?></td></tr>
              <tr><th><?php echo _l('hr_leave_days'); ?></th>
                <td><?php echo $r->total_days; ?> <?php if($r->is_half_day) echo '<span class="label label-info">Half Day</span>'; ?></td></tr>
              <tr><th><?php echo _l('hr_leave_reason'); ?></th><td><?php echo nl2br(htmlspecialchars($r->reason ?? '-')); ?></td></tr>
              <?php if ($r->rejection_reason): ?>
              <tr><th><?php echo _l('hr_remarks'); ?></th><td><?php echo nl2br(htmlspecialchars($r->rejection_reason)); ?></td></tr>
              <?php endif; ?>
              <?php if ($r->attachment): ?>
              <tr><th><?php echo _l('hr_attachments'); ?></th>
                <td><a href="<?php echo base_url('uploads/hr_module/leaves/' . $r->attachment); ?>" target="_blank"><i class="fa fa-paperclip"></i> View Attachment</a></td></tr>
              <?php endif; ?>
              <tr><th><?php echo _l('hr_created_at'); ?></th><td><?php echo _dt($r->created_at); ?></td></tr>
              <?php if ($r->approved_by): ?>
              <tr><th><?php echo ($r->status == 'approved' ? _l('hr_leave_approve') : _l('hr_leave_reject')) . 'd by'; ?></th>
                <td><?php echo htmlspecialchars($r->approved_by_name); ?> &mdash; <?php echo _dt($r->approved_at); ?></td></tr>
              <?php endif; ?>
            </table>

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
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h6 class="tw-font-semibold tw-mb-3"><?php echo _l('hr_actions'); ?></h6>

            <?php if ($r->status === 'pending' && (staff_can('approve', 'hr_leave') || is_admin())): ?>
            <!-- Approve -->
            <form action="<?php echo admin_url('hr_module/leave/approve/' . $r->id); ?>" method="post" class="tw-mb-3">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <div class="form-group">
                <textarea name="notes" class="form-control input-sm" rows="2" placeholder="Optional notes..."></textarea>
              </div>
              <button type="submit" class="btn btn-success btn-block btn-sm">
                <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_leave_approve'); ?>
              </button>
            </form>
            <!-- Reject -->
            <form action="<?php echo admin_url('hr_module/leave/reject/' . $r->id); ?>" method="post" class="tw-mb-3">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <div class="form-group">
                <textarea name="reason" class="form-control input-sm" rows="2" placeholder="Rejection reason..."></textarea>
              </div>
              <button type="submit" class="btn btn-danger btn-block btn-sm">
                <i class="fa fa-times tw-mr-1"></i><?php echo _l('hr_leave_reject'); ?>
              </button>
            </form>
            <?php endif; ?>

            <?php if (in_array($r->status, ['pending', 'approved'])): ?>
            <a href="<?php echo admin_url('hr_module/leave/cancel/' . $r->id); ?>"
               onclick="return confirm('Cancel this leave request?')"
               class="btn btn-warning btn-block btn-sm">
              <i class="fa fa-ban tw-mr-1"></i><?php echo _l('hr_leave_cancel'); ?>
            </a>
            <?php endif; ?>

            <hr>
            <a href="<?php echo admin_url('hr_module/leave'); ?>" class="btn btn-default btn-block btn-sm">
              <i class="fa fa-arrow-left tw-mr-1"></i><?php echo _l('hr_back'); ?>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
