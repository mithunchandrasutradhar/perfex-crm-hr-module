<?php defined('BASEPATH') or exit('No direct script access allowed');
$sbadge = ['pending'=>'default','in_progress'=>'warning','partially_completed'=>'info','completed'=>'success'];
$view_filters = $filters;
unset($view_filters['view']);
if (!function_exists('hr_perf_report_url')) {
    function hr_perf_report_url($view, $filters) {
        $filters['view'] = $view;
        return admin_url('hr_module/reports/performance?' . http_build_query($filters));
    }
}
?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>

  <div class="btn-group tw-mb-3">
    <a href="<?php echo hr_perf_report_url('detailed', $view_filters); ?>" class="btn btn-default <?php echo $view === 'detailed' ? 'active' : ''; ?>">Detailed</a>
    <a href="<?php echo hr_perf_report_url('employee', $view_filters); ?>" class="btn btn-default <?php echo $view === 'employee' ? 'active' : ''; ?>">By Employee</a>
    <a href="<?php echo hr_perf_report_url('department', $view_filters); ?>" class="btn btn-default <?php echo $view === 'department' ? 'active' : ''; ?>">By Department</a>
  </div>

  <div class="panel_s tw-mb-3"><div class="panel-body">
    <?php echo form_open(admin_url('hr_module/reports/performance'), ['method'=>'get']); ?>
    <input type="hidden" name="view" value="<?php echo $view; ?>">
    <div class="row">
      <div class="col-md-2">
        <select name="year" class="selectpicker" data-width="100%">
          <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
          <option value="<?php echo $y; ?>" <?php if(($filters['year']??'')==$y) echo 'selected'; ?>><?php echo $y; ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="department_id" class="selectpicker" data-width="100%" data-live-search="true" data-none-selected-text="All Departments">
          <option value="">All Departments</option>
          <?php foreach($departments as $d): ?>
          <option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="status" class="selectpicker" data-width="100%" data-none-selected-text="All Status">
          <option value="">All Status</option>
          <?php foreach(['pending','in_progress','partially_completed','completed'] as $s): ?>
          <option value="<?php echo $s; ?>" <?php if(($filters['status']??'')==$s) echo 'selected'; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button></div>
      <div class="col-md-2"><a href="<?php echo hr_perf_report_url($view, array_merge($view_filters, ['export'=>'csv'])); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a></div>
    </div>
    <?php echo form_close(); ?>
  </div></div>

  <?php if ($view === 'detailed'): ?>
    <?php $completed = array_filter((array)$rows, fn($r)=>$r->status==='completed'); ?>
    <div class="row tw-mb-3">
      <?php foreach([['Total Sub-Targets',count($rows),'#4f46e5'],['Completed',count($completed),'#059669']] as [$label,$val,$color]): ?>
      <div class="col-md-6"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid <?php echo $color; ?>;box-shadow:0 1px 3px rgba(0,0,0,.08)">
        <div style="font-size:1.3rem;font-weight:700;color:<?php echo $color; ?>"><?php echo $val; ?></div>
        <div style="font-size:0.78rem;color:#64748b"><?php echo $label; ?></div>
      </div></div>
      <?php endforeach; ?>
    </div>

    <div class="panel_s"><div class="panel-body panel-table-full">
      <table class="table table-hover">
        <thead><tr><th>Employee</th><th>Department</th><th>Target</th><th>Sub-Target</th><th>Assigned By</th><th>Evaluators</th><th>Due Date</th><th class="text-right">Completion</th><th>Status</th></tr></thead>
        <tbody>
        <?php if(empty($rows)): ?><tr><td colspan="9" class="text-center text-muted" style="padding:30px">No records.</td></tr>
        <?php else: foreach($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?><br><small class="text-muted"><?php echo $r->employee_code; ?></small></td>
          <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
          <td><?php echo htmlspecialchars($r->target_title); ?></td>
          <td><?php echo htmlspecialchars($r->sub_target_title); ?></td>
          <td><?php echo htmlspecialchars($r->assigned_by_name ?? '-'); ?></td>
          <td><?php echo $r->evaluator_names ? htmlspecialchars($r->evaluator_names) : '-'; ?></td>
          <td><?php echo $r->due_date ? date('d M Y', strtotime($r->due_date)) : '-'; ?></td>
          <td class="text-right"><?php echo $r->completion_percentage !== null ? rtrim(rtrim(number_format($r->completion_percentage,2),'0'),'.').'%' : '-'; ?></td>
          <td><span class="label label-<?php echo $sbadge[$r->status] ?? 'default'; ?>"><?php echo ucfirst(str_replace('_',' ',$r->status)); ?></span></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div></div>

  <?php elseif ($view === 'employee'): ?>
    <div class="panel_s"><div class="panel-body panel-table-full">
      <table class="table table-hover">
        <thead><tr><th>Employee</th><th>Department</th><th class="text-right">Total</th><th class="text-right">Completed</th><th class="text-right">In Progress</th><th class="text-right">Partial</th><th class="text-right">Pending</th><th class="text-right">Avg Completion</th><th class="text-right">Avg Rating</th></tr></thead>
        <tbody>
        <?php if(empty($rows)): ?><tr><td colspan="9" class="text-center text-muted" style="padding:30px">No records.</td></tr>
        <?php else: foreach($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?><br><small class="text-muted"><?php echo $r->employee_code; ?></small></td>
          <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
          <td class="text-right"><?php echo (int) $r->total_sub_targets; ?></td>
          <td class="text-right"><span class="label label-success"><?php echo (int) $r->completed_count; ?></span></td>
          <td class="text-right"><span class="label label-warning"><?php echo (int) $r->in_progress_count; ?></span></td>
          <td class="text-right"><span class="label label-info"><?php echo (int) $r->partial_count; ?></span></td>
          <td class="text-right"><span class="label label-default"><?php echo (int) $r->pending_count; ?></span></td>
          <td class="text-right"><?php echo $r->avg_completion !== null ? round($r->avg_completion,1).'%' : '-'; ?></td>
          <td class="text-right"><?php echo $r->avg_rating !== null ? $r->avg_rating.' / 5' : '-'; ?></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div></div>

  <?php elseif ($view === 'department'): ?>
    <div class="panel_s"><div class="panel-body panel-table-full">
      <table class="table table-hover">
        <thead><tr><th>Department</th><th class="text-right">Total</th><th class="text-right">Completed</th><th class="text-right">In Progress</th><th class="text-right">Partial</th><th class="text-right">Pending</th><th class="text-right">Avg Completion</th><th class="text-right">Avg Rating</th></tr></thead>
        <tbody>
        <?php if(empty($rows)): ?><tr><td colspan="8" class="text-center text-muted" style="padding:30px">No records.</td></tr>
        <?php else: foreach($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
          <td class="text-right"><?php echo (int) $r->total_sub_targets; ?></td>
          <td class="text-right"><span class="label label-success"><?php echo (int) $r->completed_count; ?></span></td>
          <td class="text-right"><span class="label label-warning"><?php echo (int) $r->in_progress_count; ?></span></td>
          <td class="text-right"><span class="label label-info"><?php echo (int) $r->partial_count; ?></span></td>
          <td class="text-right"><span class="label label-default"><?php echo (int) $r->pending_count; ?></span></td>
          <td class="text-right"><?php echo $r->avg_completion !== null ? round($r->avg_completion,1).'%' : '-'; ?></td>
          <td class="text-right"><?php echo $r->avg_rating !== null ? $r->avg_rating.' / 5' : '-'; ?></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div></div>
  <?php endif; ?>

</div></div>
</div></div>
<?php init_tail(); ?>
