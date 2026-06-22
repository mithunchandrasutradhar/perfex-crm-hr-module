<?php defined('BASEPATH') or exit('No direct script access allowed');
$is_edit  = !empty($review);
$form_url = $is_edit
    ? admin_url('hr_module/performance/edit/' . $review->id)
    : admin_url('hr_module/performance/add');
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-9 col-md-offset-1">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/performance'); ?>"><?php echo _l('hr_performance_list'); ?></a></li>
          <?php if ($is_edit): ?>
          <li><a href="<?php echo admin_url('hr_module/performance/view/'.$review->id); ?>">#<?php echo $review->id; ?></a></li>
          <?php endif; ?>
          <li class="active"><?php echo $is_edit ? _l('hr_performance_edit') : _l('hr_performance_add'); ?></li>
        </ol>
        <?php echo form_open($form_url); ?>
        <!-- Section 1: Review Setup -->
        <div class="panel_s">
          <div class="panel-heading"><h4 class="tw-font-semibold"><?php echo _l('hr_performance_add'); ?></h4></div>
          <div class="panel-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
                  <select name="employee_id" class="form-control" required>
                    <option value=""><?php echo _l('hr_select'); ?></option>
                    <?php foreach ($employees as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php if($is_edit && $review->employee_id==$id) echo 'selected'; ?>>
                      <?php echo htmlspecialchars($name); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label><?php echo _l('hr_performance_reviewer'); ?> <span class="text-danger">*</span></label>
                  <select name="reviewer_id" class="form-control" required>
                    <option value=""><?php echo _l('hr_select'); ?></option>
                    <?php foreach ($reviewers as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php if($is_edit && $review->reviewer_id==$id) echo 'selected'; ?>>
                      <?php echo htmlspecialchars($name); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label><?php echo _l('hr_performance_period'); ?> From <span class="text-danger">*</span></label>
                  <input type="date" name="review_period_from" class="form-control" required
                         value="<?php echo $is_edit ? $review->review_period_from : date('Y-01-01'); ?>">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label><?php echo _l('hr_performance_period'); ?> To <span class="text-danger">*</span></label>
                  <input type="date" name="review_period_to" class="form-control" required
                         value="<?php echo $is_edit ? $review->review_period_to : date('Y-12-31'); ?>">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label><?php echo _l('hr_performance_criteria'); ?></label>
              <textarea name="criteria" class="form-control" rows="4"
                        placeholder="List evaluation criteria, one per line&#10;e.g.&#10;Work Quality&#10;Teamwork&#10;Punctuality&#10;Target Achievement"><?php echo $is_edit ? htmlspecialchars($review->criteria) : ''; ?></textarea>
              <small class="text-muted">List each criterion on a new line. These will be shown as a guide during evaluation.</small>
            </div>
            <div class="form-group">
              <label><?php echo _l('hr_notes'); ?></label>
              <textarea name="notes" class="form-control" rows="2"><?php echo $is_edit ? htmlspecialchars($review->notes) : ''; ?></textarea>
            </div>
          </div>
        </div>

        <!-- Section 2: Self Assessment (only visible when editing) -->
        <?php if ($is_edit): ?>
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="tw-font-semibold"><?php echo _l('hr_performance_self_assessment'); ?>
              <?php if ($review->status === 'pending'): ?>
              <span class="label label-default tw-ml-2" style="font-size:0.7rem">Awaiting</span>
              <?php elseif ($review->self_assessment): ?>
              <span class="label label-success tw-ml-2" style="font-size:0.7rem">Submitted</span>
              <?php endif; ?>
            </h4>
          </div>
          <div class="panel-body">
            <div class="form-group">
              <textarea name="self_assessment" class="form-control" rows="5"
                        placeholder="Employee's self-assessment notes..."><?php echo htmlspecialchars($review->self_assessment ?? ''); ?></textarea>
            </div>
          </div>
        </div>

        <!-- Section 3: Manager Evaluation (only visible when editing) -->
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="tw-font-semibold"><?php echo _l('hr_performance_manager_review'); ?>
              <?php if ($review->status === 'completed'): ?>
              <span class="label label-success tw-ml-2" style="font-size:0.7rem">Completed</span>
              <?php endif; ?>
            </h4>
          </div>
          <div class="panel-body">
            <div class="form-group">
              <label><?php echo _l('hr_performance_manager_review'); ?></label>
              <textarea name="manager_review" class="form-control" rows="5"
                        placeholder="Reviewer's evaluation and feedback..."><?php echo htmlspecialchars($review->manager_review ?? ''); ?></textarea>
            </div>
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label><?php echo _l('hr_performance_final_score'); ?> <small class="text-muted">(0–100)</small></label>
                  <input type="number" step="0.01" min="0" max="100" name="final_score" class="form-control"
                         id="score_input"
                         value="<?php echo $review->final_score !== null ? $review->final_score : ''; ?>"
                         placeholder="Enter score to mark as Completed">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label><?php echo _l('hr_performance_rating'); ?></label>
                  <div id="rating_preview" class="tw-mt-2">
                    <?php if ($review->rating): ?>
                    <span class="label label-info"><?php echo $review->rating; ?></span>
                    <?php endif; ?>
                  </div>
                  <small class="text-muted">Auto-assigned from score</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="tw-flex tw-gap-2 tw-mb-4">
          <button type="submit" class="btn btn-primary"><i class="fa fa-save tw-mr-1"></i><?php echo _l('hr_save'); ?></button>
          <a href="<?php echo $is_edit ? admin_url('hr_module/performance/view/'.$review->id) : admin_url('hr_module/performance'); ?>" class="btn btn-default">Cancel</a>
        </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    var ratings = [[90,'Excellent'],[75,'Very Good'],[60,'Good'],[40,'Average'],[0,'Poor']];
    var colors  = {'Excellent':'success','Very Good':'info','Good':'primary','Average':'warning','Poor':'danger'};
    $('#score_input').on('input', function(){
        var v = parseFloat($(this).val());
        if (isNaN(v)) { $('#rating_preview').html(''); return; }
        var r = 'Poor';
        for (var i=0;i<ratings.length;i++) { if (v>=ratings[i][0]) { r=ratings[i][1]; break; } }
        $('#rating_preview').html('<span class="label label-'+colors[r]+'">'+r+'</span>');
    });
});
</script>
