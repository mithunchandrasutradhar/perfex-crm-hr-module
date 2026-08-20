<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/** @var array $departments */
/** @var array $employees   */
/** @var bool  $is_global   */
if (!isset($departments)) $departments = [];
if (!isset($employees))   $employees   = [];
if (!isset($is_global))   $is_global   = is_admin() || staff_can('view', 'hr_attendance');
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_attendance_list'); ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <!-- Filters -->
            <?php if ($is_global): ?>
            <select id="f-dept" class="selectpicker" data-width="260px">
              <option value=""><?php echo _l('hr_all') . ' Dept'; ?></option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="f-emp" class="selectpicker" data-width="220px" data-live-search="true"
                    data-none-selected-text="<?php echo _l('hr_employee'); ?>">
              <option value=""><?php echo _l('hr_all') . ' Employees'; ?></option>
              <?php foreach ($employees as $id => $name): ?>
              <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
              <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <div class="input-group date" style="width:150px">
              <input type="text" id="f-from" class="form-control datepicker" autocomplete="off" placeholder="From date">
              <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
            </div>
            <div class="input-group date" style="width:150px">
              <input type="text" id="f-to" class="form-control datepicker" autocomplete="off" placeholder="To date">
              <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
            </div>
            <select id="f-status" class="selectpicker" data-width="130px">
              <option value=""><?php echo _l('hr_all'); ?> Status</option>
              <option value="present">Present</option>
              <option value="late">Late</option>
              <option value="absent">Absent</option>
              <option value="half_day">Half Day</option>
            </select>
            <?php if (staff_can('create', 'hr_attendance')): ?>
            <button class="btn btn-primary" id="btn-add-att">
              <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_attendance_add'); ?>
            </button>
            <?php endif; ?>
            <?php if ($is_global): ?>
            <a href="<?php echo admin_url('hr_module/attendance/monthly'); ?>" class="btn btn-default btn-sm">
              <i class="fa-regular fa-calendar tw-mr-1"></i><?php echo _l('hr_attendance_monthly'); ?>
            </a>
            <a href="<?php echo admin_url('hr_module/attendance/import'); ?>" class="btn btn-default btn-sm">
              <i class="fa-solid fa-upload tw-mr-1"></i><?php echo _l('hr_attendance_import'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_employee'), _l('hr_department'),
              _l('hr_attendance_date'), _l('hr_attendance_in_time'), _l('hr_attendance_out_time'),
              _l('hr_attendance_working_hours'), _l('hr_status'), 'Source', _l('hr_attendance_view_log'),
            ], 'hr-attendance'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Attendance Modal — only rendered for users who can create attendance records -->
<?php if (staff_can('create', 'hr_attendance')): ?>
<div class="modal fade" id="attModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title" id="att-modal-title"><?php echo _l('hr_attendance_add'); ?></h4>
    </div>
    <form id="attForm">
      <div class="modal-body">
        <input type="hidden" id="att_id">
        <div class="row">
          <div class="col-md-8">
            <div class="form-group select-placeholder">
              <label><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
              <select name="employee_id" id="att_emp" class="selectpicker" data-width="100%" data-live-search="true"
                      data-none-selected-text="<?php echo _l('hr_select'); ?>" required>
                <option value=""><?php echo _l('hr_select'); ?></option>
                <?php foreach ($employees as $id => $name): ?>
                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?php echo _l('hr_attendance_date'); ?> <span class="text-danger">*</span></label>
              <div class="input-group date">
                <input type="text" name="attendance_date" id="att_date" class="form-control datepicker" autocomplete="off" required value="<?php echo _d(date('Y-m-d')); ?>">
                <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label><?php echo _l('hr_attendance_in_time'); ?></label>
              <input type="time" name="in_time" id="att_in" class="form-control">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label><?php echo _l('hr_attendance_out_time'); ?></label>
              <input type="time" name="out_time" id="att_out" class="form-control">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group select-placeholder">
              <label><?php echo _l('hr_attendance_status'); ?></label>
              <select name="status" id="att_status" class="selectpicker" data-width="100%">
                <option value="present">Present</option>
                <option value="late">Late</option>
                <option value="absent">Absent</option>
                <option value="half_day">Half Day</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label><?php echo _l('hr_notes'); ?></label>
          <textarea name="notes" id="att_notes" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
        <button type="submit" class="btn btn-primary" id="att-submit-btn">
          <?php echo _l('hr_save'); ?>
        </button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; // staff_can('create', 'hr_attendance') ?>

<!-- Punch Log Modal -->
<div class="modal fade" id="punchLogModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title tw-font-semibold">
        <i class="fa fa-fingerprint tw-mr-1 text-info"></i><?php echo _l('hr_attendance_punch_log'); ?>
      </h4>
      <div class="tw-mt-1">
        <span class="modal-title-name tw-font-semibold"></span>
        <span class="label label-info modal-title-count tw-ml-2"></span>
      </div>
    </div>
    <div class="modal-body" style="padding-top:0">
      <table class="table table-striped tw-mb-0">
        <thead><tr>
          <th><?php echo _l('hr_attendance_punch_time'); ?></th>
          <th>Devices</th>
          <th>Location</th>
          <th><?php echo _l('hr_attendance_verify_method'); ?></th>
        </tr></thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
    </div>
  </div></div>
</div>

<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-attendance', window.location.href, [], [2, 'desc']);

    function reload() {
        var url = window.location.href.split('?')[0]
            + '?department_id=' + $('#f-dept').val()
            + '&employee_id=' + $('#f-emp').val()
            + '&status=' + $('#f-status').val()
            + '&from_date=' + encodeURIComponent($('#f-from').val())
            + '&to_date=' + encodeURIComponent($('#f-to').val());
        $('.table-hr-attendance').DataTable().ajax.url(url).load();
    }
    $('#f-dept, #f-emp, #f-status, #f-from, #f-to').on('change changed.bs.select', reload);

    // View Log
    var verifyIcon = {
        'Fingerprint': 'fa-fingerprint text-info',
        'Face':        'fa-face-smile text-success',
        'ID Card':     'fa-id-card text-warning',
        'Password':    'fa-key text-muted',
    };
    $(document).on('click', '.hr-view-log', function(){
        var employee = $(this).data('employee');
        var date     = $(this).data('date');
        var name     = $(this).data('name');
        $('#punchLogModal .modal-title-name').text(name + ' — ' + date);
        $('#punchLogModal .modal-title-count').text('');
        var $body = $('#punchLogModal tbody').html('<tr><td colspan="4" class="text-center text-muted">Loading...</td></tr>');
        $('#punchLogModal').modal('show');
        $.getJSON('<?php echo admin_url('hr_module/attendance/punches/'); ?>' + employee + '/' + date, function(rows){
            if (!rows || !rows.length) {
                $('#punchLogModal .modal-title-count').text('0 punches');
                $body.html('<tr><td colspan="4" class="text-center text-muted">No punch records available for this entry.</td></tr>');
                return;
            }
            $('#punchLogModal .modal-title-count').text(rows.length + (rows.length === 1 ? ' punch' : ' punches'));
            var html = '';
            rows.forEach(function(r){
                var icon = verifyIcon[r.verify_mode] || 'fa-circle-question text-muted';
                html += '<tr><td><i class="fa fa-clock tw-mr-1 text-muted"></i><strong>' + r.time + '</strong></td>'
                    + '<td>' + r.device_name + '</td>'
                    + '<td>' + r.device_location + '</td>'
                    + '<td><i class="fa ' + icon + ' tw-mr-1"></i>' + r.verify_mode + '</td></tr>';
            });
            $body.html(html);
        }).fail(function(){
            $('#punchLogModal .modal-title-count').text('');
            $body.html('<tr><td colspan="4" class="text-center text-muted">No punch records available for this entry.</td></tr>');
        });
    });

    // Add
    $('#btn-add-att').on('click', function(){
        $('#attForm')[0].reset(); $('#att_id').val('');
        $('#att_date').val('<?php echo _d(date('Y-m-d')); ?>');
        $('#att_emp').selectpicker('refresh'); $('#att_status').selectpicker('refresh');
        $('#att-modal-title').text('<?php echo _l('hr_attendance_add'); ?>');
        $('#attModal').modal('show');
    });

    // Edit
    $(document).on('click', '.hr-edit-att', function(e){
        e.preventDefault();
        $.getJSON('<?php echo admin_url('hr_module/attendance/edit/'); ?>'+$(this).data('id'), function(d){
            $('#att_id').val(d.id); $('#att_emp').val(d.employee_id).selectpicker('refresh');
            $('#att_date').val(d.attendance_date); $('#att_in').val(d.in_time || '');
            $('#att_out').val(d.out_time || ''); $('#att_status').val(d.status).selectpicker('refresh');
            $('#att_notes').val(d.notes);
            $('#att-modal-title').text('<?php echo _l('hr_attendance_edit'); ?>');
            $('#attModal').modal('show');
        });
    });

    // Submit
    $('#attForm').on('submit', function(e){
        e.preventDefault();
        var id  = $('#att_id').val();
        var url = id
            ? '<?php echo admin_url('hr_module/attendance/edit/'); ?>'+id
            : '<?php echo admin_url('hr_module/attendance/add'); ?>';
        var $btn = $('#att-submit-btn').prop('disabled', true);
        $.post(url, $(this).serialize()+'&<?php echo $this->security->get_csrf_token_name(); ?>=<?php echo $this->security->get_csrf_hash(); ?>', function(r){
            if(r.success){ alert_float('success', r.message || '<?php echo _l('hr_attendance_added'); ?>'); $('#attModal').modal('hide'); $('.table-hr-attendance').DataTable().ajax.reload(); }
            else alert_float('danger', r.message);
        }, 'json').always(function(){ $btn.prop('disabled', false); });
    });
});
</script>
