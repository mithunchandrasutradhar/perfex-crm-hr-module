<?php defined('BASEPATH') or exit('No direct script access allowed');
$is_edit  = !empty($training);
$form_url = $is_edit
    ? admin_url('hr_module/training/edit/' . $training->id)
    : admin_url('hr_module/training/add');
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/training'); ?>"><?php echo _l('hr_training_list'); ?></a></li>
          <?php if ($is_edit): ?>
          <li><a href="<?php echo admin_url('hr_module/training/view/'.$training->id); ?>"><?php echo htmlspecialchars($training->title); ?></a></li>
          <?php endif; ?>
          <li class="active"><?php echo $is_edit ? _l('hr_training_edit') : _l('hr_training_add'); ?></li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo $is_edit ? _l('hr_training_edit') : _l('hr_training_add'); ?></h4>
            <?php echo form_open_multipart($form_url); ?>
              <div class="form-group">
                <label><?php echo _l('hr_training_title'); ?> <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required
                       value="<?php echo $is_edit ? htmlspecialchars($training->title) : ''; ?>"
                       placeholder="e.g. Leadership Development Program">
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group select-placeholder">
                    <label for="instructor_id"><?php echo _l('hr_training_trainer'); ?></label>
                    <select name="instructor_id" id="instructor_id" class="selectpicker" data-width="100%"
                            data-live-search="true" data-none-selected-text="<?php echo _l('hr_select'); ?>">
                      <option value=""><?php echo _l('hr_select'); ?></option>
                      <?php foreach ($staff as $id => $name): ?>
                      <option value="<?php echo $id; ?>" <?php if ($is_edit && $training->instructor_id == $id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($name); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                    <?php if ($is_edit && !$training->instructor_id && $training->trainer): ?>
                    <small class="text-muted"><?php echo _l('hr_training_legacy_trainer'); ?>: <?php echo htmlspecialchars($training->trainer); ?></small>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?php echo _l('hr_training_venue'); ?></label>
                    <input type="text" name="venue" class="form-control"
                           value="<?php echo $is_edit ? htmlspecialchars($training->venue) : ''; ?>"
                           placeholder="Location or Online">
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_training_sessions'); ?> <span class="text-danger">*</span></label>
                <p class="text-muted tw-mb-2" style="font-size:0.85rem"><?php echo _l('hr_training_sessions_hint'); ?></p>
                <div id="sessions-container">
                  <?php if (!empty($sessions)): foreach ($sessions as $s): ?>
                  <div class="row session-row tw-mb-2">
                    <div class="col-md-5">
                      <div class="input-group date">
                        <input type="text" name="session_date[]" class="form-control datepicker" autocomplete="off" required
                               value="<?php echo _d($s->session_date); ?>">
                        <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <input type="time" name="session_start_time[]" class="form-control"
                             value="<?php echo $s->start_time ? date('H:i', strtotime($s->start_time)) : ''; ?>">
                    </div>
                    <div class="col-md-3">
                      <input type="time" name="session_end_time[]" class="form-control"
                             value="<?php echo $s->end_time ? date('H:i', strtotime($s->end_time)) : ''; ?>">
                    </div>
                    <div class="col-md-1">
                      <a href="#" class="btn btn-danger" onclick="remove_session_row(this); return false;"><i class="fa fa-trash"></i></a>
                    </div>
                  </div>
                  <?php endforeach; else: ?>
                  <div class="row session-row tw-mb-2">
                    <div class="col-md-5">
                      <div class="input-group date">
                        <input type="text" name="session_date[]" class="form-control datepicker" autocomplete="off" required value="">
                        <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <input type="time" name="session_start_time[]" class="form-control" value="">
                    </div>
                    <div class="col-md-3">
                      <input type="time" name="session_end_time[]" class="form-control" value="">
                    </div>
                    <div class="col-md-1">
                      <a href="#" class="btn btn-danger" onclick="remove_session_row(this); return false;"><i class="fa fa-trash"></i></a>
                    </div>
                  </div>
                  <?php endif; ?>
                </div>
                <button type="button" class="btn btn-default btn-sm" id="add-session-btn">
                  <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_training_add_day'); ?>
                </button>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?php echo _l('hr_training_cost'); ?></label>
                    <input type="number" step="0.01" min="0" name="cost" class="form-control"
                           value="<?php echo $is_edit ? $training->cost : '0'; ?>">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?php echo _l('hr_training_capacity'); ?> <small class="text-muted">(leave blank = unlimited)</small></label>
                    <input type="number" min="1" name="capacity" class="form-control"
                           value="<?php echo $is_edit ? $training->capacity : ''; ?>">
                  </div>
                </div>
              </div>
              <div class="form-group select-placeholder">
                <label for="status"><?php echo _l('hr_status'); ?></label>
                <select name="status" id="status" class="selectpicker" data-width="100%">
                  <?php foreach (['scheduled','ongoing','completed','cancelled'] as $s): ?>
                  <option value="<?php echo $s; ?>" <?php if($is_edit && $training->status===$s) echo 'selected'; elseif(!$is_edit && $s==='scheduled') echo 'selected'; ?>>
                    <?php echo ucfirst($s); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Description / Objectives</label>
                <textarea name="description" class="form-control" rows="4"
                          placeholder="Training objectives, agenda, prerequisites..."><?php echo $is_edit ? htmlspecialchars($training->description) : ''; ?></textarea>
              </div>
              <div class="form-group">
                <label>Attachment <small class="text-muted">(PDF/DOC/PPT/Image, max 5MB)</small></label>
                <?php if ($is_edit && $training->attachment): ?>
                <div class="tw-mb-2">
                  <a href="<?php echo admin_url('hr_module/training/download/'.$training->id); ?>" target="_blank">
                    <i class="fa fa-file tw-mr-1"></i>Current attachment
                  </a>
                </div>
                <?php endif; ?>
                <input type="file" name="attachment" class="form-control"
                       accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png">
              </div>
              <div class="tw-flex tw-gap-2">
                <button type="submit" class="btn btn-primary"><?php echo _l('hr_save'); ?></button>
                <a href="<?php echo $is_edit ? admin_url('hr_module/training/view/'.$training->id) : admin_url('hr_module/training'); ?>" class="btn btn-default">Cancel</a>
              </div>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
function remove_session_row(el) {
    if ($('#sessions-container .session-row').length <= 1) {
        alert_float('danger', '<?php echo _l('hr_training_at_least_one_day'); ?>');
        return;
    }
    $(el).closest('.session-row').remove();
}
$(function(){
    $('#add-session-btn').on('click', function(){
        var row = '<div class="row session-row tw-mb-2">'
            + '<div class="col-md-5"><div class="input-group date">'
            + '<input type="text" name="session_date[]" class="form-control datepicker" autocomplete="off" required value="">'
            + '<div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div></div></div>'
            + '<div class="col-md-3"><input type="time" name="session_start_time[]" class="form-control" value=""></div>'
            + '<div class="col-md-3"><input type="time" name="session_end_time[]" class="form-control" value=""></div>'
            + '<div class="col-md-1"><a href="#" class="btn btn-danger" onclick="remove_session_row(this); return false;"><i class="fa fa-trash"></i></a></div>'
            + '</div>';
        $('#sessions-container').append(row);
        init_datepicker();
    });
});
</script>
