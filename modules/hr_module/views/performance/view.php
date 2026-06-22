<?php defined('BASEPATH') or exit('No direct script access allowed');
$badge = ['pending'=>'default','in_progress'=>'warning','completed'=>'success'];
$rating_color = ['Excellent'=>'success','Very Good'=>'info','Good'=>'primary','Average'=>'warning','Poor'=>'danger'];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/performance'); ?>"><?php echo _l('hr_performance_list'); ?></a></li>
          <li class="active">#<?php echo $review->id; ?></li>
        </ol>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8">

        <!-- Header card -->
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
              <div>
                <h4 class="tw-font-bold tw-mb-1"><?php echo _l('hr_performance_view'); ?></h4>
                <p class="text-muted tw-mb-1">
                  <a href="<?php echo admin_url('hr_module/employees/view/'.$review->employee_id); ?>">
                    <strong><?php echo htmlspecialchars($review->first_name.' '.$review->last_name); ?></strong>
                  </a>
                  &nbsp;<span class="label label-default"><?php echo $review->employee_code; ?></span>
                </p>
                <p class="text-muted tw-mb-0">
                  <?php echo htmlspecialchars($review->department_name ?? ''); ?>
                  <?php if($review->designation_name): ?> &middot; <?php echo htmlspecialchars($review->designation_name); ?><?php endif; ?>
                </p>
              </div>
              <div class="tw-text-right">
                <span class="label label-<?php echo $badge[$review->status] ?? 'default'; ?> tw-text-sm">
                  <?php echo ucfirst(str_replace('_',' ',$review->status)); ?>
                </span>
                <?php if ($review->rating): ?>
                <br><span class="label label-<?php echo $rating_color[$review->rating] ?? 'default'; ?> tw-mt-1 tw-inline-block">
                  <?php echo $review->rating; ?>
                </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 col-sm-6"><div class="panel_s" style="background:#f8fafc"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-text-sm tw-font-semibold"><?php echo date('d M Y',strtotime($review->review_period_from)); ?></div>
                <div class="tw-text-xs text-muted">Period Start</div>
              </div></div></div>
              <div class="col-md-4 col-sm-6"><div class="panel_s" style="background:#f8fafc"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-text-sm tw-font-semibold"><?php echo date('d M Y',strtotime($review->review_period_to)); ?></div>
                <div class="tw-text-xs text-muted">Period End</div>
              </div></div></div>
              <?php if ($review->final_score !== null): ?>
              <div class="col-md-4 col-sm-6"><div class="panel_s" style="background:#eff6ff"><div class="panel-body tw-text-center tw-py-2">
                <div class="tw-text-2xl tw-font-bold text-primary"><?php echo $review->final_score; ?>%</div>
                <div class="tw-text-xs text-muted">Final Score</div>
              </div></div></div>
              <?php endif; ?>
            </div>

            <p class="text-muted tw-text-sm">Reviewer: <strong><?php echo htmlspecialchars($review->reviewer_name ?? '-'); ?></strong></p>
          </div>
        </div>

        <!-- Criteria -->
        <?php if ($review->criteria): ?>
        <div class="panel_s">
          <div class="panel-heading"><h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_performance_criteria'); ?></h5></div>
          <div class="panel-body">
            <ul class="tw-pl-5">
              <?php foreach (explode("\n", trim($review->criteria)) as $c): ?>
              <?php if (trim($c)): ?><li><?php echo htmlspecialchars(trim($c)); ?></li><?php endif; ?>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <?php endif; ?>

        <!-- Self Assessment -->
        <?php if ($review->self_assessment): ?>
        <div class="panel_s">
          <div class="panel-heading"><h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_performance_self_assessment'); ?></h5></div>
          <div class="panel-body">
            <p><?php echo nl2br(htmlspecialchars($review->self_assessment)); ?></p>
          </div>
        </div>
        <?php elseif ($review->status === 'pending'): ?>
        <div class="alert alert-info"><i class="fa fa-info-circle tw-mr-1"></i>Awaiting employee self-assessment.</div>
        <?php endif; ?>

        <!-- Manager Review -->
        <?php if ($review->manager_review): ?>
        <div class="panel_s">
          <div class="panel-heading"><h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_performance_manager_review'); ?></h5></div>
          <div class="panel-body">
            <p><?php echo nl2br(htmlspecialchars($review->manager_review)); ?></p>
            <?php if ($review->final_score !== null): ?>
            <hr>
            <div class="tw-flex tw-items-center tw-gap-4">
              <div>
                <span class="tw-text-sm text-muted">Final Score</span><br>
                <strong class="tw-text-xl text-primary"><?php echo $review->final_score; ?>%</strong>
              </div>
              <?php if ($review->rating): ?>
              <div>
                <span class="tw-text-sm text-muted">Rating</span><br>
                <span class="label label-<?php echo $rating_color[$review->rating] ?? 'default'; ?> tw-text-sm"><?php echo $review->rating; ?></span>
              </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($review->notes): ?>
        <div class="panel_s">
          <div class="panel-heading"><h5 class="tw-font-semibold tw-mb-0"><?php echo _l('hr_notes'); ?></h5></div>
          <div class="panel-body"><p class="text-muted"><?php echo nl2br(htmlspecialchars($review->notes)); ?></p></div>
        </div>
        <?php endif; ?>

      </div>

      <!-- Sidebar -->
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold tw-mb-3">Actions</h5>
            <?php if (staff_can('edit','hr_performance')): ?>
            <a href="<?php echo admin_url('hr_module/performance/edit/'.$review->id); ?>" class="btn btn-primary btn-block tw-mb-2">
              <i class="fa fa-pencil-alt tw-mr-1"></i>
              <?php echo $review->status === 'pending' ? 'Submit Assessment / Evaluate' : _l('hr_performance_edit'); ?>
            </a>
            <?php endif; ?>
            <?php if (staff_can('delete','hr_performance')): ?>
            <a href="<?php echo admin_url('hr_module/performance/delete/'.$review->id); ?>" class="btn btn-default btn-block _delete">
              <i class="fa fa-trash tw-mr-1"></i>Delete
            </a>
            <?php endif; ?>
            <a href="<?php echo admin_url('hr_module/performance'); ?>" class="btn btn-default btn-block tw-mt-2">
              <i class="fa fa-arrow-left tw-mr-1"></i>Back to List
            </a>
          </div>
        </div>

        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold">Review Timeline</h5>
            <div class="timeline-simple">
              <div class="tw-flex tw-items-start tw-gap-2 tw-mb-3">
                <i class="fa fa-circle text-success tw-mt-1"></i>
                <div><div class="tw-text-sm tw-font-semibold">Created</div>
                <div class="tw-text-xs text-muted"><?php echo date('d M Y H:i', strtotime($review->created_at)); ?>
                  <?php if ($review->created_by_name): ?> by <?php echo htmlspecialchars($review->created_by_name); ?><?php endif; ?>
                </div></div>
              </div>
              <?php if ($review->self_assessment): ?>
              <div class="tw-flex tw-items-start tw-gap-2 tw-mb-3">
                <i class="fa fa-circle text-info tw-mt-1"></i>
                <div><div class="tw-text-sm tw-font-semibold">Self-Assessment Submitted</div></div>
              </div>
              <?php endif; ?>
              <?php if ($review->status === 'completed'): ?>
              <div class="tw-flex tw-items-start tw-gap-2 tw-mb-3">
                <i class="fa fa-circle text-warning tw-mt-1"></i>
                <div><div class="tw-text-sm tw-font-semibold">Evaluation Completed</div>
                <div class="tw-text-xs text-muted">Score: <?php echo $review->final_score; ?>% — <?php echo $review->rating; ?></div></div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
