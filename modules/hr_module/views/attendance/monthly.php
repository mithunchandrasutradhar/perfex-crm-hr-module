<?php defined('BASEPATH') or exit('No direct script access allowed');
$month_name = date('F', mktime(0,0,0,$month,1,$year));
$prev_month = $month == 1 ? 12 : $month - 1;
$prev_year  = $month == 1 ? $year - 1 : $year;
$next_month = $month == 12 ? 1 : $month + 1;
$next_year  = $month == 12 ? $year + 1 : $year;
$status_colors = ['present'=>'#22c55e','late'=>'#f59e0b','absent'=>'#ef4444','half_day'=>'#3b82f6'];
$status_labels = ['present'=>'P','late'=>'L','absent'=>'A','half_day'=>'H'];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/attendance'); ?>"><?php echo _l('hr_attendance_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_attendance_monthly'); ?></li>
        </ol>
      </div>
    </div>

    <!-- Controls -->
    <form method="get" class="row tw-mb-4">
      <div class="col-md-4">
        <div class="form-group">
          <label><?php echo _l('hr_employee'); ?></label>
          <select name="employee_id" class="form-control">
            <option value=""><?php echo _l('hr_select'); ?></option>
            <?php foreach ($employees as $id => $name): ?>
            <option value="<?php echo $id; ?>" <?php if($employee_id == $id) echo 'selected'; ?>><?php echo htmlspecialchars($name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-group">
          <label><?php echo _l('hr_attendance_monthly'); ?></label>
          <select name="month" class="form-control">
            <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?php echo $m; ?>" <?php if($month==$m) echo 'selected'; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-group">
          <label><?php echo _l('hr_leave_year'); ?></label>
          <select name="year" class="form-control">
            <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
            <option value="<?php echo $y; ?>" <?php if($year==$y) echo 'selected'; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <div class="form-group"><label>&nbsp;</label>
          <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> View</button>
        </div>
      </div>
    </form>

    <?php if ($employee_id && !empty($summary)): ?>
    <!-- Summary cards -->
    <div class="row tw-mb-4">
      <?php
      $cards = [
        ['label'=>'Present',  'val'=>$summary['present'],  'color'=>'success', 'icon'=>'fa-check-circle'],
        ['label'=>'Late',     'val'=>$summary['late'],     'color'=>'warning', 'icon'=>'fa-clock'],
        ['label'=>'Absent',   'val'=>$summary['absent'],   'color'=>'danger',  'icon'=>'fa-times-circle'],
        ['label'=>'Half Day', 'val'=>$summary['half_day'], 'color'=>'info',    'icon'=>'fa-adjust'],
        ['label'=>'Work Hrs', 'val'=>$summary['total_hours'].'h', 'color'=>'primary', 'icon'=>'fa-hourglass-half'],
      ];
      foreach ($cards as $c): ?>
      <div class="col-md-2 col-sm-4">
        <div class="panel_s" style="border-top:3px solid var(--bs-<?php echo $c['color']; ?>)">
          <div class="panel-body tw-text-center tw-py-3">
            <i class="fa <?php echo $c['icon']; ?> fa-2x text-<?php echo $c['color']; ?> tw-mb-1"></i>
            <div class="tw-text-xl tw-font-bold"><?php echo $c['val']; ?></div>
            <div class="tw-text-xs text-muted"><?php echo $c['label']; ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Calendar grid -->
    <?php if ($employee_id): ?>
    <div class="panel_s">
      <div class="panel-body">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
          <a href="?employee_id=<?php echo $employee_id; ?>&month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-default btn-sm"><i class="fa fa-chevron-left"></i></a>
          <h5 class="tw-font-semibold tw-mb-0"><?php echo $month_name . ' ' . $year; ?></h5>
          <a href="?employee_id=<?php echo $employee_id; ?>&month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-default btn-sm"><i class="fa fa-chevron-right"></i></a>
        </div>

        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px">
          <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
          <div style="text-align:center;font-weight:600;font-size:0.75rem;color:#64748b;padding:4px"><?php echo $d; ?></div>
          <?php endforeach; ?>

          <?php
          $first_day = date('w', mktime(0,0,0,$month,1,$year)); // 0=Sun
          for($i = 0; $i < $first_day; $i++) echo '<div></div>';

          for($day = 1; $day <= $days_in_month; $day++):
            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $rec      = $records[$date_str] ?? null;
            $dow      = date('w', mktime(0,0,0,$month,$day,$year));
            $is_weekend = ($dow == 0 || $dow == 6);
            $bg = '#f8fafc'; $color = '#94a3b8'; $label = '';
            if ($rec) {
                $bg    = $status_colors[$rec->status] ?? '#94a3b8';
                $color = '#fff';
                $label = $status_labels[$rec->status] ?? '?';
                if ($rec->in_time) $label .= '<br><span style="font-size:0.6rem">'.substr($rec->in_time,0,5).'</span>';
            } elseif ($is_weekend) {
                $bg = '#e2e8f0'; $color = '#94a3b8'; $label = 'WO';
            }
          ?>
          <div style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>;border-radius:6px;padding:6px 4px;text-align:center;font-size:0.75rem;font-weight:600;min-height:44px;display:flex;flex-direction:column;align-items:center;justify-content:center;">
            <span style="font-size:0.8rem"><?php echo $day; ?></span>
            <?php if ($label) echo $label; ?>
          </div>
          <?php endfor; ?>
        </div>

        <!-- Legend -->
        <div class="tw-flex tw-gap-4 tw-mt-4 tw-flex-wrap">
          <?php foreach ($status_colors as $s => $c): ?>
          <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem">
            <span style="width:12px;height:12px;background:<?php echo $c; ?>;border-radius:3px;display:inline-block"></span>
            <?php echo ucfirst(str_replace('_',' ',$s)); ?>
          </span>
          <?php endforeach; ?>
          <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem">
            <span style="width:12px;height:12px;background:#e2e8f0;border-radius:3px;display:inline-block"></span> Weekend
          </span>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">Select an employee to view their monthly attendance calendar.</div>
    <?php endif; ?>
  </div>
</div>
<?php init_tail(); ?>
