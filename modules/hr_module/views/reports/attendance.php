<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>

  <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
    <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo $title; ?></h4>
    <?php echo form_open(admin_url('hr_module/reports/attendance'), ['method' => 'get', 'class' => 'tw-flex tw-flex-wrap tw-gap-2 tw-items-center']); ?>
      <div class="input-group date" style="width:150px">
        <input type="text" name="from_date" class="form-control datepicker" autocomplete="off"
               value="<?php echo _d($filters['from_date']); ?>" placeholder="From date">
        <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
      </div>
      <div class="input-group date" style="width:150px">
        <input type="text" name="to_date" class="form-control datepicker" autocomplete="off"
               value="<?php echo _d($filters['to_date']); ?>" placeholder="To date">
        <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
      </div>
      <select name="department_id" class="selectpicker" data-width="200px">
        <option value=""><?php echo _l('hr_all') . ' Dept'; ?></option>
        <?php foreach ($departments as $d): ?>
        <option value="<?php echo $d->id; ?>" <?php if(($filters['department_id']??'')==$d->id) echo 'selected'; ?>><?php echo htmlspecialchars($d->name); ?></option>
        <?php endforeach; ?>
      </select>
      <select name="employee_id" class="selectpicker" data-width="220px" data-live-search="true"
              data-none-selected-text="<?php echo _l('hr_all') . ' Employees'; ?>">
        <option value="">All Employees</option>
        <?php foreach ($employees as $id => $name): ?>
        <option value="<?php echo $id; ?>" <?php if(($filters['employee_id']??'')==$id) echo 'selected'; ?>><?php echo htmlspecialchars($name); ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-filter tw-mr-1"></i><?php echo _l('hr_filter'); ?>
      </button>
      <a href="<?php echo admin_url('hr_module/reports/attendance?'.http_build_query($filters).'&export=csv'); ?>" class="btn btn-default">
        <i class="fa-solid fa-download tw-mr-1"></i>CSV
      </a>
    <?php echo form_close(); ?>
  </div>

  <!-- Table -->
  <div class="panel_s"><div class="panel-body panel-table-full">
    <table class="table table-hover">
      <thead><tr>
        <th>Employee</th><th>Employee Code</th><th>Department</th>
        <th>Present</th><th>Late</th><th>Absent</th>
      </tr></thead>
      <tbody>
      <?php if(empty($rows)): ?>
      <tr><td colspan="6" class="text-center text-muted" style="padding:30px">No employees found.</td></tr>
      <?php else: ?>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r->first_name.' '.$r->last_name); ?></td>
        <td><?php echo htmlspecialchars($r->employee_code); ?></td>
        <td><?php echo htmlspecialchars($r->department_name ?? '-'); ?></td>
        <td><span class="label label-success"><i class="fa fa-check tw-mr-1"></i><?php echo $r->present; ?></span></td>
        <td><span class="label label-warning"><i class="fa fa-clock tw-mr-1"></i><?php echo $r->late; ?></span></td>
        <td><span class="label label-danger"><i class="fa fa-xmark tw-mr-1"></i><?php echo $r->absent; ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
