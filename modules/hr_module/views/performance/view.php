<?php defined('BASEPATH') or exit('No direct script access allowed');
$badge = ['pending'=>'default','in_progress'=>'warning','partially_completed'=>'info','completed'=>'success'];
$rating_color = ['Excellent'=>'success','Very Good'=>'info','Good'=>'primary','Average'=>'warning','Poor'=>'danger'];
$status_labels = [
    'pending'              => _l('hr_performance_status_pending'),
    'in_progress'          => _l('hr_performance_status_in_progress'),
    'partially_completed'  => _l('hr_performance_status_partial'),
    'completed'            => _l('hr_performance_status_completed'),
];
$can_assign = staff_can('create', 'hr_performance') || staff_can('edit', 'hr_performance');
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/performance'); ?>"><?php echo _l('hr_performance_list'); ?></a></li>
          <li class="active">#<?php echo $target->id; ?></li>
        </ol>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8">

        <!-- Target header -->
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
              <div>
                <h4 class="tw-font-bold tw-mb-1"><?php echo htmlspecialchars($target->title); ?></h4>
                <p class="text-muted tw-mb-1">
                  <a href="<?php echo admin_url('hr_module/employees/view/'.$target->employee_id); ?>">
                    <strong><?php echo htmlspecialchars($target->first_name.' '.$target->last_name); ?></strong>
                  </a>
                  &nbsp;<span class="label label-default"><?php echo $target->employee_code; ?></span>
                </p>
                <p class="text-muted tw-mb-0">
                  <?php echo htmlspecialchars($target->department_name ?? ''); ?>
                  <?php if($target->designation_name): ?> &middot; <?php echo htmlspecialchars($target->designation_name); ?><?php endif; ?>
                </p>
              </div>
              <div class="tw-text-right tw-text-sm text-muted">
                <?php if ($target->due_date): ?>
                <div><?php echo _l('hr_performance_due_date'); ?>: <?php echo date('d M Y', strtotime($target->due_date)); ?></div>
                <?php endif; ?>
                <div><?php echo _l('hr_performance_assigned_by'); ?>: <?php echo htmlspecialchars($target->assigned_by_name ?? '-'); ?></div>
              </div>
            </div>
            <?php if ($target->description): ?>
            <p><?php echo nl2br(htmlspecialchars($target->description)); ?></p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Sub-Targets -->
        <h5 class="tw-font-semibold tw-mb-2"><?php echo _l('hr_performance_sub_targets'); ?></h5>
        <?php if (empty($sub_targets)): ?>
        <div class="alert alert-info"><?php echo _l('hr_performance_no_sub_targets'); ?></div>
        <?php endif; ?>

        <?php foreach ($sub_targets as $st): ?>
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-start tw-mb-2">
              <div>
                <strong><?php echo htmlspecialchars($st->title); ?></strong>
                <?php if ($st->due_date): ?><span class="text-muted tw-text-xs tw-ml-2"><?php echo date('d M Y', strtotime($st->due_date)); ?></span><?php endif; ?>
              </div>
              <div class="tw-text-right">
                <span class="label label-<?php echo $badge[$st->status] ?? 'default'; ?>">
                  <?php echo $status_labels[$st->status] ?? ucfirst($st->status); ?>
                </span>
                <?php if ($st->status === 'partially_completed' && $st->completion_percentage !== null): ?>
                <br><span class="label label-default tw-mt-1 tw-inline-block"><?php echo rtrim(rtrim(number_format($st->completion_percentage, 2), '0'), '.'); ?>%</span>
                <?php endif; ?>
              </div>
            </div>

            <?php if ($st->description): ?>
            <p class="tw-text-sm"><?php echo nl2br(htmlspecialchars($st->description)); ?></p>
            <?php endif; ?>

            <?php if ($st->employee_note): ?>
            <p class="tw-text-sm text-muted"><strong><?php echo _l('hr_performance_employee_note'); ?>:</strong> <?php echo nl2br(htmlspecialchars($st->employee_note)); ?></p>
            <?php endif; ?>

            <div class="tw-mb-2">
              <?php foreach ($st->evaluators as $ev): ?>
              <span class="label label-default tw-mr-1 tw-mb-1 tw-inline-block"><?php echo htmlspecialchars($ev->name); ?></span>
              <?php endforeach; ?>
              <?php if (empty($st->evaluators)): ?><span class="text-muted tw-text-xs"><?php echo _l('hr_performance_no_evaluators'); ?></span><?php endif; ?>
            </div>

            <?php if ($can_edit_details): ?>
            <div class="tw-mb-2">
              <a href="<?php echo admin_url('hr_module/performance/edit_sub_target/'.$st->id); ?>" class="tw-text-xs"><i class="fa fa-pencil-alt tw-mr-1"></i><?php echo _l('hr_edit'); ?></a>
              <?php if ($st->status === 'pending'): ?>
              &nbsp;|&nbsp;<a href="<?php echo admin_url('hr_module/performance/delete_sub_target/'.$st->id); ?>" class="tw-text-xs text-danger _delete"><i class="fa fa-trash tw-mr-1"></i><?php echo _l('hr_delete'); ?></a>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($st->can_change_status): ?>
            <?php echo form_open(admin_url('hr_module/performance/update_status/'.$st->id), ['class' => 'st-status-form', 'data-id' => $st->id]); ?>
              <input type="hidden" name="status" class="st-status-input" value="<?php echo $st->status; ?>">
              <div class="tw-flex tw-flex-wrap tw-gap-2 tw-mb-2">
                <button type="button" class="btn btn-default btn-xs st-status-btn" data-status="pending"><?php echo _l('hr_performance_status_pending'); ?></button>
                <button type="button" class="btn btn-warning btn-xs st-status-btn" data-status="in_progress"><?php echo _l('hr_performance_status_in_progress'); ?></button>
                <button type="button" class="btn btn-info btn-xs st-status-btn" data-status="partially_completed"><?php echo _l('hr_performance_status_partial'); ?></button>
                <button type="button" class="btn btn-success btn-xs st-status-btn" data-status="completed"><?php echo _l('hr_performance_status_completed'); ?></button>
              </div>
              <div class="st-partial-fields row" style="display:none">
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_performance_completion_percentage'); ?></label>
                    <input type="number" step="0.01" min="0" max="100" name="completion_percentage" class="form-control"
                           value="<?php echo $st->completion_percentage !== null ? $st->completion_percentage : ''; ?>">
                  </div>
                </div>
              </div>
              <div class="form-group">
                <textarea name="employee_note" class="form-control" rows="2"
                          placeholder="<?php echo _l('hr_performance_employee_note'); ?>"><?php echo htmlspecialchars($st->employee_note ?? ''); ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-xs"><?php echo _l('hr_save'); ?></button>
            <?php echo form_close(); ?>
            <?php endif; ?>

            <?php if ($st->feedback): ?>
            <hr>
            <?php foreach ($st->feedback as $f): ?>
            <div class="tw-mb-2 tw-pb-2" style="border-bottom:1px solid #eee">
              <div class="tw-flex tw-justify-between">
                <strong class="tw-text-sm"><?php echo htmlspecialchars($f->evaluator_name ?? '-'); ?></strong>
                <?php if ($f->rating): ?><span class="label label-<?php echo $rating_color[$f->rating] ?? 'default'; ?>"><?php echo htmlspecialchars($f->rating); ?></span><?php endif; ?>
              </div>
              <p class="tw-mb-1 tw-text-sm"><?php echo nl2br(htmlspecialchars($f->feedback)); ?></p>
              <small class="text-muted"><?php echo date('d M Y H:i', strtotime($f->created_at)); ?></small>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($st->is_evaluator || $can_edit_details): ?>
            <hr>
            <?php echo form_open(admin_url('hr_module/performance/add_feedback/'.$st->id)); ?>
              <div class="form-group">
                <textarea name="feedback" class="form-control" rows="2" required
                          placeholder="<?php echo _l('hr_performance_add_feedback'); ?>..."></textarea>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group select-placeholder">
                    <select name="rating" class="selectpicker" data-width="100%">
                      <option value="">-- <?php echo _l('hr_performance_rating'); ?> --</option>
                      <option value="Excellent">Excellent</option>
                      <option value="Very Good">Very Good</option>
                      <option value="Good">Good</option>
                      <option value="Average">Average</option>
                      <option value="Poor">Poor</option>
                    </select>
                  </div>
                </div>
              </div>
              <button type="submit" class="btn btn-default btn-xs tw-mt-2"><i class="fa fa-comment tw-mr-1"></i><?php echo _l('hr_performance_add_feedback'); ?></button>
            <?php echo form_close(); ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if ($can_edit_details): ?>
        <a href="<?php echo admin_url('hr_module/performance/add_sub_target/'.$target->id); ?>" class="btn btn-default btn-sm">
          <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_performance_add_sub_target'); ?>
        </a>
        <?php endif; ?>

      </div>

      <!-- Sidebar -->
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold tw-mb-3">Actions</h5>
            <?php if ($can_edit_details): ?>
            <a href="<?php echo admin_url('hr_module/performance/edit/'.$target->id); ?>" class="btn btn-default btn-block tw-mb-2">
              <i class="fa fa-pencil-alt tw-mr-1"></i><?php echo _l('hr_performance_edit'); ?>
            </a>
            <?php endif; ?>
            <?php if ($can_delete): ?>
            <a href="<?php echo admin_url('hr_module/performance/delete/'.$target->id); ?>" class="btn btn-default btn-block _delete">
              <i class="fa fa-trash tw-mr-1"></i>Delete
            </a>
            <?php endif; ?>
            <a href="<?php echo admin_url('hr_module/performance'); ?>" class="btn btn-default btn-block tw-mt-2">
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
    function syncPartialVisibility($form){
        var status = $form.find('.st-status-input').val();
        $form.find('.st-partial-fields').toggle(status === 'partially_completed');
    }
    $('.st-status-form').each(function(){ syncPartialVisibility($(this)); });

    $('.st-status-btn').on('click', function(){
        var $form = $(this).closest('form');
        $form.find('.st-status-input').val($(this).data('status'));
        $form.find('.st-status-btn').removeClass('active');
        $(this).addClass('active');
        syncPartialVisibility($form);
    });

    $('.st-status-form').on('submit', function(e){
        var $form = $(this);
        if ($form.find('.st-status-input').val() === 'partially_completed') {
            var pct = parseFloat($form.find('[name="completion_percentage"]').val());
            if (isNaN(pct) || pct < 0 || pct > 100) {
                e.preventDefault();
                alert_float('danger', '<?php echo _l('hr_performance_invalid_percentage'); ?>');
            }
        }
    });
});
</script>
