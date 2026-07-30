<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_shift_list'); ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <select id="f-dept" class="form-control input-sm" style="width:150px">
              <option value=""><?php echo _l('hr_all'); ?> Dept</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="f-shift" class="form-control input-sm" style="width:150px">
              <option value="">All Shifts</option>
              <?php foreach ($shift_types as $s): ?>
              <option value="<?php echo $s->id; ?>"><?php echo htmlspecialchars($s->name); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="f-status" class="form-control input-sm" style="width:120px">
              <option value="">All Status</option>
              <option value="pending"><?php echo _l('hr_shift_status_pending'); ?></option>
              <option value="approved"><?php echo _l('hr_shift_status_approved'); ?></option>
              <option value="rejected"><?php echo _l('hr_shift_status_rejected'); ?></option>
            </select>
            <?php if ($can_manage): ?>
            <a href="<?php echo admin_url('hr_module/shifts/apply'); ?>" class="btn btn-primary">
              <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_shift_add_request'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_shift_employee'), _l('hr_department'), _l('hr_shift_type'),
              _l('hr_shift_date_range'), _l('hr_status'), 'Submitted',
            ], 'hr-shifts'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="rejectShiftModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><?php echo _l('hr_shift_reject'); ?></h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label><?php echo _l('hr_remarks'); ?></label>
          <textarea id="reject-shift-reason" class="form-control" rows="3" placeholder="Rejection reason..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
        <button type="button" class="btn btn-danger" id="reject-shift-confirm-btn"><?php echo _l('hr_shift_reject'); ?></button>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-shifts', window.location.href, [], [5,'desc']);
    function reload() {
        var url = window.location.href.split('?')[0]
            + '?department_id=' + $('#f-dept').val()
            + '&shift_type_id=' + $('#f-shift').val()
            + '&status='        + $('#f-status').val();
        $('.table-hr-shifts').DataTable().ajax.url(url).load();
    }
    $('#f-dept,#f-shift,#f-status').on('change', reload);

    function csrf_pair() {
        return '<?php echo $this->security->get_csrf_token_name(); ?>=<?php echo $this->security->get_csrf_hash(); ?>';
    }

    // Approve (quick action, straight from the list)
    $(document).on('click', '.hr-shift-approve', function(e){
        e.preventDefault();
        if (!confirm('<?php echo _l('hr_shift_approve'); ?>?')) return;
        var id = $(this).data('id');
        $.post('<?php echo admin_url('hr_module/shifts/approve/'); ?>' + id, csrf_pair(), function(r){
            if (r.success) { alert_float('success', '<?php echo _l('hr_shift_approved_msg'); ?>'); $('.table-hr-shifts').DataTable().ajax.reload(null, false); }
            else alert_float('danger', r.message);
        }, 'json');
    });

    // Reject (needs a reason, so it goes through a modal)
    var rejectShiftId = null;
    $(document).on('click', '.hr-shift-reject', function(e){
        e.preventDefault();
        rejectShiftId = $(this).data('id');
        $('#reject-shift-reason').val('');
        $('#rejectShiftModal').modal('show');
    });
    $('#reject-shift-confirm-btn').on('click', function(){
        var $btn = $(this).prop('disabled', true);
        $.post('<?php echo admin_url('hr_module/shifts/reject/'); ?>' + rejectShiftId,
            csrf_pair() + '&reason=' + encodeURIComponent($('#reject-shift-reason').val()), function(r){
                if (r.success) {
                    alert_float('success', '<?php echo _l('hr_shift_rejected_msg'); ?>');
                    $('#rejectShiftModal').modal('hide');
                    $('.table-hr-shifts').DataTable().ajax.reload(null, false);
                } else alert_float('danger', r.message);
            }, 'json').always(function(){ $btn.prop('disabled', false); });
    });
});
</script>
