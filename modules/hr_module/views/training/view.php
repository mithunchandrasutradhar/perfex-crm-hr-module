<?php defined('BASEPATH') or exit('No direct script access allowed');
$badge = ['scheduled'=>'default','ongoing'=>'warning','completed'=>'success','cancelled'=>'danger'];
$enrolled_ids = array_column((array)$participants, 'employee_id');
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/training'); ?>"><?php echo _l('hr_training_list'); ?></a></li>
          <li class="active"><?php echo htmlspecialchars($training->title); ?></li>
        </ol>
      </div>
    </div>

    <div class="row">
      <!-- Training details -->
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-start tw-mb-4">
              <div>
                <h4 class="tw-font-bold tw-mb-1"><?php echo htmlspecialchars($training->title); ?></h4>
                <?php if ($training->trainer): ?>
                <p class="text-muted tw-mb-0"><i class="fa fa-user-tie tw-mr-1"></i><?php echo htmlspecialchars($training->trainer); ?></p>
                <?php endif; ?>
                <?php if ($training->venue): ?>
                <p class="text-muted tw-mb-0"><i class="fa fa-map-marker-alt tw-mr-1"></i><?php echo htmlspecialchars($training->venue); ?></p>
                <?php endif; ?>
              </div>
              <span class="label label-<?php echo $badge[$training->status] ?? 'default'; ?> tw-text-sm">
                <?php echo ucfirst($training->status); ?>
              </span>
            </div>

            <div class="row tw-mb-3">
              <div class="col-md-3 col-sm-6"><div class="panel_s" style="background:#eff6ff"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-font-bold"><?php echo date('d M Y', strtotime($training->start_date)); ?></div>
                <div class="tw-text-xs text-muted">Start Date</div>
              </div></div></div>
              <div class="col-md-3 col-sm-6"><div class="panel_s" style="background:#eff6ff"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-font-bold"><?php echo date('d M Y', strtotime($training->end_date)); ?></div>
                <div class="tw-text-xs text-muted">End Date</div>
              </div></div></div>
              <div class="col-md-3 col-sm-6"><div class="panel_s" style="background:#f0fdf4"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-font-bold text-success"><?php echo number_format($training->cost, 2); ?></div>
                <div class="tw-text-xs text-muted">Cost</div>
              </div></div></div>
              <div class="col-md-3 col-sm-6"><div class="panel_s" style="background:#f8fafc"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-font-bold"><?php echo $training->enrolled_count; ?><?php echo $training->capacity ? '/'.$training->capacity : ''; ?></div>
                <div class="tw-text-xs text-muted">Enrolled<?php echo $training->capacity ? ' / Capacity' : ''; ?></div>
              </div></div></div>
            </div>

            <?php if ($training->description): ?>
            <h5 class="tw-font-semibold">Description / Objectives</h5>
            <p><?php echo nl2br(htmlspecialchars($training->description)); ?></p>
            <?php endif; ?>

            <?php if ($training->attachment): ?>
            <a href="<?php echo base_url('uploads/hr_module/training/'.$training->attachment); ?>" target="_blank" class="btn btn-default btn-sm">
              <i class="fa fa-paperclip tw-mr-1"></i>View Attachment
            </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Participants -->
        <div class="panel_s">
          <div class="panel-heading tw-flex tw-justify-between tw-items-center">
            <h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_training_participants'); ?>
              <span class="label label-default tw-ml-1"><?php echo count($participants); ?></span>
            </h5>
            <?php if (staff_can('edit','hr_training') && $training->status !== 'cancelled'): ?>
            <button class="btn btn-primary btn-xs" data-toggle="modal" data-target="#enrollModal">
              <i class="fa fa-user-plus tw-mr-1"></i><?php echo _l('hr_training_enroll'); ?>
            </button>
            <?php endif; ?>
          </div>
          <div class="panel-body">
            <?php if (empty($participants)): ?>
            <p class="text-muted">No participants enrolled yet.</p>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover table-condensed">
                <thead><tr>
                  <th><?php echo _l('hr_employee'); ?></th>
                  <th><?php echo _l('hr_department'); ?></th>
                  <th>Enrolled On</th>
                  <th>Status</th>
                  <?php if (staff_can('edit','hr_training')): ?><th><?php echo _l('hr_actions'); ?></th><?php endif; ?>
                </tr></thead>
                <tbody>
                  <?php foreach ($participants as $p): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($p->first_name.' '.$p->last_name); ?>
                      <br><small class="text-muted"><?php echo $p->employee_code; ?></small></td>
                    <td><?php echo htmlspecialchars($p->department_name ?? '-'); ?></td>
                    <td><?php echo date('d M Y', strtotime($p->enrolled_at)); ?></td>
                    <td>
                      <?php if ($p->completed): ?>
                      <span class="label label-success">Completed</span>
                      <?php if ($p->completion_date): ?>
                      <br><small class="text-muted"><?php echo date('d M Y', strtotime($p->completion_date)); ?></small>
                      <?php endif; ?>
                      <?php else: ?>
                      <span class="label label-default">Enrolled</span>
                      <?php endif; ?>
                    </td>
                    <?php if (staff_can('edit','hr_training')): ?>
                    <td>
                      <?php if (!$p->completed): ?>
                      <?php echo form_open(admin_url('hr_module/training/mark_completed/'.$training->id.'/'.$p->employee_id), ['class'=>'d-inline']); ?>
                        <input type="hidden" name="completion_date" value="<?php echo date('Y-m-d'); ?>">
                        <button type="submit" class="btn btn-success btn-xs" title="Mark Completed">
                          <i class="fa fa-check"></i>
                        </button>
                      <?php echo form_close(); ?>
                      <?php endif; ?>
                      <a href="<?php echo admin_url('hr_module/training/remove_participant/'.$training->id.'/'.$p->employee_id); ?>"
                         class="btn btn-danger btn-xs _confirm_delete" title="Remove">
                        <i class="fa fa-times"></i>
                      </a>
                    </td>
                    <?php endif; ?>
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
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold tw-mb-3">Actions</h5>
            <?php if (staff_can('edit','hr_training')): ?>
            <a href="<?php echo admin_url('hr_module/training/edit/'.$training->id); ?>" class="btn btn-primary btn-block tw-mb-2">
              <i class="fa fa-pencil-alt tw-mr-1"></i><?php echo _l('hr_training_edit'); ?>
            </a>
            <?php endif; ?>
            <?php if (staff_can('delete','hr_training')): ?>
            <a href="<?php echo admin_url('hr_module/training/delete/'.$training->id); ?>" class="btn btn-default btn-block _delete">
              <i class="fa fa-trash tw-mr-1"></i>Delete
            </a>
            <?php endif; ?>
            <a href="<?php echo admin_url('hr_module/training'); ?>" class="btn btn-default btn-block tw-mt-2">
              <i class="fa fa-arrow-left tw-mr-1"></i>Back to List
            </a>
          </div>
        </div>

        <!-- Progress -->
        <?php if ($training->enrolled_count > 0): ?>
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold">Completion Progress</h5>
            <?php $pct = $training->enrolled_count > 0 ? round(($training->completed_count / $training->enrolled_count)*100) : 0; ?>
            <div class="progress" style="height:10px">
              <div class="progress-bar progress-bar-success" style="width:<?php echo $pct; ?>%"></div>
            </div>
            <p class="text-muted tw-text-sm tw-mt-1">
              <?php echo $training->completed_count; ?> of <?php echo $training->enrolled_count; ?> completed (<?php echo $pct; ?>%)
            </p>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Enroll Modal -->
<div class="modal fade" id="enrollModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title"><?php echo _l('hr_training_enroll'); ?> Participants</h4>
    </div>
    <div class="modal-body">
      <p class="text-muted">Select employees to enroll. Already enrolled employees are excluded.</p>
      <div style="max-height:340px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;padding:8px" id="enroll-list">
        <?php foreach ($employees as $eid => $ename): ?>
        <?php if (in_array($eid, $enrolled_ids)) continue; ?>
        <div class="checkbox">
          <label>
            <input type="checkbox" class="enroll-chk" value="<?php echo $eid; ?>">
            <?php echo htmlspecialchars($ename); ?>
          </label>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-primary" id="enroll-btn">
        <i class="fa fa-user-plus tw-mr-1"></i>Enroll Selected
      </button>
    </div>
  </div></div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    $('#enroll-btn').on('click', function(){
        var ids = [];
        $('.enroll-chk:checked').each(function(){ ids.push($(this).val()); });
        if (!ids.length) { alert('Select at least one employee.'); return; }
        $(this).prop('disabled', true).text('Enrolling...');
        $.post('<?php echo admin_url('hr_module/training/enroll/'.$training->id); ?>',
               {employee_ids: ids, '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'},
               function(r){
                   if(r.success){ alert_float('success', r.message); location.reload(); }
                   else alert_float('danger', r.message);
               }, 'json');
    });
});
</script>
