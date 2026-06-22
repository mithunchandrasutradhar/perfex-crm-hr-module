<?php defined('BASEPATH') or exit('No direct script access allowed');
$sbadge = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','cancelled'=>'default'];
?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>

  <div class="panel_s tw-mb-3"><div class="panel-body">
    <?php echo form_open(admin_url('hr_module/reports/leave'), ['method'=>'get']); ?>
    <div class="row">
      <div class="col-md-2"><input type="date" name="from_date" class="form-control input-sm" value="<?php echo $filters['from_date'] ?? ''; ?>" placeholder="From Date"></div>
      <div class="col-md-2"><input type="date" name="to_date" class="form-control input-sm" value="<?php echo $filters['to_date'] ?? ''; ?>" placeholder="To Date"></div>
      <div class="col-md-2">
        <select name="department_id" class="form-control input-sm">
          <option value="">All Departments</option>
          <?php foreach($departments as $d): ?>
          <option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="leave_type_id" class="form-control input-sm">
          <option value="">All Types</option>
          <?php foreach($leave_types as $lt): ?>
          <option value="<?php echo $lt->id; ?>" <?php if(($filters['leave_type_id']??'')==$lt->id) echo 'selected'; ?>><?php echo htmlspecialchars($lt->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-control input-sm">
          <option value="">All Status</option>
          <?php foreach(['approved','pending','rejected','cancelled'] as $s): ?>
          <option value="<?php echo $s; ?>" <?php if(($filters['status']??'')==$s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1"><button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button></div>
      <div class="col-md-1"><a href="<?php echo admin_url('hr_module/reports/leave?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block"><i class="fa fa-download"></i></a></div>
    </div>
    <?php echo form_close(); ?>
  </div></div>

  <!-- Stats -->
  <?php
  $counts = ['approved'=>0,'pending'=>0,'rejected'=>0,'total_days'=>0];
  foreach($rows as $r) {
    if(isset($counts[$r->status])) $counts[$r->status]++;
    if($r->status === 'approved') $counts['total_days'] += $r->days_requested;
  }
  $stat_cards = [
    ['Approved',$counts['approved'],'#059669'],['Pending',$counts['pending'],'#d97706'],
    ['Rejected',$counts['rejected'],'#dc2626'],['Total Days (Approved)',$counts['total_days'],'#4f46e5'],
    ['Total Records',count($rows),'#0891b2'],
  ];
  ?>
  <div class="row tw-mb-3">
  <?php foreach($stat_cards as [$label,$val,$color]): ?>
  <div class="col-md-2"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid <?php echo $color; ?>;box-shadow:0 1px 3px rgba(0,0,0,.08)">
    <div style="font-size:1.3rem;font-weight:700;color:<?php echo $color; ?>"><?php echo $val; ?></div>
    <div style="font-size:0.78rem;color:#64748b"><?php echo $label; ?></div>
  </div></div>
  <?php endforeach; ?>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <table class="table table-hover">
      <thead><tr><th>Employee</th><th>Department</th><th>Leave Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th></tr></thead>
      <tbody>
      <?php if(empty($rows)): ?><tr><td colspan="7" class="text-center text-muted" style="padding:30px">No records.</td></tr>
      <?php else: foreach($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?><br><small class="text-muted"><?php echo $r->employee_code; ?></small></td>
        <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
        <td><?php echo htmlspecialchars($r->leave_type_name ?? '-'); ?></td>
        <td><?php echo date('d M Y', strtotime($r->from_date)); ?></td>
        <td><?php echo date('d M Y', strtotime($r->to_date)); ?></td>
        <td><?php echo $r->days_requested; ?></td>
        <td><span class="label label-<?php echo $sbadge[$r->status] ?? 'default'; ?>"><?php echo ucfirst($r->status); ?></span></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
