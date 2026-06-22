<?php defined('BASEPATH') or exit('No direct script access allowed');
$status_badge = ['present'=>'success','late'=>'warning','absent'=>'danger','half_day'=>'info'];
$totals = ['present'=>0,'late'=>0,'absent'=>0,'half_day'=>0,'hours'=>0];
foreach ($records as $r) {
    if (isset($totals[$r->status])) $totals[$r->status]++;
    $totals['hours'] += (float)$r->working_hours;
}
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/attendance'); ?>"><?php echo _l('hr_attendance_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_attendance_report'); ?></li>
        </ol>
        <!-- Filter form -->
        <form method="get" class="panel_s">
          <div class="panel-body">
            <div class="row">
              <div class="col-md-2">
                <div class="form-group"><label>From Date</label>
                  <input type="date" name="from_date" class="form-control" value="<?php echo $filters['from_date']; ?>"></div>
              </div>
              <div class="col-md-2">
                <div class="form-group"><label>To Date</label>
                  <input type="date" name="to_date" class="form-control" value="<?php echo $filters['to_date']; ?>"></div>
              </div>
              <div class="col-md-3">
                <div class="form-group"><label><?php echo _l('hr_department'); ?></label>
                  <select name="department_id" class="form-control">
                    <option value=""><?php echo _l('hr_all'); ?></option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?php echo $d->id; ?>" <?php if($filters['department_id']==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
                    <?php endforeach; ?>
                  </select></div>
              </div>
              <div class="col-md-3">
                <div class="form-group"><label><?php echo _l('hr_employee'); ?></label>
                  <select name="employee_id" class="form-control">
                    <option value=""><?php echo _l('hr_all'); ?></option>
                    <?php foreach ($employees as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php if($filters['employee_id']==$id) echo 'selected'; ?>><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                  </select></div>
              </div>
              <div class="col-md-2">
                <div class="form-group"><label>&nbsp;</label>
                  <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-filter tw-mr-1"></i>Filter</button>
                </div>
              </div>
            </div>
          </div>
        </form>

        <!-- Summary -->
        <div class="row tw-mb-4">
          <div class="col-md-2 col-sm-4"><div class="panel_s"><div class="panel-body tw-text-center">
            <div class="tw-text-2xl tw-font-bold text-success"><?php echo $totals['present']; ?></div>
            <div class="text-muted">Present</div>
          </div></div></div>
          <div class="col-md-2 col-sm-4"><div class="panel_s"><div class="panel-body tw-text-center">
            <div class="tw-text-2xl tw-font-bold text-warning"><?php echo $totals['late']; ?></div>
            <div class="text-muted">Late</div>
          </div></div></div>
          <div class="col-md-2 col-sm-4"><div class="panel_s"><div class="panel-body tw-text-center">
            <div class="tw-text-2xl tw-font-bold text-danger"><?php echo $totals['absent']; ?></div>
            <div class="text-muted">Absent</div>
          </div></div></div>
          <div class="col-md-2 col-sm-4"><div class="panel_s"><div class="panel-body tw-text-center">
            <div class="tw-text-2xl tw-font-bold text-info"><?php echo $totals['half_day']; ?></div>
            <div class="text-muted">Half Day</div>
          </div></div></div>
          <div class="col-md-2 col-sm-4"><div class="panel_s"><div class="panel-body tw-text-center">
            <div class="tw-text-2xl tw-font-bold text-primary"><?php echo number_format($totals['hours'],1); ?>h</div>
            <div class="text-muted">Total Hours</div>
          </div></div></div>
          <div class="col-md-2 col-sm-4"><div class="panel_s"><div class="panel-body tw-text-center">
            <div class="tw-text-2xl tw-font-bold"><?php echo count($records); ?></div>
            <div class="text-muted">Total Records</div>
          </div></div></div>
        </div>

        <!-- Table -->
        <div class="panel_s">
          <div class="panel-body">
            <div class="table-responsive">
              <table class="table table-hover table-condensed" id="att-report-table">
                <thead><tr>
                  <th><?php echo _l('hr_employee'); ?></th>
                  <th><?php echo _l('hr_department'); ?></th>
                  <th>Date</th>
                  <th>In</th><th>Out</th><th>Hours</th>
                  <th><?php echo _l('hr_status'); ?></th>
                  <th>Source</th>
                </tr></thead>
                <tbody>
                  <?php foreach ($records as $r): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r->employee_name); ?></td>
                    <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
                    <td><?php echo date('D d M Y', strtotime($r->attendance_date)); ?></td>
                    <td><?php echo $r->in_time  ? substr($r->in_time, 0, 5)  : '-'; ?></td>
                    <td><?php echo $r->out_time ? substr($r->out_time, 0, 5) : '-'; ?></td>
                    <td><?php echo $r->working_hours ? $r->working_hours.'h' : '-'; ?></td>
                    <td><span class="label label-<?php echo $status_badge[$r->status] ?? 'default'; ?>">
                      <?php echo ucfirst(str_replace('_',' ',$r->status)); ?></span></td>
                    <td><?php echo ucfirst($r->source); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){ $('#att-report-table').DataTable({order:[[2,'desc'],]}); });
</script>
