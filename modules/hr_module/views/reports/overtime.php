<?php defined('BASEPATH') or exit('No direct script access allowed');
$sbadge = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'];
?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>
  <div class="panel_s tw-mb-3"><div class="panel-body">
    <?php echo form_open(admin_url('hr_module/reports/overtime'), ['method'=>'get']); ?>
    <div class="row">
      <div class="col-md-2"><input type="date" name="from_date" class="form-control input-sm" value="<?php echo $filters['from_date'] ?? ''; ?>"></div>
      <div class="col-md-2"><input type="date" name="to_date"   class="form-control input-sm" value="<?php echo $filters['to_date'] ?? ''; ?>"></div>
      <div class="col-md-2"><select name="department_id" class="form-control input-sm"><option value="">All Departments</option><?php foreach($departments as $d): ?><option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><select name="status" class="form-control input-sm"><option value="">All Status</option><?php foreach(['pending','approved','rejected'] as $s): ?><option value="<?php echo $s; ?>" <?php if(($filters['status']??'')==$s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button></div>
      <div class="col-md-2"><a href="<?php echo admin_url('hr_module/reports/overtime?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a></div>
    </div>
    <?php echo form_close(); ?>
  </div></div>

  <div class="row tw-mb-3">
    <?php foreach([['Total Records',count($rows),'#4f46e5'],['Total Hours',number_format($total_hours,1).'h','#059669'],['Total Amount',number_format($total_amount,2),'#d97706']] as [$label,$val,$color]): ?>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid <?php echo $color; ?>;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:<?php echo $color; ?>"><?php echo $val; ?></div>
      <div style="font-size:0.78rem;color:#64748b"><?php echo $label; ?></div>
    </div></div>
    <?php endforeach; ?>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <table class="table table-hover">
      <thead><tr><th>Employee</th><th>Department</th><th>Date</th><th class="text-right">Hours</th><th>Rate</th><th class="text-right">Amount</th><th>Status</th></tr></thead>
      <tbody>
      <?php if(empty($rows)): ?><tr><td colspan="7" class="text-center text-muted" style="padding:30px">No records.</td></tr>
      <?php else: foreach($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?><br><small class="text-muted"><?php echo $r->employee_code; ?></small></td>
        <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
        <td><?php echo date('d M Y', strtotime($r->overtime_date)); ?></td>
        <td class="text-right"><?php echo $r->hours; ?></td>
        <td><?php echo $r->rate_multiplier; ?>x</td>
        <td class="text-right"><?php echo number_format($r->total_amount,2); ?></td>
        <td><span class="label label-<?php echo $sbadge[$r->status] ?? 'default'; ?>"><?php echo ucfirst($r->status); ?></span></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
