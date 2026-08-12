<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/** @var object      $policy            */
/** @var bool        $can_manage        */
/** @var bool        $is_admin_reviewer */
/** @var object|null $pending_revision  */
$p = $policy;
$status_badge = [
    'pending'   => 'label-warning',
    'published' => 'label-success',
    'rejected'  => 'label-danger',
];
$p_attachments = $this->Policies_model->decode_attachments($p->attachment);
$revision_attachments = $pending_revision ? $this->Policies_model->decode_attachments($pending_revision->attachment) : [];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/policies'); ?>">Policies</a></li>
          <li class="active"><?php echo htmlspecialchars($p->title); ?></li>
        </ol>
      </div>
    </div>

    <div class="row">
      <!-- Main -->
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-heading tw-flex tw-items-center tw-justify-between">
            <h5 class="tw-font-semibold tw-mb-0"><?php echo htmlspecialchars($p->title); ?></h5>
            <span class="label <?php echo $status_badge[$p->status] ?? 'label-default'; ?>"><?php echo ucfirst($p->status); ?></span>
          </div>
          <div class="panel-body">
            <table class="table table-condensed tw-mb-3">
              <tr><th style="width:35%">Visibility</th>
                <td><?php echo $p->type === 'public' ? 'Public - All Employees' : htmlspecialchars('Private - ' . ($p->department_names ?: '-')); ?></td></tr>
              <tr><th>Submitted By</th><td><?php echo htmlspecialchars($p->created_by_name ?: '-'); ?></td></tr>
              <?php if ($p->status === 'published'): ?>
              <tr><th>Published</th><td><?php echo _dt($p->published_at); ?> by <?php echo htmlspecialchars($p->approved_by_name ?: '-'); ?></td></tr>
              <?php endif; ?>
            </table>

            <?php if ($p->status === 'rejected' && $can_manage): ?>
            <div class="alert alert-danger">
              <strong>Rejected.</strong>
              <?php if ($p->rejection_reason): ?><?php echo nl2br(htmlspecialchars($p->rejection_reason)); ?><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($p->status === 'pending'): ?>
            <div class="alert alert-warning">This policy is awaiting admin approval and is not yet visible to employees.</div>
            <?php endif; ?>

            <?php if ($p->status === 'published' || $can_manage): ?>
            <hr>
            <?php foreach ($p_attachments as $a): ?>
            <a href="<?php echo admin_url('hr_module/policies/download/' . $p->id . '/' . $a['file']); ?>" target="_blank" class="btn btn-default tw-mb-3 tw-mr-2">
              <i class="fa fa-file-pdf tw-mr-1"></i><?php echo htmlspecialchars($a['name'] ?: $a['file']); ?>
            </a>
            <?php endforeach; ?>
            <?php if ($p->content && trim(strip_tags($p->content)) !== ''): ?>
            <div class="policy-content"><?php echo $p->content; ?></div>
            <?php endif; ?>
            <?php if (empty($p_attachments) && (!$p->content || trim(strip_tags($p->content)) === '')): ?>
            <p class="text-muted">No content added.</p>
            <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <?php // Only the manager who can submit/own this policy's updates, or the
              // approver who must review them, gets to see a pending update -
              // regular employees just see the current published version until
              // it's actually approved. ?>
        <?php if ($pending_revision && ($can_manage || $is_admin_reviewer)): ?>
        <div class="panel_s">
          <div class="panel-heading tw-flex tw-items-center tw-justify-between">
            <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-clock tw-mr-2 text-warning"></i>Pending Update</h5>
            <span class="label label-warning">Awaiting Approval</span>
          </div>
          <div class="panel-body">
            <p class="text-muted tw-text-sm">Submitted by <?php echo htmlspecialchars($pending_revision->submitted_by_name ?: '-'); ?> on <?php echo _dt($pending_revision->created_at); ?>. The version above stays visible to employees until this is approved.</p>
            <table class="table table-condensed tw-mb-3">
              <tr><th style="width:35%">Title</th><td><?php echo htmlspecialchars($pending_revision->title); ?></td></tr>
              <tr><th>Visibility</th>
                <td><?php echo $pending_revision->type === 'public' ? 'Public - All Employees' : htmlspecialchars('Private - ' . ($pending_revision->department_names ?: '-')); ?></td></tr>
            </table>
            <?php foreach ($revision_attachments as $a): ?>
            <a href="<?php echo admin_url('hr_module/policies/download/' . $p->id . '/' . $a['file']); ?>" target="_blank" class="btn btn-default btn-sm tw-mb-3 tw-mr-2">
              <i class="fa fa-file-pdf tw-mr-1"></i><?php echo htmlspecialchars($a['name'] ?: $a['file']); ?> (proposed)
            </a>
            <?php endforeach; ?>
            <?php if ($pending_revision->content && trim(strip_tags($pending_revision->content)) !== ''): ?>
            <div class="policy-content"><?php echo $pending_revision->content; ?></div>
            <?php endif; ?>

            <?php if ($is_admin_reviewer): ?>
            <hr>
            <form action="<?php echo admin_url('hr_module/policies/approve_revision/' . $pending_revision->id); ?>" method="post" class="tw-inline-block tw-mr-2">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <button type="submit" class="btn btn-success btn-sm"><i class="fa fa-check tw-mr-1"></i>Approve Update</button>
            </form>
            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#rejectRevisionModal">
              <i class="fa fa-times tw-mr-1"></i>Reject Update
            </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Actions -->
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h6 class="tw-font-semibold tw-mb-3">Actions</h6>

            <?php if ($is_admin_reviewer && $p->status === 'pending'): ?>
            <form action="<?php echo admin_url('hr_module/policies/approve/' . $p->id); ?>" method="post" class="tw-mb-3">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <button type="submit" class="btn btn-success btn-block btn-sm"><i class="fa fa-check tw-mr-1"></i>Approve &amp; Publish</button>
            </form>
            <form action="<?php echo admin_url('hr_module/policies/reject/' . $p->id); ?>" method="post" class="tw-mb-3">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <div class="form-group">
                <textarea name="reason" class="form-control input-sm" rows="2" placeholder="Rejection reason..."></textarea>
              </div>
              <button type="submit" class="btn btn-danger btn-block btn-sm"><i class="fa fa-times tw-mr-1"></i>Reject</button>
            </form>
            <?php endif; ?>

            <?php if ($can_manage && $p->status === 'published'): ?>
            <a href="<?php echo admin_url('hr_module/policies/edit/' . $p->id); ?>" class="btn btn-default btn-block btn-sm tw-mb-3">
              <i class="fa fa-pencil-alt tw-mr-1"></i>Submit Update
            </a>
            <?php endif; ?>

            <?php if ($can_manage): ?>
            <a href="<?php echo admin_url('hr_module/policies/delete/' . $p->id); ?>" class="btn btn-default btn-block btn-sm text-danger _delete">
              <i class="fa fa-trash tw-mr-1"></i>Delete
            </a>
            <?php endif; ?>

            <hr>
            <a href="<?php echo admin_url('hr_module/policies'); ?>" class="btn btn-default btn-block btn-sm">
              <i class="fa fa-arrow-left tw-mr-1"></i>Back
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($pending_revision && $is_admin_reviewer): ?>
<div class="modal fade" id="rejectRevisionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?php echo admin_url('hr_module/policies/reject_revision/' . $pending_revision->id); ?>" method="post">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Reject Update</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Reason</label>
            <textarea name="reason" class="form-control" rows="3" placeholder="Reason for rejecting this update..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Reject Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
<?php init_tail(); ?>
