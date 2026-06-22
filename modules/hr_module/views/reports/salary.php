<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>
  <div class="panel_s tw-mb-3"><div class="panel-body">
    <?php echo form_open(admin_url('hr_module/reports/salary'), ['method'=>'get']); ?>
    <div class="row">
      <div class="col-md-3"><select name="department_id" class="form-control input-sm"><option value="">All Departments</option><?php foreach($departments as $d): ?><option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><select name="status" class="form-control input-sm"><option value="">All Status</option><option value="active" <?php if(($filters['status']??'')=='active') echo 'selected'; ?>>Active</option><option value="inactive" <?php if(($filters['status']??'')=='inactive') echo 'selected'; ?>>Inactive</option></select></div>
      <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button></div>
      <div class="col-md-2"><a href="<?php echo admin_url('hr_module/reports/salary?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a></div>
    </div>
    <?php echo form_close(); ?>
  </div></div>

  <?php
  $total_basic = array_sum(array_column((array)$rows,'basic_salary'));
  $total_gross = array_sum(array_column((array)$rows,'gross_salary'));
  $avg_gross   = count($rows) ? $total_gross/count($rows) : 0;
  ?>
  <div class="row tw-mb-3">
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #4f46e5;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#4f46e5"><?php echo number_format($total_basic,2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Basic Salary</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #059669;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#059669"><?php echo number_format($total_gross,2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Gross Salary</div>
    </div></div>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #d97706;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:#d97706"><?php echo number_format($avg_gross,2); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Average Gross Salary</div>
    </div></div>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <h4 class="tw-mb-2">Employee Salary Details</h4>
    <table class="table table-hover">
      <thead><tr>
        <th>Employee</th><th>Department</th><th>Designation</th>
        <th class="text-right">Basic</th><th class="text-right">Allowances</th><th class="text-right">Deductions</th><th class="text-right">Gross</th>
      </tr></thead>
      <tbody>
      <?php if(empty($rows)): ?><tr><td colspan="7" class="text-center text-muted" style="padding:30px">No records.</td></tr>
      <?php else: foreach($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?><br><small class="text-muted"><?php echo $r->employee_code; ?></small></td>
        <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
        <td><?php echo htmlspecialchars($r->designation_name ?? '-'); ?></td>
        <td class="text-right"><?php echo number_format($r->basic_salary,2); ?></td>
        <td class="text-right text-success"><?php echo number_format($r->total_allowances ?? 0,2); ?></td>
        <td class="text-right text-danger"><?php echo number_format($r->total_deductions ?? 0,2); ?></td>
        <td class="text-right"><strong><?php echo number_format($r->gross_salary,2); ?></strong></td>
      </tr>
      <?php endforeach; ?>
      <tr class="active">
        <td colspan="3"><strong>Total</strong></td>
        <td class="text-right"><strong><?php echo number_format($total_basic,2); ?></strong></td>
        <td class="text-right"><strong><?php echo number_format(array_sum(array_column((array)$rows,'total_allowances')),2); ?></strong></td>
        <td class="text-right"><strong><?php echo number_format(array_sum(array_column((array)$rows,'total_deductions')),2); ?></strong></td>
        <td class="text-right"><strong><?php echo number_format($total_gross,2); ?></strong></td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>

  <?php if(!empty($dept_summary)): ?>
  <div class="panel_s"><div class="panel-body panel-table-full">
    <h4 class="tw-mb-2">Department Summary</h4>
    <table class="table table-hover">
      <thead><tr>
        <th>Department</th><th class="text-right">Employees</th>
        <th class="text-right">Avg Salary</th><th class="text-right">Min Salary</th><th class="text-right">Max Salary</th>
      </tr></thead>
      <tbody>
      <?php foreach($dept_summary as $d): ?>
      <tr>
        <td><strong><?php echo htmlspecialchars($d->department_name ?? 'Unassigned'); ?></strong></td>
        <td class="text-right"><?php echo $d->emp_count; ?></td>
        <td class="text-right"><?php echo number_format($d->avg_salary,2); ?></td>
        <td class="text-right"><?php echo number_format($d->min_salary,2); ?></td>
        <td class="text-right"><?php echo number_format($d->max_salary,2); ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
  <?php endif; ?>

</div></div>
</div></div>
<?php init_tail(); ?>
