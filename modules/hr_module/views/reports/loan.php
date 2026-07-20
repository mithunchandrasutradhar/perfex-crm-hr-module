<?php defined('BASEPATH') or exit('No direct script access allowed');
$sbadge = ['pending'=>'warning','approved'=>'info','active'=>'primary','closed'=>'success','rejected'=>'danger'];
?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>
  <div class="panel_s tw-mb-3"><div class="panel-body">
    <?php echo form_open(admin_url('hr_module/reports/loan'), ['method'=>'get']); ?>
    <div class="row">
      <div class="col-md-3"><select name="department_id" class="form-control input-sm"><option value="">All Departments</option><?php foreach($departments as $d): ?><option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-3"><select name="status" class="form-control input-sm"><option value="">All Status</option><?php foreach(['pending','approved','active','closed','rejected'] as $s): ?><option value="<?php echo $s; ?>" <?php if(($filters['status']??'')==$s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button></div>
      <div class="col-md-2"><a href="<?php echo admin_url('hr_module/reports/loan?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i> CSV</a></div>
    </div>
    <?php echo form_close(); ?>
  </div></div>

  <div class="row tw-mb-3">
    <?php foreach([['Total Loans',count($rows),'#4f46e5'],['Total Amount',number_format($total_amount,2),'#059669'],['Outstanding',number_format($total_outstanding,2),'#dc2626']] as [$label,$val,$color]): ?>
    <div class="col-md-4"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid <?php echo $color; ?>;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.3rem;font-weight:700;color:<?php echo $color; ?>"><?php echo $val; ?></div>
      <div style="font-size:0.78rem;color:#64748b"><?php echo $label; ?></div>
    </div></div>
    <?php endforeach; ?>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <table class="table table-hover">
      <thead><tr><th>Employee</th><th>Department</th><th class="text-right">Amount</th><th class="text-right">Installment/mo</th><th class="text-right">Outstanding</th><th class="text-right">Repaid</th><th>Status</th><th>Approved On</th></tr></thead>
      <tbody>
      <?php if(empty($rows)): ?><tr><td colspan="8" class="text-center text-muted" style="padding:30px">No records.</td></tr>
      <?php else: foreach($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?><br><small class="text-muted"><?php echo $r->employee_code; ?></small></td>
        <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
        <td class="text-right"><?php echo number_format($r->loan_amount,2); ?></td>
        <td class="text-right"><?php echo number_format($r->monthly_installment,2); ?></td>
        <td class="text-right text-danger"><?php echo number_format($r->outstanding,2); ?></td>
        <td class="text-right text-success"><?php echo number_format($r->total_repaid,2); ?></td>
        <td><span class="label label-<?php echo $sbadge[$r->status] ?? 'default'; ?>"><?php echo ucfirst($r->status); ?></span></td>
        <td><?php echo $r->approved_at ? _d($r->approved_at) : '-'; ?></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
