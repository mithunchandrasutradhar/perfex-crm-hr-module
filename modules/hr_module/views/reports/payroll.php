<?php defined('BASEPATH') or exit('No direct script access allowed');
$sbadge = ['draft'=>'default','approved'=>'info','paid'=>'success'];
$months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>

  <div class="panel_s tw-mb-3"><div class="panel-body">
    <?php echo form_open(admin_url('hr_module/reports/payroll'), ['method'=>'get']); ?>
    <div class="row">
      <div class="col-md-2">
        <select name="month" class="form-control input-sm">
          <option value="">All Months</option>
          <?php for($m=1;$m<=12;$m++): ?>
          <option value="<?php echo $m; ?>" <?php if(($filters['month']??'')==$m) echo 'selected'; ?>><?php echo $months[$m]; ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="year" class="form-control input-sm">
          <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
          <option value="<?php echo $y; ?>" <?php if(($filters['year']??'')==$y) echo 'selected'; ?>><?php echo $y; ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="department_id" class="form-control input-sm">
          <option value="">All Departments</option>
          <?php foreach($departments as $d): ?>
          <option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-control input-sm">
          <option value="">All Status</option>
          <option value="draft" <?php if(($filters['status']??'')=='draft') echo 'selected'; ?>>Draft</option>
          <option value="paid" <?php if(($filters['status']??'')=='paid') echo 'selected'; ?>>Paid</option>
        </select>
      </div>
      <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button></div>
      <div class="col-md-2"><a href="<?php echo admin_url('hr_module/reports/payroll?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download tw-mr-1"></i>CSV</a></div>
    </div>
    <?php echo form_close(); ?>
  </div></div>

  <div class="row tw-mb-3">
    <?php $stat_cards = [
      ['Gross Earnings', number_format($totals['gross'],2), '#059669'],
      ['Total Deductions', number_format($totals['deductions'],2), '#dc2626'],
      ['Net Payroll', number_format($totals['net'],2), '#4f46e5'],
      ['Records', count($rows), '#0891b2'],
    ]; ?>
    <?php foreach($stat_cards as [$label,$val,$color]): ?>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid <?php echo $color; ?>;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:<?php echo $color; ?>"><?php echo $val; ?></div>
      <div style="font-size:0.78rem;color:#64748b"><?php echo $label; ?></div>
    </div></div>
    <?php endforeach; ?>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <table class="table table-hover">
      <thead><tr><th>Employee</th><th>Department</th><th>Period</th><th class="text-right">Basic</th><th class="text-right">Gross</th><th class="text-right">Deductions</th><th class="text-right">Net</th><th>Status</th></tr></thead>
      <tbody>
      <?php if(empty($rows)): ?><tr><td colspan="8" class="text-center text-muted" style="padding:30px">No records.</td></tr>
      <?php else: foreach($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?><br><small class="text-muted"><?php echo $r->employee_code; ?></small></td>
        <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
        <td><?php echo $months[$r->pay_month].' '.$r->pay_year; ?></td>
        <td class="text-right"><?php echo number_format($r->basic_salary, 2); ?></td>
        <td class="text-right"><?php echo number_format($r->gross_earnings, 2); ?></td>
        <td class="text-right text-danger"><?php echo number_format($r->total_deductions, 2); ?></td>
        <td class="text-right"><strong><?php echo number_format($r->net_salary, 2); ?></strong></td>
        <td><span class="label label-<?php echo $sbadge[$r->status] ?? 'default'; ?>"><?php echo ucfirst($r->status); ?></span></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
      <?php if(!empty($rows)): ?>
      <tfoot><tr class="active">
        <td colspan="4"><strong>Totals</strong></td>
        <td class="text-right"><strong><?php echo number_format($totals['gross'],2); ?></strong></td>
        <td class="text-right text-danger"><strong><?php echo number_format($totals['deductions'],2); ?></strong></td>
        <td class="text-right"><strong><?php echo number_format($totals['net'],2); ?></strong></td>
        <td></td>
      </tr></tfoot>
      <?php endif; ?>
    </table>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
