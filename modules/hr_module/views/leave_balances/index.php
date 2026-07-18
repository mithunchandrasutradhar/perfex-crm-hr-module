<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <div class="tw-flex tw-items-center tw-gap-3">
            <a href="<?php echo admin_url('hr_module/leave'); ?>" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i></a>
            <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700 tw-mb-0"><?php echo _l('hr_leave_balances_list'); ?> — <?php echo $year; ?></h4>
          </div>
          <div class="tw-flex tw-gap-2">
            <!-- Year filter -->
            <form method="get" class="tw-flex tw-gap-2">
              <select name="dept_id" class="selectpicker" data-width="160px">
                <option value=""><?php echo _l('hr_all') . ' Dept'; ?></option>
                <?php foreach ($departments as $d): ?>
                <option value="<?php echo $d->id; ?>" <?php if($this->input->get('dept_id') == $d->id) echo 'selected'; ?>>
                  <?php echo htmlspecialchars($d->name); ?>
                </option>
                <?php endforeach; ?>
              </select>
              <select name="year" class="selectpicker" data-width="90px">
                <?php for($y = date('Y'); $y >= date('Y')-3; $y--): ?>
                <option value="<?php echo $y; ?>" <?php if($year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
              </select>
              <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-filter"></i></button>
            </form>
            <?php if (is_admin() || staff_can('approve', 'hr_leave')): ?>
            <form method="post" action="<?php echo admin_url('hr_module/leave_balances/allocate'); ?>" onsubmit="return confirm('Allocate leave balances for <?php echo $year; ?>?')">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <input type="hidden" name="year" value="<?php echo $year; ?>">
              <button type="submit" class="btn btn-success btn-sm"><i class="fa fa-magic tw-mr-1"></i>Allocate <?php echo $year; ?></button>
            </form>
            <?php endif; ?>
          </div>
        </div>

        <div class="panel_s">
          <div class="panel-body">
            <?php if (empty($balances)): ?>
            <div class="alert alert-info">No leave balances found. Use the "Allocate" button to generate balances for employees.</div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover table-condensed">
                <thead>
                  <tr>
                    <th><?php echo _l('hr_employee'); ?></th>
                    <th><?php echo _l('hr_department'); ?></th>
                    <th><?php echo _l('hr_leave_type'); ?></th>
                    <th><?php echo _l('hr_leave_allocated'); ?></th>
                    <th><?php echo _l('hr_leave_used'); ?></th>
                    <th><?php echo _l('hr_leave_remaining'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($balances as $b):
                    $total     = $b->allocated_days + $b->carry_forward_days;
                    $remaining = $total - $b->used_days;
                    $pct       = $total > 0 ? min(100, round($b->used_days / $total * 100)) : 0;
                    $color     = $pct >= 90 ? 'danger' : ($pct >= 60 ? 'warning' : 'success');
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($b->employee_name); ?><br><small class="text-muted"><?php echo $b->employee_code; ?></small></td>
                    <td><?php echo htmlspecialchars($b->department_name ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($b->leave_type_name); ?></td>
                    <td><?php echo $total; ?><?php if($b->carry_forward_days > 0) echo ' <small class="text-muted">(+' . $b->carry_forward_days . ' CF)</small>'; ?></td>
                    <td><?php echo $b->used_days; ?></td>
                    <td>
                      <strong class="text-<?php echo $color; ?>"><?php echo $remaining; ?></strong>
                      <div class="progress tw-my-0 progress-bar-mini">
                        <div class="progress-bar progress-bar-<?php echo $color; ?> no-percent-text not-dynamic" role="progressbar"
                             aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"
                             style="width: <?php echo $pct; ?>%" data-percent="<?php echo $pct; ?>"></div>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
