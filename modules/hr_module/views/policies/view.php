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
          <li><a href="<?php echo admin_url('hr_module/policies'); ?>">Policy</a></li>
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
            <span>
              <span class="label <?php echo $status_badge[$p->status] ?? 'label-default'; ?>"><?php echo ucfirst($p->status); ?></span>
              <?php if ($p->status === 'published' && !$p->active): ?>
              <span class="label label-default tw-ml-1">Inactive</span>
              <?php endif; ?>
            </span>
          </div>
          <div class="panel-body">
            <table class="table table-condensed tw-mb-3">
              <tr><th style="width:35%">Visibility</th>
                <td><?php echo $p->type === 'public' ? 'Public - All Employees' : htmlspecialchars('Private - ' . ($p->department_names ?: '-')); ?></td></tr>
              <tr><th>Submitted By</th><td><?php echo htmlspecialchars($p->created_by_name ?: '-'); ?></td></tr>
              <tr><th>Submitted On</th><td><?php echo _dt($p->created_at); ?></td></tr>
              <tr><th>Effective Date</th><td>
                <?php echo $p->effective_date ? _d($p->effective_date) : '-'; ?>
                <?php if ($this->Policies_model->is_backdated($p->effective_date, $p->created_at)): ?>
                <span class="label label-warning tw-ml-1" title="This policy's effective date is earlier than the day it was submitted"><i class="fa fa-triangle-exclamation tw-mr-1"></i>Backdated</span>
                <?php endif; ?>
              </td></tr>
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
              <tr><th>Effective Date</th><td>
                <?php echo $pending_revision->effective_date ? _d($pending_revision->effective_date) : '-'; ?>
                <?php if ($this->Policies_model->is_backdated($pending_revision->effective_date, $pending_revision->created_at)): ?>
                <span class="label label-warning tw-ml-1" title="This update's effective date is earlier than the day it was submitted"><i class="fa fa-triangle-exclamation tw-mr-1"></i>Backdated</span>
                <?php endif; ?>
              </td></tr>
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

        <?php if (!empty($revision_history)): ?>
        <div class="panel_s">
          <div class="panel-heading">
            <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-history tw-mr-2 text-muted"></i>Policy History</h5>
          </div>
          <div class="panel-body">
            <p class="text-muted tw-text-sm">Past updates to this policy - what it used to say before each one was reviewed.</p>
            <?php foreach ($revision_history as $h): ?>
            <details class="tw-mb-2" style="border:1px solid #eee;border-radius:4px;padding:8px 12px">
              <summary style="cursor:pointer">
                <span class="label <?php echo $h->status === 'approved' ? 'label-success' : 'label-danger'; ?>"><?php echo ucfirst($h->status); ?></span>
                <?php echo htmlspecialchars($h->title); ?>
                <span class="text-muted tw-text-sm">
                  - submitted by <?php echo htmlspecialchars($h->submitted_by_name ?: '-'); ?> on <?php echo _dt($h->created_at); ?>,
                  reviewed by <?php echo htmlspecialchars($h->reviewed_by_name ?: '-'); ?> on <?php echo $h->reviewed_at ? _dt($h->reviewed_at) : '-'; ?>
                </span>
              </summary>
              <div class="tw-mt-2">
                <table class="table table-condensed tw-mb-2">
                  <tr><th style="width:35%">Visibility</th>
                    <td><?php echo $h->type === 'public' ? 'Public - All Employees' : htmlspecialchars('Private - ' . ($h->department_names ?: '-')); ?></td></tr>
                  <?php if ($h->status === 'rejected' && $h->rejection_reason): ?>
                  <tr><th>Rejection Reason</th><td><?php echo nl2br(htmlspecialchars($h->rejection_reason)); ?></td></tr>
                  <?php endif; ?>
                </table>
                <?php foreach ($this->Policies_model->decode_attachments($h->attachment) as $a): ?>
                <a href="<?php echo admin_url('hr_module/policies/download/' . $p->id . '/' . $a['file']); ?>" target="_blank" class="btn btn-default btn-sm tw-mb-2 tw-mr-2">
                  <i class="fa fa-file-pdf tw-mr-1"></i><?php echo htmlspecialchars($a['name'] ?: $a['file']); ?>
                </a>
                <?php endforeach; ?>
                <?php if ($h->content && trim(strip_tags($h->content)) !== ''): ?>
                <div class="policy-content"><?php echo $h->content; ?></div>
                <?php endif; ?>
              </div>
            </details>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Actions -->
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h6 class="tw-font-semibold tw-mb-3">Actions</h6>

            <?php $show_pending_actions = $is_admin_reviewer && $p->status === 'pending'; ?>
            <?php if ($show_pending_actions): ?>
            <div class="form-group">
              <textarea id="policy-reject-note" class="form-control input-sm" rows="2" placeholder="Rejection reason (used only if you click Reject)..."></textarea>
            </div>
            <div class="row tw-mb-3">
              <div class="col-xs-4">
                <form action="<?php echo admin_url('hr_module/policies/approve/' . $p->id); ?>" method="post">
                  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <button type="submit" class="btn btn-success btn-block btn-sm"><i class="fa fa-check tw-mr-1"></i>Approve</button>
                </form>
              </div>
              <div class="col-xs-4">
                <form action="<?php echo admin_url('hr_module/policies/reject/' . $p->id); ?>" method="post" onsubmit="hrPolicyCopyRejectNote(this)">
                  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <input type="hidden" name="reason">
                  <button type="submit" class="btn btn-danger btn-block btn-sm"><i class="fa fa-times tw-mr-1"></i>Reject</button>
                </form>
              </div>
              <div class="col-xs-4">
                <?php if ($can_manage): ?>
                <a href="<?php echo admin_url('hr_module/policies/delete/' . $p->id); ?>" class="btn btn-default btn-block btn-sm text-danger _delete">
                  <i class="fa fa-trash tw-mr-1"></i>Delete
                </a>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

            <?php if ($can_manage && $p->status === 'published'): ?>
            <div class="row tw-mb-3">
              <div class="col-xs-4">
                <a href="<?php echo admin_url('hr_module/policies/edit/' . $p->id); ?>" class="btn btn-default btn-block btn-sm">
                  <i class="fa fa-pencil-alt tw-mr-1"></i>Update
                </a>
              </div>
              <div class="col-xs-4">
                <form action="<?php echo admin_url('hr_module/policies/toggle_active/' . $p->id); ?>" method="post">
                  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                  <button type="submit" class="btn btn-default btn-block btn-sm">
                    <?php if ($p->active): ?>
                    <i class="fa fa-ban tw-mr-1"></i>Deactivate
                    <?php else: ?>
                    <i class="fa fa-check tw-mr-1"></i>Activate
                    <?php endif; ?>
                  </button>
                </form>
              </div>
              <div class="col-xs-4">
                <a href="<?php echo admin_url('hr_module/policies/delete/' . $p->id); ?>" class="btn btn-default btn-block btn-sm text-danger _delete">
                  <i class="fa fa-trash tw-mr-1"></i>Delete
                </a>
              </div>
            </div>
            <?php endif; ?>

            <?php if ($can_manage && !$show_pending_actions && $p->status !== 'published'): ?>
            <a href="<?php echo admin_url('hr_module/policies/delete/' . $p->id); ?>" class="btn btn-default btn-block btn-sm text-danger _delete">
              <i class="fa fa-trash tw-mr-1"></i>Delete
            </a>
            <?php endif; ?>
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
<style>
/* Same web font loaded for the editor's own iframe in policies/form.php -
   without this, content saved using the "Bangla" font choice there has
   nothing to actually render with on this page and silently falls back to
   a generic substitute instead. */
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali&display=swap');
/* $p->content/$pending_revision->content is TinyMCE-authored HTML (see
   policies/form.php) rendered verbatim - without this, headings/lists/
   spacing all collapse to plain unstyled text, losing whatever structure
   was actually designed in the editor. Scoped to .policy-content only. */
/* Applied as the default (not just an opt-in dropdown choice) so Bangla text
   renders in this font even when nobody explicitly picked "Bangla" from the
   editor's font menu while typing - a browser only uses this for characters
   Noto Sans Bengali actually has glyphs for (Bengali script), and silently
   falls through to Arial for everything else (English words mixed into the
   same policy), so this is safe for mixed-language content either way. */
/* !important throughout this block is deliberate: the admin theme resets
   list/text styling fairly aggressively for its own UI (nav menus, panels,
   etc.), and since .policy-content is only ever used here to render
   editor-authored content verbatim, it should always win over that reset
   rather than the two silently fighting for which one applies. */
.policy-content { line-height: 1.7; font-family: 'Noto Sans Bengali', Arial, sans-serif; }
.policy-content p { margin-bottom: 12px !important; }
.policy-content h1, .policy-content h2, .policy-content h3,
.policy-content h4, .policy-content h5, .policy-content h6 {
    font-weight: 600 !important; margin-top: 20px !important; margin-bottom: 10px !important;
}
.policy-content h1 { font-size: 28px !important; }
.policy-content h2 { font-size: 24px !important; }
.policy-content h3 { font-size: 20px !important; }
.policy-content h4 { font-size: 16px !important; }
.policy-content h5 { font-size: 14px !important; }
.policy-content h6 { font-size: 13px !important; }
.policy-content strong, .policy-content b { font-weight: 700 !important; }
.policy-content em, .policy-content i { font-style: italic !important; }
.policy-content u { text-decoration: underline !important; }
.policy-content s, .policy-content strike { text-decoration: line-through !important; }
.policy-content sub { vertical-align: sub !important; font-size: smaller !important; }
.policy-content sup { vertical-align: super !important; font-size: smaller !important; }
.policy-content a { color: #2b7de9 !important; text-decoration: underline !important; }
.policy-content ul { list-style-type: disc !important; margin: 0 0 12px !important; padding-left: 28px !important; }
.policy-content ol { list-style-type: decimal !important; margin: 0 0 12px !important; padding-left: 28px !important; }
.policy-content ul ul { list-style-type: circle !important; }
.policy-content li { display: list-item !important; margin-bottom: 4px !important; }
.policy-content blockquote {
    border-left: 3px solid #ddd !important; margin: 0 0 12px !important; padding-left: 15px !important; color: #666 !important;
}
.policy-content hr { border: 0 !important; border-top: 1px solid #ddd !important; margin: 20px 0 !important; }
.policy-content table { border-collapse: collapse !important; margin-bottom: 12px !important; width: auto !important; }
.policy-content table td, .policy-content table th { border: 1px solid #ddd !important; padding: 6px 10px !important; }
.policy-content img { max-width: 100% !important; height: auto !important; }
.policy-content pre {
    background: #f5f5f5 !important; border: 1px solid #ddd !important; border-radius: 4px !important;
    padding: 10px 12px !important; overflow-x: auto !important; white-space: pre-wrap !important;
    font-family: Consolas, Monaco, monospace !important; margin-bottom: 12px !important;
}
.policy-content code { background: #f5f5f5 !important; padding: 1px 5px !important; border-radius: 3px !important; font-family: Consolas, Monaco, monospace !important; }
.policy-content pre code { background: none !important; padding: 0 !important; }
</style>
<script>
// Copies the shared rejection-reason textarea into the Reject form's hidden
// field right before it submits - one visible textarea instead of one per
// action, mirroring the same pattern already used on leave/view.php.
function hrPolicyCopyRejectNote(form) {
    $(form).find('input[name="reason"]').val($('#policy-reject-note').val());
}
</script>
