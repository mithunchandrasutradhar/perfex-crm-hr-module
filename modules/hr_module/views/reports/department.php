<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>
  <div class="panel_s tw-mb-3"><div class="panel-body">
    <?php echo form_open(admin_url('hr_module/reports/department'), ['method'=>'get']); ?>
    <div class="row">
      <div class="col-md-3"><select name="department_id" class="form-control input-sm" required>
        <option value="">Select Department</option>
        <?php foreach($departments as $d): ?>
        <option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
        <?php endforeach; ?>
      </select></div>
      <div class="col-md-2"><select name="year" class="form-control input-sm">
        <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
        <option value="<?php echo $y; ?>" <?php if(($filters['year']??date('Y'))==$y) echo 'selected'; ?>><?php echo $y; ?></option>
        <?php endfor; ?>
      </select></div>
      <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm btn-block">View Report</button></div>
      <?php if(!empty($rows)): ?>
      <div class="col-md-2"><a href="<?php echo admin_url('hr_module/reports/department?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a></div>
      <?php endif; ?>
    </div>
    <?php echo form_close(); ?>
  </div></div>

  <?php if(!empty($rows)): ?>

  <?php
  $total_leave  = array_sum(array_column((array)$rows,'total_leave_days'));
  $total_salary = array_sum(array_column((array)$rows,'total_salary'));
  $dept_name    = htmlspecialchars($rows[0]->department_name ?? '');
  ?>
  <div class="row tw-mb-3">
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #4f46e5;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#4f46e5"><?php echo count($rows); ?></div>
      <div style="font-size:0.78rem;color:#64748b"><?php echo $dept_name; ?> — Employees</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #059669;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#059669"><?php echo $total_leave; ?> days</div>
      <div style="font-size:0.78rem;color:#64748b">Total Leave Days (<?php echo $filters['year']??date('Y'); ?>)</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #d97706;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#d97706"><?php echo number_format($total_salary,2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Payroll (<?php echo $filters['year']??date('Y'); ?>)</div>
    </div></div>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <table class="table table-hover">
      <thead><tr>
        <th>Employee</th><th>Designation</th><th>Employment</th>
        <th>Join Date</th><th class="text-right">Leave Days</th><th class="text-right">Total Salary</th>
      </tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td>
          <a href="<?php echo admin_url('hr_module/employees/view/'.$r->id); ?>"><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?></a>
          <br><small class="text-muted"><?php echo $r->employee_code; ?></small>
        </td>
        <td><?php echo htmlspecialchars($r->designation_name ?? '-'); ?></td>
        <td><?php echo ucfirst(str_replace('_',' ', $r->employment_type ?? '-')); ?></td>
        <td><?php echo $r->hire_date ? date('d M Y', strtotime($r->hire_date)) : '-'; ?></td>
        <td class="text-right"><?php echo $r->total_leave_days ?? 0; ?></td>
        <td class="text-right"><?php echo number_format($r->total_salary ?? 0, 2); ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="active">
        <td colspan="4"><strong>Total</strong></td>
        <td class="text-right"><strong><?php echo $total_leave; ?></strong></td>
        <td class="text-right"><strong><?php echo number_format($total_salary,2); ?></strong></td>
      </tr>
      </tbody>
    </table>
  </div></div>

  <?php elseif(isset($filters['department_id']) && $filters['department_id']): ?>
  <div class="alert alert-info">No employees found for the selected department and year.</div>
  <?php else: ?>
  <div class="panel_s"><div class="panel-body text-center text-muted" style="padding:40px">
    <i class="fa fa-building-o fa-3x" style="opacity:.3"></i>
    <p class="tw-mt-3">Select a department above to view the report.</p>
  </div></div>
  <?php endif; ?>

</div></div>
</div></div>
<?php init_tail(); ?>
