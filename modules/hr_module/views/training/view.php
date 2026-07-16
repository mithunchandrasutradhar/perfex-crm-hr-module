<?php defined('BASEPATH') or exit('No direct script access allowed');
$badge = ['scheduled'=>'default','ongoing'=>'warning','completed'=>'success','cancelled'=>'danger'];
$att_badge = ['pending'=>'default','present'=>'success','absent'=>'danger','partial'=>'warning'];
$enrolled_ids = array_column((array)$participants, 'employee_id');
$instructor_label = $training->instructor_name ?: $training->trainer;
$can_generate_report = staff_can('create','hr_training') || staff_can('edit','hr_training') || $is_instructor;
$sessions_by_date = array_column($sessions, null, 'session_date');
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
                <?php if ($instructor_label): ?>
                <p class="text-muted tw-mb-0"><i class="fa fa-user-tie tw-mr-1"></i><?php echo _l('hr_training_trainer'); ?>: <?php echo htmlspecialchars($instructor_label); ?></p>
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

            <?php if (!empty($sessions)): ?>
            <h5 class="tw-font-semibold"><?php echo _l('hr_training_sessions'); ?></h5>
            <div class="tw-flex tw-flex-wrap tw-gap-2 tw-mb-3">
              <?php foreach ($sessions as $s): ?>
              <span class="label label-default tw-text-sm" style="padding:6px 10px">
                <i class="fa fa-calendar-day tw-mr-1"></i><?php echo date('d M Y', strtotime($s->session_date)); ?>
                <?php if ($s->start_time || $s->end_time): ?>
                <span class="tw-ml-1">
                  <?php echo $s->start_time ? date('g:i A', strtotime($s->start_time)) : '?'; ?>
                  &ndash;
                  <?php echo $s->end_time ? date('g:i A', strtotime($s->end_time)) : '?'; ?>
                </span>
                <?php endif; ?>
              </span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($training->description): ?>
            <h5 class="tw-font-semibold">Description / Objectives</h5>
            <p><?php echo nl2br(htmlspecialchars($training->description)); ?></p>
            <?php endif; ?>

            <?php if ($training->completion_note): ?>
            <h5 class="tw-font-semibold"><?php echo _l('hr_training_completion_note'); ?></h5>
            <p><?php echo nl2br(htmlspecialchars($training->completion_note)); ?></p>
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
                  <th><?php echo _l('hr_training_attendance'); ?></th>
                  <?php if ($can_mark_attendance): ?><th><?php echo _l('hr_actions'); ?></th><?php endif; ?>
                </tr></thead>
                <tbody>
                  <?php foreach ($participants as $p): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($p->first_name.' '.$p->last_name); ?>
                      <br><small class="text-muted"><?php echo $p->employee_code; ?></small></td>
                    <td><?php echo htmlspecialchars($p->department_name ?? '-'); ?></td>
                    <td><?php echo date('d M Y', strtotime($p->enrolled_at)); ?></td>
                    <td>
                      <span class="label label-<?php echo $att_badge[$p->attendance_status] ?? 'default'; ?>">
                        <?php echo ucfirst($p->attendance_status); ?>
                      </span>
                      <?php if ($p->total_days > 0): ?>
                      <br><small class="text-muted"><?php echo $p->present_days; ?> of <?php echo $p->total_days; ?> days present</small>
                      <?php endif; ?>
                    </td>
                    <?php if ($can_mark_attendance): ?>
                    <td>
                      <a href="#" data-toggle="modal" data-target="#noteModal<?php echo $p->employee_id; ?>"
                         class="<?php echo $p->notes ? 'text-warning' : 'tw-text-neutral-500'; ?>" title="<?php echo _l('hr_training_add_note'); ?>">
                        <i class="fa fa-sticky-note"></i>
                      </a>
                      <?php if (staff_can('edit','hr_training')): ?>
                      <a href="<?php echo admin_url('hr_module/training/remove_participant/'.$training->id.'/'.$p->employee_id); ?>"
                         class="tw-text-neutral-500 _confirm_delete" data-toggle="tooltip" title="Remove">
                        <i class="fa fa-user-times"></i>
                      </a>
                      <?php endif; ?>
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

        <!-- Daily Attendance -->
        <?php if (!empty($participants) && !empty($days)): ?>
        <div class="panel_s">
          <div class="panel-heading">
            <h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_training_daily_attendance'); ?></h5>
          </div>
          <div class="panel-body">
            <div class="table-responsive">
              <table class="table table-hover table-condensed">
                <thead><tr>
                  <th><?php echo _l('hr_employee'); ?></th>
                  <?php foreach ($days as $day): ?>
                  <?php $sess = $sessions_by_date[$day] ?? null; ?>
                  <th class="text-center">
                    <?php echo date('d M', strtotime($day)); ?>
                    <?php if ($sess && ($sess->start_time || $sess->end_time)): ?>
                    <br><small class="text-muted tw-font-normal">
                      <?php echo $sess->start_time ? date('g:i A', strtotime($sess->start_time)) : '?'; ?>&ndash;<?php echo $sess->end_time ? date('g:i A', strtotime($sess->end_time)) : '?'; ?>
                    </small>
                    <?php endif; ?>
                  </th>
                  <?php endforeach; ?>
                </tr></thead>
                <tbody>
                  <?php foreach ($participants as $p): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($p->first_name.' '.$p->last_name); ?></td>
                    <?php foreach ($days as $day): ?>
                    <?php $day_status = $attendance_grid[$p->employee_id][$day] ?? 'pending'; ?>
                    <td class="text-center">
                      <?php if ($can_mark_attendance): ?>
                      <?php echo form_open(admin_url('hr_module/training/mark_daily_attendance/'.$training->id.'/'.$p->employee_id), ['style'=>'display:inline']); ?>
                        <input type="hidden" name="date" value="<?php echo $day; ?>">
                        <button type="submit" name="status" value="present"
                                class="<?php echo $day_status === 'present' ? 'text-success' : 'tw-text-neutral-300'; ?> tw-bg-transparent tw-border-0 tw-px-1"
                                data-toggle="tooltip" title="<?php echo _l('hr_training_mark_present'); ?>">
                          <i class="fa fa-check"></i>
                        </button>
                        <button type="submit" name="status" value="absent"
                                class="<?php echo $day_status === 'absent' ? 'text-danger' : 'tw-text-neutral-300'; ?> tw-bg-transparent tw-border-0 tw-px-1"
                                data-toggle="tooltip" title="<?php echo _l('hr_training_mark_absent'); ?>">
                          <i class="fa fa-times"></i>
                        </button>
                      <?php echo form_close(); ?>
                      <?php else: ?>
                      <span class="label label-<?php echo $att_badge[$day_status] ?? 'default'; ?>">
                        <?php echo ucfirst($day_status); ?>
                      </span>
                      <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Employee Feedback (about the instructor/training) -->
        <?php $has_feedback = false; foreach ($participants as $p) { if (!empty($p->employee_feedback)) { $has_feedback = true; break; } } ?>
        <?php if ($can_mark_attendance && $has_feedback): ?>
        <div class="panel_s">
          <div class="panel-heading">
            <h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_training_employee_feedback'); ?></h5>
          </div>
          <div class="panel-body">
            <?php foreach ($participants as $p): if (empty($p->employee_feedback)) continue; ?>
            <div class="tw-mb-3" style="border-left:3px solid #6366f1;padding-left:12px">
              <strong><?php echo htmlspecialchars($p->first_name.' '.$p->last_name); ?></strong>
              <p class="tw-mb-0"><?php echo nl2br(htmlspecialchars($p->employee_feedback)); ?></p>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- My Feedback (the logged-in employee's own feedback about this training) -->
        <?php if (!empty($own_emp_id) && in_array($own_emp_id, $enrolled_ids, false)): ?>
        <?php $my_feedback = ''; foreach ($participants as $p) { if ($p->employee_id == $own_emp_id) { $my_feedback = $p->employee_feedback; break; } } ?>
        <div class="panel_s">
          <div class="panel-heading">
            <h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_training_my_feedback'); ?></h5>
          </div>
          <div class="panel-body">
            <p class="text-muted"><?php echo _l('hr_training_my_feedback_hint'); ?></p>
            <?php echo form_open(admin_url('hr_module/training/save_employee_feedback/'.$training->id.'/'.$own_emp_id)); ?>
              <div class="form-group">
                <textarea name="feedback" class="form-control" rows="4"
                          placeholder="<?php echo _l('hr_training_feedback_placeholder'); ?>"><?php echo htmlspecialchars($my_feedback ?? ''); ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary"><?php echo _l('hr_save'); ?></button>
            <?php echo form_close(); ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold tw-mb-3">Actions</h5>
            <?php if ($can_mark_attendance && !in_array($training->status, ['completed', 'cancelled'], true)): ?>
            <button type="button" class="btn btn-success btn-block tw-mb-2" data-toggle="modal" data-target="#completeModal">
              <i class="fa fa-check-circle tw-mr-1"></i><?php echo _l('hr_training_mark_complete'); ?>
            </button>
            <?php endif; ?>
            <?php if ($can_generate_report): ?>
            <a href="<?php echo admin_url('hr_module/training/report/'.$training->id); ?>" target="_blank" class="btn btn-default btn-block tw-mb-2">
              <i class="fa fa-file-text-o tw-mr-1"></i><?php echo _l('hr_training_generate_report'); ?>
            </a>
            <?php endif; ?>
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
        <?php if ($training->total_day_marks > 0): ?>
        <?php $pct = round(($training->present_day_marks / $training->total_day_marks) * 100); ?>
        <div class="panel_s">
          <div class="panel-body">
            <p class="project-info tw-mb-1 tw-font-medium tw-text-base tw-tracking-tight">
              <?php echo _l('hr_training_attendance'); ?>
              <span class="tw-text-neutral-500 tw-text-sm"><?php echo $pct; ?>%</span>
            </p>
            <div class="progress progress-bar-mini">
              <div class="progress-bar progress-bar-success no-percent-text not-dynamic" role="progressbar"
                   aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"
                   style="width: 0%" data-percent="<?php echo $pct; ?>">
              </div>
            </div>
            <p class="text-muted tw-text-sm tw-mt-1">
              <?php echo $training->present_day_marks; ?> of <?php echo $training->total_day_marks; ?> attendance days marked present
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
      <div class="form-group select-placeholder tw-mb-0">
        <select id="enroll-select" class="selectpicker" multiple data-width="100%"
                data-live-search="true" data-actions-box="true"
                data-none-selected-text="<?php echo _l('hr_select'); ?>">
          <?php foreach ($employees as $eid => $ename): ?>
          <?php if (in_array($eid, $enrolled_ids)) continue; ?>
          <option value="<?php echo $eid; ?>"><?php echo htmlspecialchars($ename); ?></option>
          <?php endforeach; ?>
        </select>
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

<!-- Mark Complete Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <?php echo form_open(admin_url('hr_module/training/mark_complete/'.$training->id)); ?>
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title"><?php echo _l('hr_training_mark_complete'); ?></h4>
    </div>
    <div class="modal-body">
      <p class="text-muted"><?php echo _l('hr_training_confirm_mark_complete'); ?></p>
      <div class="form-group">
        <label for="completion_note"><?php echo _l('hr_training_completion_note'); ?></label>
        <textarea name="completion_note" id="completion_note" class="form-control" rows="4"
                  placeholder="<?php echo _l('hr_training_completion_note_placeholder'); ?>"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
      <button type="submit" class="btn btn-success"><?php echo _l('hr_training_mark_complete'); ?></button>
    </div>
    <?php echo form_close(); ?>
  </div></div>
</div>

<!-- Employee Note Modals -->
<?php if ($can_mark_attendance): foreach ($participants as $p): ?>
<div class="modal fade" id="noteModal<?php echo $p->employee_id; ?>" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <?php echo form_open(admin_url('hr_module/training/save_employee_note/'.$training->id.'/'.$p->employee_id)); ?>
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title"><?php echo _l('hr_training_note_about'); ?> <?php echo htmlspecialchars($p->first_name.' '.$p->last_name); ?></h4>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <textarea name="note" class="form-control" rows="4"
                  placeholder="<?php echo _l('hr_training_note_placeholder'); ?>"><?php echo htmlspecialchars($p->notes ?? ''); ?></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
      <button type="submit" class="btn btn-primary"><?php echo _l('hr_save'); ?></button>
    </div>
    <?php echo form_close(); ?>
  </div></div>
</div>
<?php endforeach; endif; ?>
<?php init_tail(); ?>
<script>
$(function(){
    $('#enroll-btn').on('click', function(){
        var ids = $('#enroll-select').val() || [];
        if (!ids.length) { alert_float('danger', 'Select at least one employee.'); return; }
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
