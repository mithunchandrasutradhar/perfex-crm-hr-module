<?php defined('BASEPATH') or exit('No direct script access allowed');
$status_badge = ['present'=>'success','absent'=>'danger','late'=>'warning','half_day'=>'info'];
$months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>

  <!-- Filters -->
  <div class="panel_s tw-mb-3">
    <div class="panel-body">
      <?php echo form_open(admin_url('hr_module/reports/attendance'), ['method'=>'get']); ?>
      <div class="row">
        <div class="col-md-2">
          <select name="month" class="form-control input-sm">
            <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?php echo $m; ?>" <?php if(($filters['month']??'') == $m) echo 'selected'; ?>><?php echo $months[$m]; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select name="year" class="form-control input-sm">
            <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
            <option value="<?php echo $y; ?>" <?php if(($filters['year']??'') == $y) echo 'selected'; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select name="department_id" class="form-control input-sm">
            <option value="">All Departments</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select name="status" class="form-control input-sm">
            <option value="">All Status</option>
            <?php foreach(['present','absent','late','half_day'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php if(($filters['status']??'')==$s) echo 'selected'; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button>
        </div>
        <div class="col-md-2">
          <a href="<?php echo admin_url('hr_module/reports/attendance?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default btn-sm btn-block">
            <i class="fa fa-download tw-mr-1"></i>CSV
          </a>
        </div>
      </div>
      <?php echo form_close(); ?>
    </div>
  </div>

  <!-- Summary -->
  <div class="row tw-mb-3">
    <?php
    $cards = [
      ['label'=>'Present',   'val'=>$summary['present'],   'color'=>'#059669'],
      ['label'=>'Absent',    'val'=>$summary['absent'],    'color'=>'#dc2626'],
      ['label'=>'Late',      'val'=>$summary['late'],      'color'=>'#d97706'],
      ['label'=>'Half Day',  'val'=>$summary['half_day'],  'color'=>'#0891b2'],
      ['label'=>'Total Hours','val'=>number_format($summary['total_hours'],1), 'color'=>'#7c3aed'],
    ];
    foreach($cards as $c): ?>
    <div class="col-md-2 col-sm-4">
      <div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid <?php echo $c['color']; ?>;box-shadow:0 1px 3px rgba(0,0,0,.08)">
        <div style="font-size:1.4rem;font-weight:700;color:<?php echo $c['color']; ?>"><?php echo $c['val']; ?></div>
        <div style="font-size:0.78rem;color:#64748b"><?php echo $c['label']; ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Table -->
  <div class="panel_s"><div class="panel-body panel-table-full">
    <table class="table table-hover">
      <thead><tr>
        <th>Date</th><th>Employee</th><th>Department</th>
        <th>Status</th><th>In</th><th>Out</th><th>Hours</th>
      </tr></thead>
      <tbody>
      <?php if(empty($rows)): ?>
      <tr><td colspan="7" class="text-center text-muted" style="padding:30px">No records found.</td></tr>
      <?php else: ?>
      <?php foreach($rows as $r): ?>
      <?php
        $hrs = '';
        if ($r->in_time && $r->out_time) {
            $hrs = number_format((strtotime($r->out_time)-strtotime($r->in_time))/3600, 1).'h';
        }
      ?>
      <tr>
        <td><?php echo date('d M Y', strtotime($r->attendance_date)); ?></td>
        <td><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?><br><small class="text-muted"><?php echo $r->employee_code; ?></small></td>
        <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
        <td><span class="label label-<?php echo $status_badge[$r->status] ?? 'default'; ?>"><?php echo ucfirst(str_replace('_',' ',$r->status)); ?></span></td>
        <td><?php echo $r->in_time ? date('H:i', strtotime($r->in_time)) : '-'; ?></td>
        <td><?php echo $r->out_time ? date('H:i', strtotime($r->out_time)) : '-'; ?></td>
        <td><?php echo $hrs ?: '-'; ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
