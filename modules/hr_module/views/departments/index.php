<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-items-center tw-justify-between">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_department_list'); ?></h4>
          <?php if (staff_can('create', 'hr_departments')): ?>
          <button class="btn btn-primary" id="btn-add-department">
            <i class="fa fa-plus tw-mr-1"></i> <?php echo _l('hr_department_add'); ?>
          </button>
          <?php endif; ?>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_name'),
              _l('hr_employee_code'),
              _l('hr_department_parent'),
              _l('hr_department_head'),
              _l('hr_employee'),
              _l('hr_status'),
              _l('hr_actions'),
            ], 'hr-departments'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Department Modal -->
<div class="modal fade" id="departmentModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="deptModalTitle"><?php echo _l('hr_department_add'); ?></h4>
      </div>
      <form id="departmentForm">
        <div class="modal-body">
          <input type="hidden" id="dept_id" name="dept_id" value="">
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label><?php echo _l('hr_name'); ?> <span class="text-danger">*</span></label>
                <input type="text" name="name" id="dept_name" class="form-control" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label><?php echo _l('hr_employee_code'); ?></label>
                <input type="text" name="code" id="dept_code" class="form-control">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label><?php echo _l('hr_department_parent'); ?></label>
                <select name="parent_id" id="dept_parent" class="form-control">
                  <option value=""><?php echo _l('hr_none'); ?></option>
                  <?php foreach ($departments as $d): ?>
                  <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label><?php echo _l('hr_department_head'); ?></label>
                <select name="head_staff_id" id="dept_head" class="form-control">
                  <option value=""><?php echo _l('hr_select'); ?></option>
                  <?php foreach ($staff_members as $s): ?>
                  <option value="<?php echo $s->staffid; ?>"><?php echo htmlspecialchars($s->fullname); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label><?php echo _l('hr_description'); ?></label>
            <textarea name="description" id="dept_description" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-group">
            <div class="checkbox">
              <label>
                <input type="checkbox" name="status" id="dept_status" value="1" checked>
                <?php echo _l('hr_active'); ?>
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
          <button type="submit" class="btn btn-primary" id="dept-submit-btn">
            <i class="fa fa-save tw-mr-1"></i><?php echo _l('hr_save'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
$(function () {
    initDataTable('.table-hr-departments', window.location.href, [], []);

    // Open Add modal
    $('#btn-add-department').on('click', function () {
        $('#departmentForm')[0].reset();
        $('#dept_id').val('');
        $('#dept_status').prop('checked', true);
        $('#deptModalTitle').text('<?php echo _l('hr_department_add'); ?>');
        $('#departmentModal').modal('show');
    });

    // Open Edit modal
    $(document).on('click', '.hr-edit-dept', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.getJSON('<?php echo admin_url('hr_module/departments/edit/'); ?>' + id, function (data) {
            $('#dept_id').val(data.id);
            $('#dept_name').val(data.name);
            $('#dept_code').val(data.code);
            $('#dept_parent').val(data.parent_id);
            $('#dept_head').val(data.head_staff_id);
            $('#dept_description').val(data.description);
            $('#dept_status').prop('checked', data.status == 1);
            $('#deptModalTitle').text('<?php echo _l('hr_department_edit'); ?>');
            $('#departmentModal').modal('show');
        });
    });

    // Submit form
    $('#departmentForm').on('submit', function (e) {
        e.preventDefault();
        var id  = $('#dept_id').val();
        var url = id
            ? '<?php echo admin_url('hr_module/departments/edit/'); ?>' + id
            : '<?php echo admin_url('hr_module/departments/add'); ?>';
        var $btn = $('#dept-submit-btn').prop('disabled', true);
        $.post(url, $(this).serialize(), function (resp) {
            if (resp.success) {
                alert_float('success', resp.message);
                $('#departmentModal').modal('hide');
                $('.table-hr-departments').DataTable().ajax.reload();
            } else {
                alert_float('danger', resp.message);
            }
        }, 'json').always(function () { $btn.prop('disabled', false); });
    });

    // Delete
    $(document).on('click', '.hr-delete-dept', function (e) {
        e.preventDefault();
        var id   = $(this).data('id');
        var name = $(this).data('name');
        if (!confirm('<?php echo _l('hr_delete'); ?> "' + name + '"?')) return;
        $.post('<?php echo admin_url('hr_module/departments/delete/'); ?>' + id, {
            '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
        }, function (resp) {
            if (resp.success) {
                alert_float('success', resp.message);
                $('.table-hr-departments').DataTable().ajax.reload();
            } else {
                alert_float('danger', resp.message);
            }
        }, 'json');
    });
});
</script>
