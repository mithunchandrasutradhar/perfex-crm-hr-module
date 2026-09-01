<?php defined('BASEPATH') or exit('No direct script access allowed');
$badge = ['scheduled'=>'default','ongoing'=>'warning','completed'=>'success','cancelled'=>'danger'];
$att_badge = ['pending'=>'default','present'=>'success','absent'=>'danger','partial'=>'warning'];
$enrolled_ids = array_column((array)$participants, 'employee_id');
$instructor_label = $training->instructor_name ?: $training->trainer;
$can_generate_report = staff_can('create','hr_training') || staff_can('edit','hr_training') || $is_instructor;
$sessions_by_date = array_column($sessions, null, 'session_date');
// Departments actually represented among this training's participants - used
// to populate the Department filter on both the Participants and Daily
// Attendance tables below, rather than every department in the company.
$participant_departments = [];
foreach ($participants as $p) {
    if (!empty($p->department_name)) {
        $participant_departments[$p->department_name] = true;
    }
}
$participant_departments = array_keys($participant_departments);
sort($participant_departments);
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
                <p class="text-muted tw-mb-0"><i class="fa fa-user-tie tw-mr-1"></i><?php echo _l('hr_training_trainer'); ?>: <?php echo htmlspecialchars($instructor_label); ?>
                  <?php if (!$training->instructor_id && ($training->external_instructor_email || $training->external_instructor_phone)): ?>
                  <span class="tw-text-neutral-400">(external<?php echo $training->external_instructor_email ? ' &mdash; ' . htmlspecialchars($training->external_instructor_email) : ''; ?><?php echo $training->external_instructor_phone ? ' &mdash; ' . htmlspecialchars($training->external_instructor_phone) : ''; ?>)</span>
                  <?php endif; ?>
                </p>
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
            <!-- Authored via the tinymce editor on Add/Edit (real HTML), so
                 rendered as-is here rather than escaped/nl2br'd plain text. -->
            <div><?php echo $training->description; ?></div>
            <?php endif; ?>

            <?php if ($training->completion_note): ?>
            <h5 class="tw-font-semibold"><?php echo _l('hr_training_completion_note'); ?></h5>
            <p><?php echo nl2br(htmlspecialchars($training->completion_note)); ?></p>
            <?php endif; ?>

            <?php if ($training->attachment): ?>
            <a href="<?php echo admin_url('hr_module/training/download/'.$training->id); ?>" target="_blank" class="btn btn-default btn-sm">
              <i class="fa fa-paperclip tw-mr-1"></i>View Attachment
            </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Participants -->
        <div class="panel_s">
          <div class="panel-heading tw-flex tw-justify-between tw-items-center tw-flex-wrap tw-gap-2">
            <h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_training_participants'); ?>
              <span class="label label-default tw-ml-1"><?php echo count($participants); ?></span>
            </h5>
            <div class="tw-flex tw-items-center tw-gap-2 tw-flex-wrap">
              <div class="dataTables_filter">
                <input type="search" id="participants-filter-search" class="form-control input-sm" placeholder="Search...">
              </div>
              <?php if (!empty($participant_departments)): ?>
              <select id="participants-filter-dept" class="selectpicker" data-width="150px" data-live-search="true">
                <option value="">All Departments</option>
                <?php foreach ($participant_departments as $dname): ?>
                <option value="<?php echo htmlspecialchars($dname); ?>"><?php echo htmlspecialchars($dname); ?></option>
                <?php endforeach; ?>
              </select>
              <?php endif; ?>
              <select id="participants-filter-status" class="selectpicker" data-width="130px">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="partial">Partial</option>
              </select>
              <?php if (staff_can('edit','hr_training') && $training->status !== 'cancelled'): ?>
              <button class="btn btn-primary" data-toggle="modal" data-target="#enrollModal">
                <i class="fa fa-user-plus tw-mr-1"></i><?php echo _l('hr_training_enroll'); ?>
              </button>
              <?php endif; ?>
            </div>
          </div>
          <div class="panel-body">
            <?php if (empty($participants)): ?>
            <p class="text-muted">No participants enrolled yet.</p>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover table-condensed table-hr-training-participants">
                <thead><tr>
                  <th><?php echo _l('hr_employee'); ?></th>
                  <th><?php echo _l('hr_department'); ?></th>
                  <th>Enrolled On</th>
                  <th><?php echo _l('hr_training_attendance'); ?></th>
                  <?php if ($can_mark_attendance): ?><th><?php echo _l('hr_training_instructor_note'); ?></th><?php endif; ?>
                  <?php if ($can_mark_attendance): ?><th><?php echo _l('hr_actions'); ?></th><?php endif; ?>
                </tr></thead>
                <tbody>
                  <?php foreach ($participants as $p): ?>
                  <?php $note_preview = $p->notes ? mb_strimwidth($p->notes, 0, 60, '...') : ''; ?>
                  <tr data-department="<?php echo htmlspecialchars($p->department_name ?? ''); ?>" data-status="<?php echo htmlspecialchars($p->attendance_status); ?>">
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
                    <td style="max-width:220px">
                      <a href="#" data-toggle="modal" data-target="#noteModal<?php echo $p->employee_id; ?>"
                         class="<?php echo $p->notes ? '' : 'text-muted'; ?>"
                         title="<?php echo $p->notes ? htmlspecialchars($p->notes) : _l('hr_training_add_note'); ?>">
                        <?php echo $note_preview !== '' ? htmlspecialchars($note_preview) : '<i class="fa-regular fa-plus tw-mr-1"></i>'._l('hr_training_add_note'); ?>
                      </a>
                    </td>
                    <td>
                      <a href="#" data-toggle="modal" data-target="#noteModal<?php echo $p->employee_id; ?>"
                         class="tw-text-neutral-500" title="<?php echo _l('hr_edit'); ?>">
                        <i class="fa fa-pencil-alt"></i>
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
          <div class="panel-heading tw-flex tw-justify-between tw-items-center tw-flex-wrap tw-gap-2">
            <h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_training_daily_attendance'); ?></h5>
            <div class="tw-flex tw-items-center tw-gap-2 tw-flex-wrap">
              <div class="dataTables_filter">
                <input type="search" id="daily-filter-search" class="form-control input-sm" placeholder="Search...">
              </div>
              <?php if (!empty($participant_departments)): ?>
              <select id="daily-filter-dept" class="selectpicker" data-width="150px" data-live-search="true">
                <option value="">All Departments</option>
                <?php foreach ($participant_departments as $dname): ?>
                <option value="<?php echo htmlspecialchars($dname); ?>"><?php echo htmlspecialchars($dname); ?></option>
                <?php endforeach; ?>
              </select>
              <?php endif; ?>
              <select id="daily-filter-status" class="selectpicker" data-width="130px">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="partial">Partial</option>
              </select>
            </div>
          </div>
          <div class="panel-body">
            <div class="table-responsive">
              <table class="table table-hover table-condensed table-hr-training-daily-attendance">
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
                    <?php if ($can_mark_attendance): ?>
                    <div class="tw-mt-1">
                      <?php echo form_open(admin_url('hr_module/training/mark_daily_attendance_bulk/'.$training->id), ['style'=>'display:inline']); ?>
                        <input type="hidden" name="date" value="<?php echo $day; ?>">
                        <button type="submit" name="status" value="present"
                                class="text-success tw-bg-transparent tw-border-0 tw-px-1 tw-font-normal"
                                data-toggle="tooltip" title="Mark everyone Present for this day"
                                onclick="return confirm('Mark all participants Present for <?php echo date('d M Y', strtotime($day)); ?>?');">
                          <i class="fa fa-check-double"></i>
                        </button>
                        <button type="submit" name="status" value="absent"
                                class="text-danger tw-bg-transparent tw-border-0 tw-px-1 tw-font-normal"
                                data-toggle="tooltip" title="Mark everyone Absent for this day"
                                onclick="return confirm('Mark all participants Absent for <?php echo date('d M Y', strtotime($day)); ?>?');">
                          <i class="fa fa-ban"></i>
                        </button>
                      <?php echo form_close(); ?>
                    </div>
                    <?php endif; ?>
                  </th>
                  <?php endforeach; ?>
                </tr></thead>
                <tbody>
                  <?php foreach ($participants as $p): ?>
                  <tr data-department="<?php echo htmlspecialchars($p->department_name ?? ''); ?>" data-status="<?php echo htmlspecialchars($p->attendance_status); ?>">
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

        <!-- Employee Feedback (about the instructor/training) - stacked, always visible -->
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
            <?php if ($my_feedback): ?>
            <div id="feedback-display">
              <p class="tw-mb-2"><?php echo nl2br(htmlspecialchars($my_feedback)); ?></p>
              <button type="button" class="btn btn-default btn-sm" id="feedback-edit-btn">
                <i class="fa fa-pencil-alt tw-mr-1"></i><?php echo _l('hr_edit'); ?>
              </button>
            </div>
            <?php endif; ?>
            <div id="feedback-form" <?php if ($my_feedback) echo 'style="display:none"'; ?>>
              <p class="text-muted"><?php echo _l('hr_training_my_feedback_hint'); ?></p>
              <?php echo form_open(admin_url('hr_module/training/save_employee_feedback/'.$training->id.'/'.$own_emp_id)); ?>
                <div class="form-group">
                  <textarea name="feedback" class="form-control" rows="4"
                            placeholder="<?php echo _l('hr_training_feedback_placeholder'); ?>"><?php echo htmlspecialchars($my_feedback ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo _l('hr_save'); ?></button>
                <?php if ($my_feedback): ?>
                <button type="button" class="btn btn-default" id="feedback-cancel-btn"><?php echo _l('hr_cancel'); ?></button>
                <?php endif; ?>
              <?php echo form_close(); ?>
            </div>
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
              <i class="fa-regular fa-file-lines tw-mr-1"></i><?php echo _l('hr_training_generate_report'); ?>
            </a>
            <a href="<?php echo admin_url('hr_module/training/email_report/'.$training->id); ?>" class="btn btn-default btn-block tw-mb-2 _delete">
              <i class="fa-regular fa-envelope tw-mr-1"></i><?php echo _l('hr_training_email_report'); ?>
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
    // Plain client-side filter + pagination - deliberately NOT the DataTables
    // library: this page's tables already have all their real rows
    // server-rendered (no AJAX), and initializing DataTables on top of them
    // triggers Perfex's own "dt-table-loading" skeleton CSS (meant for tables
    // that start empty and get filled via AJAX), which then never clears
    // since no AJAX call ever fires to complete it. This just shows/hides the
    // existing <tr> elements (by filter match, then by page) - no data,
    // columns, or row actions/forms/modals change at all.
    function hrSetupTable(tableSelector, searchInputId, deptSelectId, statusSelectId, pageSize) {
        var $table = $(tableSelector);
        if ($table.length === 0) return;
        var $allRows = $table.find('tbody tr');
        if ($allRows.length === 0) return;

        var $searchInput = searchInputId ? $('#' + searchInputId) : $();
        var $deptSel      = deptSelectId   ? $('#' + deptSelectId)   : $();
        var $statusSel    = statusSelectId ? $('#' + statusSelectId) : $();
        var current = 1;
        var $nav = null, $info, $ul;

        function matchingRows() {
            var search = $searchInput.length ? $.trim($searchInput.val()).toLowerCase() : '';
            var dept   = $deptSel.length   ? $deptSel.val()   : '';
            var status = $statusSel.length ? $statusSel.val() : '';
            return $allRows.filter(function () {
                var okSearch = !search || $(this).text().toLowerCase().indexOf(search) !== -1;
                var okDept   = !dept   || $(this).attr('data-department') === dept;
                var okStatus = !status || $(this).attr('data-status') === status;
                return okSearch && okDept && okStatus;
            });
        }

        // Perfex's own mainWrapperHeightFix() (assets/js/main.js) measures the
        // page's full height on load and bakes it into an inline min-height on
        // the content wrapper - it runs before this script hides most of the
        // rows below, so it captures the tall, pre-pagination height and never
        // recalculates on its own afterwards. Re-running it (it's just a plain
        // global function, safe to call again) after every show/hide here keeps
        // that min-height honest instead of leaving a huge blank gap under a
        // short filtered/paginated table.
        function hrRefreshWrapperHeight() {
            if (typeof mainWrapperHeightFix === 'function') {
                // Clear every element mainWrapperHeightFix() itself sets a
                // min-height on - not just #wrapper - BEFORE recalculating.
                // It measures $(document).outerHeight(true), which includes
                // ALL THREE of these elements' current min-heights; leaving
                // any one of them stale still feeds an inflated number back
                // into the recalculation, so it has to be all or nothing.
                $('#wrapper, #menu, #setup-menu-wrapper').css('min-height', '');
                setTimeout(mainWrapperHeightFix, 0);
            }
        }

        function render() {
            $allRows.hide();
            var $matched = matchingRows();
            var total = $matched.length;

            if (total <= pageSize) {
                $matched.show();
                if ($nav) { $nav.remove(); $nav = null; }
                hrRefreshWrapperHeight();
                return;
            }

            if (!$nav) {
                // Same dataTables_info + Bootstrap ul.pagination markup DataTables
                // itself renders everywhere else in the app - same look, without
                // needing the DataTables engine (which is what caused the
                // loading-skeleton bug on an already-server-rendered table).
                $nav  = $('<div class="dataTables_wrapper tw-mt-2 tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-2"></div>');
                $info = $('<div class="dataTables_info"></div>');
                var $pager = $('<div class="dataTables_paginate paging_simple_numbers"><ul class="pagination"></ul></div>');
                $ul = $pager.find('ul.pagination');
                $nav.append($info).append($pager);
                $table.closest('.table-responsive').after($nav);
            }

            var totalPages = Math.ceil(total / pageSize);
            if (current > totalPages) current = 1;
            var start = (current - 1) * pageSize;
            var end = Math.min(start + pageSize, total);
            $matched.hide().slice(start, end).show();
            $info.text('Showing ' + (start + 1) + ' to ' + end + ' of ' + total + ' entries');

            $ul.empty();

            var $prevLi = $('<li' + (current === 1 ? ' class="disabled"' : '') + '></li>');
            var $prevA  = $('<a href="javascript:void(0)">Previous</a>');
            if (current !== 1) $prevA.on('click', function(){ current--; render(); });
            $ul.append($prevLi.append($prevA));

            for (var p = 1; p <= totalPages; p++) {
                (function(p){
                    var $li = $('<li' + (p === current ? ' class="active"' : '') + '></li>');
                    var $a  = $('<a href="javascript:void(0)">' + p + '</a>');
                    $a.on('click', function(){ current = p; render(); });
                    $ul.append($li.append($a));
                })(p);
            }

            var $nextLi = $('<li' + (current === totalPages ? ' class="disabled"' : '') + '></li>');
            var $nextA  = $('<a href="javascript:void(0)">Next</a>');
            if (current !== totalPages) $nextA.on('click', function(){ current++; render(); });
            $ul.append($nextLi.append($nextA));

            hrRefreshWrapperHeight();
        }

        $searchInput.on('keyup input', function(){ current = 1; render(); });
        $deptSel.add($statusSel).on('change changed.bs.select', function(){ current = 1; render(); });
        render();
    }
    hrSetupTable('.table-hr-training-participants', 'participants-filter-search', 'participants-filter-dept', 'participants-filter-status', 10);
    hrSetupTable('.table-hr-training-daily-attendance', 'daily-filter-search', 'daily-filter-dept', 'daily-filter-status', 10);

    // Perfex's own main.js also re-runs mainWrapperHeightFix() on the window's
    // "load" event (assets/js/main.js, ~150ms after load fires) - that happens
    // AFTER document-ready, i.e. after the two hrSetupTable() calls above
    // already hid most of the rows, so it should already measure correctly.
    // Belt-and-suspenders anyway: clear the wrapper's min-height and force one
    // more recalculation slightly after that, so this is guaranteed to be the
    // last word regardless of load timing.
    $(window).on('load', function () {
        setTimeout(function () {
            if (typeof mainWrapperHeightFix === 'function') {
                $('#wrapper, #menu, #setup-menu-wrapper').css('min-height', '');
                mainWrapperHeightFix();
            }
        }, 300);
    });

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
    $('#feedback-edit-btn').on('click', function(){
        $('#feedback-display').hide();
        $('#feedback-form').show();
    });
    $('#feedback-cancel-btn').on('click', function(){
        $('#feedback-form').hide();
        $('#feedback-display').show();
    });
});
</script>
