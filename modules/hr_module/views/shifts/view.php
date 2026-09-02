<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object $assignment  */
/** @var bool   $can_approve */
$a = $assignment;
$badge_map = ['pending' => 'label-warning', 'approved' => 'label-success', 'rejected' => 'label-danger'];
$badge = '<span class="label ' . ($badge_map[$a->status] ?? 'label-default') . '">' . ucfirst($a->status) . '</span>';
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/shifts'); ?>"><?php echo _l('hr_shift_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_shift_view'); ?> #<?php echo $a->id; ?></li>
        </ol>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
              <div>
                <h5 class="tw-font-bold"><?php echo htmlspecialchars($a->shift_name); ?> &mdash; <?php echo htmlspecialchars($a->employee_name); ?></h5>
                <p class="text-muted tw-text-sm"><?php echo _l('hr_employee_code'); ?>: <?php echo htmlspecialchars($a->employee_code); ?></p>
              </div>
              <?php echo $badge; ?>
            </div>

            <table class="table table-condensed">
              <tr><th style="width:35%"><?php echo _l('hr_department'); ?></th><td><?php echo htmlspecialchars($a->department_name ?: '-'); ?></td></tr>
              <tr><th><?php echo _l('hr_designation'); ?></th><td><?php echo htmlspecialchars($a->designation_name ?: '-'); ?></td></tr>
              <tr><th><?php echo _l('hr_shift_type'); ?></th>
                <td><?php echo htmlspecialchars($a->shift_name); ?>
                  <span class="text-muted">(<?php echo date('g:i A', strtotime($a->start_time)) . ' - ' . date('g:i A', strtotime($a->end_time)); ?>)</span>
                </td></tr>
              <tr><th><?php echo _l('hr_shift_date_range'); ?></th>
                <td><?php echo _d($a->from_date) . ($a->to_date !== $a->from_date ? ' &mdash; ' . _d($a->to_date) : ''); ?></td></tr>
              <tr><th><?php echo _l('hr_shift_reason'); ?></th><td><?php echo nl2br(htmlspecialchars($a->reason ?: '-')); ?></td></tr>
              <?php if ($a->status === 'rejected' && $a->rejection_reason): ?>
              <tr><th><?php echo _l('hr_remarks'); ?></th><td><?php echo nl2br(htmlspecialchars($a->rejection_reason)); ?></td></tr>
              <?php endif; ?>
              <tr><th><?php echo _l('hr_created_at'); ?></th><td><?php echo _dt($a->created_at); ?> by <?php echo htmlspecialchars($a->created_by_name ?: '-'); ?></td></tr>
              <?php if (!empty($a->soft_approved_by)): ?>
              <tr><th>Soft <?php echo ucfirst($a->soft_status); ?> By</th><td><?php echo htmlspecialchars($a->soft_approved_by_name ?: '-'); ?> &mdash; <?php echo _dt($a->soft_approved_at); ?></td></tr>
              <?php endif; ?>
              <?php if ($a->approved_by): ?>
              <tr><th><?php echo ucfirst($a->status); ?> By</th><td><?php echo htmlspecialchars($a->approved_by_name ?: '-'); ?> &mdash; <?php echo _dt($a->approved_at); ?></td></tr>
              <?php endif; ?>
            </table>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <?php ob_start(); ?>

            <?php if (!empty($a->soft_approved_by)): ?>
            <div class="alert alert-info tw-py-2 tw-mb-3 tw-text-sm">
              Soft <?php echo ucfirst($a->soft_status); ?> by <strong><?php echo htmlspecialchars($a->soft_approved_by_name ?: '-'); ?></strong> &mdash; <?php echo _dt($a->soft_approved_at); ?>
            </div>
            <?php endif; ?>

            <?php if ($can_approve && $a->status === 'pending'): ?>
            <!-- Single shared note/reason field - copied into the Reject form's
                 hidden "reason" field just before it submits, so there's one
                 visible textarea instead of one per action (mirrors leave/view.php). -->
            <div class="form-group">
              <textarea id="shift-action-note" class="form-control" rows="3" placeholder="Notes / reason (optional)..."></textarea>
            </div>
            <?php endif; ?>

            <?php if ($can_approve && $a->status === 'pending'): ?>
            <!-- Approve / Reject: the real, final decision -->
            <div class="row tw-mb-2">
              <div class="col-xs-6">
                <form action="<?php echo admin_url('hr_module/shifts/approve/' . $a->id); ?>" method="post">
                  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <button type="submit" class="btn btn-success btn-block">
                    <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_shift_approve'); ?>
                  </button>
                </form>
              </div>
              <div class="col-xs-6">
                <form action="<?php echo admin_url('hr_module/shifts/reject/' . $a->id); ?>" method="post" onsubmit="hrCopyShiftNote(this)">
                  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <input type="hidden" name="reason">
                  <button type="submit" class="btn btn-danger btn-block">
                    <i class="fa fa-times tw-mr-1"></i><?php echo _l('hr_shift_reject'); ?>
                  </button>
                </form>
              </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($can_soft_approve) && $a->status === 'pending' && empty($a->soft_approved_by)): ?>
            <!-- Soft Approve/Reject: informational-only pre-approval, never blocks the real Approve/Reject above -->
            <div class="row tw-mb-2">
              <div class="col-xs-6">
                <form action="<?php echo admin_url('hr_module/shifts/soft_approve/' . $a->id); ?>" method="post">
                  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <button type="submit" class="btn btn-success btn-block">
                    <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_shift_soft_approve'); ?>
                  </button>
                </form>
              </div>
              <div class="col-xs-6">
                <form action="<?php echo admin_url('hr_module/shifts/soft_reject/' . $a->id); ?>" method="post">
                  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <button type="submit" class="btn btn-danger btn-block">
                    <i class="fa fa-times tw-mr-1"></i><?php echo _l('hr_shift_soft_reject'); ?>
                  </button>
                </form>
              </div>
            </div>
            <?php endif; ?>

            <?php if ($a->status === 'pending' && !empty($can_delete)): ?>
            <a href="<?php echo admin_url('hr_module/shifts/delete/' . $a->id); ?>" class="btn btn-danger btn-block _delete">
              <i class="fa fa-trash tw-mr-1"></i><?php echo _l('hr_delete'); ?>
            </a>
            <?php endif; ?>
      <?php $shift_actions_body = ob_get_clean(); ?>
      <?php if (trim($shift_actions_body) !== ''): ?>
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h6 class="tw-font-semibold tw-mb-3"><?php echo _l('hr_actions'); ?></h6>
            <?php echo $shift_actions_body; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
function hrCopyShiftNote(form) {
    var note = document.getElementById('shift-action-note');
    var target = form.querySelector('input[type="hidden"][name="reason"]');
    if (note && target) target.value = note.value;
}
</script>
