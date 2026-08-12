<?php defined('BASEPATH') or exit('No direct script access allowed');
$sbadge  = ['open'=>'danger','in_progress'=>'warning','resolved'=>'info','closed'=>'default'];
$pbadge  = ['low'=>'default','medium'=>'warning','high'=>'danger'];
$is_closed = in_array($ticket->status, ['closed','resolved']);
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/helpdesk'); ?>"><?php echo _l('hr_helpdesk_list'); ?></a></li>
          <li class="active">#<?php echo $ticket->id; ?> <?php echo htmlspecialchars($ticket->subject); ?></li>
        </ol>
      </div>
    </div>

    <div class="row">
      <!-- Thread -->
      <div class="col-md-8">

        <!-- Original ticket -->
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
              <div>
                <h4 class="tw-font-bold tw-mb-1"><?php echo htmlspecialchars($ticket->subject); ?></h4>
                <div class="tw-flex tw-gap-2 tw-flex-wrap">
                  <span class="label label-<?php echo $sbadge[$ticket->status] ?? 'default'; ?>">
                    <?php echo ucfirst(str_replace('_',' ',$ticket->status)); ?>
                  </span>
                  <span class="label label-<?php echo $pbadge[$ticket->priority] ?? 'default'; ?>">
                    <?php echo ucfirst($ticket->priority); ?> Priority
                  </span>
                  <?php if ($ticket->category): ?>
                  <span class="label label-default"><?php echo htmlspecialchars($ticket->category); ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <small class="text-muted"><?php echo date('d M Y H:i', strtotime($ticket->created_at)); ?></small>
            </div>

            <div class="tw-flex tw-items-center tw-gap-3 tw-mb-3">
              <?php if ($ticket->is_anonymous): ?>
              <div style="width:36px;height:36px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b">
                <i class="fa fa-user-secret"></i>
              </div>
              <div>
                <strong class="text-muted"><?php echo _l('hr_helpdesk_anonymous'); ?></strong>
              </div>
              <?php else: ?>
              <div style="width:36px;height:36px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:700;color:#64748b">
                <?php echo strtoupper(substr($ticket->first_name,0,1)); ?>
              </div>
              <div>
                <strong><?php echo htmlspecialchars($ticket->first_name.' '.$ticket->last_name); ?></strong>
                <span class="label label-default tw-ml-1" style="font-size:0.65rem"><?php echo $ticket->employee_code; ?></span>
                <br><small class="text-muted"><?php echo htmlspecialchars($ticket->department_name ?? ''); ?></small>
              </div>
              <?php endif; ?>
            </div>

            <div style="background:#f8fafc;border-left:3px solid #6366f1;padding:12px 16px;border-radius:0 6px 6px 0">
              <?php echo nl2br(htmlspecialchars($ticket->message)); ?>
            </div>

            <?php if ($ticket->attachment): ?>
            <div class="tw-mt-3">
              <a href="<?php echo admin_url('hr_module/helpdesk/download/'.$ticket->id.'/ticket'); ?>" target="_blank" class="btn btn-default btn-xs">
                <i class="fa fa-paperclip tw-mr-1"></i>Attachment
              </a>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Replies -->
        <?php foreach ($replies as $reply): ?>
        <div class="panel_s" style="margin-left:16px;border-left:3px solid #6366f1">
          <div class="panel-body">
            <div class="tw-flex tw-items-center tw-gap-3 tw-mb-3">
              <div style="width:32px;height:32px;border-radius:50%;background:#6366f1;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:0.75rem">
                <?php echo strtoupper(substr($reply->staff_name,0,1)); ?>
              </div>
              <div>
                <strong><?php echo htmlspecialchars($reply->staff_name); ?></strong>
                <span class="label label-info tw-ml-1" style="font-size:0.65rem">Staff</span>
                <br><small class="text-muted"><?php echo date('d M Y H:i', strtotime($reply->created_at)); ?></small>
              </div>
            </div>
            <p class="tw-mb-0"><?php echo nl2br(htmlspecialchars($reply->message)); ?></p>
            <?php if ($reply->attachment): ?>
            <div class="tw-mt-2">
              <a href="<?php echo admin_url('hr_module/helpdesk/download/'.$ticket->id.'/reply/'.$reply->id); ?>" target="_blank" class="btn btn-default btn-xs">
                <i class="fa fa-paperclip tw-mr-1"></i>Attachment
              </a>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if ($ticket->is_anonymous): ?>
        <!-- Internal Note - anonymous tickets have no one to reply back to,
             so staff keep a single note instead of a reply thread -->
        <?php if (staff_can('edit','hr_helpdesk') && ($ticket->internal_note || !$is_closed)): ?>
        <div class="panel_s">
          <div class="panel-heading"><h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_helpdesk_internal_note'); ?></h5></div>
          <div class="panel-body">
            <?php if ($ticket->internal_note): ?>
            <div id="note-display">
              <p class="text-muted tw-mb-2" style="font-size:0.8rem">
                <i class="fa fa-check-circle text-success tw-mr-1"></i><?php echo _l('hr_helpdesk_note_last_saved'); ?> <?php echo date('d M Y H:i', strtotime($ticket->updated_at)); ?>
              </p>
              <p class="tw-mb-2"><?php echo nl2br(htmlspecialchars($ticket->internal_note)); ?></p>
              <?php if ($ticket->internal_note_attachment): ?>
              <div class="tw-mb-2">
                <a href="<?php echo admin_url('hr_module/helpdesk/download/'.$ticket->id.'/note'); ?>" target="_blank" class="btn btn-default btn-xs">
                  <i class="fa fa-paperclip tw-mr-1"></i>Attachment
                </a>
              </div>
              <?php endif; ?>
              <p class="tw-mb-3">
                <span class="label label-<?php echo $sbadge[$ticket->status] ?? 'default'; ?>"><?php echo ucfirst(str_replace('_',' ',$ticket->status)); ?></span>
                <span class="tw-ml-2 text-muted"><?php echo _l('hr_helpdesk_assigned_to'); ?>: <?php echo $ticket->assigned_name ? htmlspecialchars($ticket->assigned_name) : '-'; ?></span>
              </p>
              <?php if (!$is_closed): ?>
              <button type="button" class="btn btn-default btn-sm" id="note-edit-btn">
                <i class="fa fa-pencil-alt tw-mr-1"></i><?php echo _l('hr_edit'); ?>
              </button>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!$is_closed): ?>
            <div id="note-form" <?php if ($ticket->internal_note) echo 'style="display:none"'; ?>>
              <?php echo form_open_multipart(admin_url('hr_module/helpdesk/save_note/'.$ticket->id)); ?>
                <div class="form-group">
                  <textarea name="note" class="form-control" rows="4"
                            placeholder="<?php echo _l('hr_helpdesk_internal_note_placeholder'); ?>"><?php echo htmlspecialchars($ticket->internal_note ?? ''); ?></textarea>
                </div>
                <?php if ($ticket->internal_note_attachment): ?>
                <div class="tw-mb-3">
                  <a href="<?php echo admin_url('hr_module/helpdesk/download/'.$ticket->id.'/note'); ?>" target="_blank" class="btn btn-default btn-xs">
                    <i class="fa fa-paperclip tw-mr-1"></i>Current Attachment
                  </a>
                </div>
                <?php endif; ?>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group select-placeholder">
                      <label><?php echo _l('hr_status'); ?></label>
                      <select name="status" class="selectpicker" data-width="100%">
                        <option value="open" <?php if ($ticket->status === 'open') echo 'selected'; ?>><?php echo _l('hr_helpdesk_status_open'); ?></option>
                        <option value="in_progress" <?php if ($ticket->status === 'in_progress') echo 'selected'; ?>><?php echo _l('hr_helpdesk_status_in_progress'); ?></option>
                        <option value="resolved" <?php if ($ticket->status === 'resolved') echo 'selected'; ?>><?php echo _l('hr_helpdesk_status_resolved'); ?></option>
                        <option value="closed" <?php if ($ticket->status === 'closed') echo 'selected'; ?>><?php echo _l('hr_helpdesk_status_closed'); ?></option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group select-placeholder">
                      <label><?php echo _l('hr_helpdesk_assigned_to'); ?></label>
                      <select name="assigned_to" class="selectpicker" data-width="100%" data-live-search="true">
                        <?php foreach ($staff as $sid => $sname): ?>
                        <option value="<?php echo $sid; ?>" <?php if($ticket->assigned_to==$sid) echo 'selected'; ?>>
                          <?php echo htmlspecialchars($sname); ?>
                        </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Attachment</label>
                      <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.jpg,.png,.txt">
                    </div>
                  </div>
                </div>
                <button type="submit" class="btn btn-primary">
                  <?php echo _l('hr_save'); ?>
                </button>
                <?php if ($ticket->internal_note): ?>
                <button type="button" class="btn btn-default" id="note-cancel-btn"><?php echo _l('hr_cancel'); ?></button>
                <?php endif; ?>
              <?php echo form_close(); ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php elseif ($ticket->internal_note): ?>
        <div class="panel_s">
          <div class="panel-heading"><h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_helpdesk_internal_note'); ?></h5></div>
          <div class="panel-body">
            <p class="tw-mb-0"><?php echo nl2br(htmlspecialchars($ticket->internal_note)); ?></p>
          </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Reply form -->
        <?php if (!$is_closed && staff_can('edit','hr_helpdesk')): ?>
        <div class="panel_s">
          <div class="panel-heading"><h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_helpdesk_reply'); ?></h5></div>
          <div class="panel-body">
            <?php echo form_open_multipart(admin_url('hr_module/helpdesk/reply/'.$ticket->id)); ?>
              <div class="form-group">
                <textarea name="message" class="form-control" rows="4" required
                          placeholder="Type your reply..."></textarea>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group select-placeholder">
                    <label><?php echo _l('hr_status'); ?></label>
                    <select name="status" class="selectpicker" data-width="100%">
                      <option value="open" <?php if ($ticket->status === 'open') echo 'selected'; ?>><?php echo _l('hr_helpdesk_status_open'); ?></option>
                      <option value="in_progress" <?php if ($ticket->status === 'in_progress') echo 'selected'; ?>><?php echo _l('hr_helpdesk_status_in_progress'); ?></option>
                      <option value="resolved" <?php if ($ticket->status === 'resolved') echo 'selected'; ?>><?php echo _l('hr_helpdesk_status_resolved'); ?></option>
                      <option value="closed" <?php if ($ticket->status === 'closed') echo 'selected'; ?>><?php echo _l('hr_helpdesk_status_closed'); ?></option>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group select-placeholder">
                    <label><?php echo _l('hr_helpdesk_assigned_to'); ?></label>
                    <select name="assigned_to" class="selectpicker" data-width="100%" data-live-search="true">
                      <?php foreach ($staff as $sid => $sname): ?>
                      <option value="<?php echo $sid; ?>" <?php if($ticket->assigned_to==$sid) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($sname); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Attachment</label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.jpg,.png,.txt">
                  </div>
                </div>
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-reply tw-mr-1"></i>Post Reply
              </button>
            <?php echo form_close(); ?>
          </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold tw-mb-3">Ticket Info</h5>
            <table class="table table-condensed">
              <tr><td class="text-muted">Ticket #</td><td><strong>#<?php echo $ticket->id; ?></strong></td></tr>
              <tr><td class="text-muted">Status</td>
                  <td><span class="label label-<?php echo $sbadge[$ticket->status] ?? 'default'; ?>"><?php echo ucfirst(str_replace('_',' ',$ticket->status)); ?></span></td></tr>
              <tr><td class="text-muted">Priority</td>
                  <td><span class="label label-<?php echo $pbadge[$ticket->priority] ?? 'default'; ?>"><?php echo ucfirst($ticket->priority); ?></span></td></tr>
              <tr><td class="text-muted">Category</td><td><?php echo $ticket->category ?: '-'; ?></td></tr>
              <tr><td class="text-muted">Assigned</td><td><?php echo $ticket->assigned_name ? htmlspecialchars($ticket->assigned_name) : '<span class="text-muted">Unassigned</span>'; ?></td></tr>
              <tr><td class="text-muted">Replies</td><td><?php echo count($replies); ?></td></tr>
              <tr><td class="text-muted">Opened</td><td><?php echo date('d M Y', strtotime($ticket->created_at)); ?></td></tr>
              <?php if ($ticket->updated_at): ?>
              <tr><td class="text-muted">Updated</td><td><?php echo date('d M Y', strtotime($ticket->updated_at)); ?></td></tr>
              <?php endif; ?>
            </table>

            <?php if (staff_can('edit','hr_helpdesk')): ?>
            <a href="<?php echo admin_url('hr_module/helpdesk/close/'.$ticket->id); ?>"
               class="btn btn-block tw-mt-2 <?php echo $is_closed ? 'btn-success' : 'btn-warning'; ?>">
              <i class="fa fa-<?php echo $is_closed ? 'redo' : 'lock'; ?> tw-mr-1"></i>
              <?php echo $is_closed ? _l('hr_helpdesk_reopen') : _l('hr_helpdesk_close'); ?>
            </a>
            <?php endif; ?>

            <?php if (staff_can('delete','hr_helpdesk')): ?>
            <a href="<?php echo admin_url('hr_module/helpdesk/delete/'.$ticket->id); ?>"
               class="btn btn-default btn-block tw-mt-2 _delete">
              <i class="fa fa-trash tw-mr-1"></i>Delete Ticket
            </a>
            <?php endif; ?>

            <a href="<?php echo admin_url('hr_module/helpdesk'); ?>" class="btn btn-default btn-block tw-mt-2">
              <i class="fa fa-arrow-left tw-mr-1"></i>Back to List
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    $('#note-edit-btn').on('click', function(){
        $('#note-display').hide();
        $('#note-form').show();
    });
    $('#note-cancel-btn').on('click', function(){
        $('#note-form').hide();
        $('#note-display').show();
    });
});
</script>
