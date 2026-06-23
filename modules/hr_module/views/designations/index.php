<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-items-center tw-justify-between">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_designation_list'); ?></h4>
          <?php if (staff_can('create', 'hr_departments')): ?>
          <button class="btn btn-primary" id="btn-add-designation">
            <i class="fa fa-plus tw-mr-1"></i> <?php echo _l('hr_designation_add'); ?>
          </button>
          <?php endif; ?>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_name'),
              _l('hr_department'),
              _l('hr_employee'),
              _l('hr_status'),
              _l('hr_actions'),
            ], 'hr-designations'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Designation Modal -->
<div class="modal fade" id="designationModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="desigModalTitle"><?php echo _l('hr_designation_add'); ?></h4>
      </div>
      <form id="designationForm">
        <div class="modal-body">
          <input type="hidden" id="desig_id" name="desig_id" value="">
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <div class="form-group">
            <label><?php echo _l('hr_name'); ?> <span class="text-danger">*</span></label>
            <input type="text" name="name" id="desig_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label><?php echo _l('hr_department'); ?></label>
            <select name="department_id" id="desig_dept" class="form-control">
              <option value=""><?php echo _l('hr_select'); ?></option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label><?php echo _l('hr_description'); ?></label>
            <textarea name="description" id="desig_description" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-group">
            <div class="checkbox checkbox-primary">
              <input type="checkbox" name="status" id="desig_status" value="1">
              <label for="desig_status"><?php echo _l('hr_active'); ?></label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
          <button type="submit" class="btn btn-primary" id="desig-submit-btn">
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
    initDataTable('.table-hr-designations', window.location.href, [], []);

    $('#btn-add-designation').on('click', function () {
        $('#designationForm')[0].reset();
        $('#desig_id').val('');
        $('#desig_status').prop('checked', true);
        $('#desigModalTitle').text('<?php echo _l('hr_designation_add'); ?>');
        $('#designationModal').modal('show');
    });

    $(document).on('click', '.hr-edit-desig', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.getJSON('<?php echo admin_url('hr_module/designations/edit/'); ?>' + id, function (data) {
            $('#desig_id').val(data.id);
            $('#desig_name').val(data.name);
            $('#desig_dept').val(data.department_id);
            $('#desig_description').val(data.description);
            $('#desig_status').prop('checked', parseInt(data.status) === 1);
            $('#desigModalTitle').text('<?php echo _l('hr_designation_edit'); ?>');
            $('#designationModal').modal('show');
        }).fail(function () {
            alert_float('danger', 'Failed to load designation data.');
        });
    });

    $('#designationForm').on('submit', function (e) {
        e.preventDefault();
        var id  = $('#desig_id').val();
        var url = id
            ? '<?php echo admin_url('hr_module/designations/edit/'); ?>' + id
            : '<?php echo admin_url('hr_module/designations/add'); ?>';
        var $btn = $('#desig-submit-btn').prop('disabled', true);
        $.post(url, $(this).serialize(), function (resp) {
            if (resp.success) {
                alert_float('success', resp.message);
                $('#designationModal').modal('hide');
                $('.table-hr-designations').DataTable().ajax.reload();
            } else {
                alert_float('danger', resp.message);
            }
        }, 'json').always(function () { $btn.prop('disabled', false); });
    });

    $(document).on('click', '.hr-delete-desig', function (e) {
        e.preventDefault();
        var id   = $(this).data('id');
        var name = $(this).data('name');
        if (!confirm('<?php echo _l('hr_delete'); ?> "' + name + '"?')) return;
        $.post('<?php echo admin_url('hr_module/designations/delete/'); ?>' + id, {
            '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
        }, function (resp) {
            if (resp.success) {
                alert_float('success', resp.message);
                $('.table-hr-designations').DataTable().ajax.reload();
            } else {
                alert_float('danger', resp.message);
            }
        }, 'json');
    });
});
</script>
