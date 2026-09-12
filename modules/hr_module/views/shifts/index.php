<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_shift_list'); ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <?php if (!empty($show_dept_filter)): ?>
            <select id="f-dept" class="selectpicker" data-width="150px" data-live-search="true">
              <option value=""><?php echo _l('hr_all'); ?> Dept</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <select id="f-shift" class="selectpicker" data-width="220px">
              <option value="">All Shifts</option>
              <?php foreach ($shift_types as $s): ?>
              <?php
              $shift_label = $s->name;
              if (!empty($s->start_time) && !empty($s->end_time)) {
                  $shift_label .= ' (' . date('g:i A', strtotime($s->start_time)) . ' - ' . date('g:i A', strtotime($s->end_time)) . ')';
              }
              ?>
              <option value="<?php echo $s->id; ?>"><?php echo htmlspecialchars($shift_label); ?></option>
              <?php endforeach; ?>
            </select>
            <div class="input-group date" style="width:150px">
              <input type="text" id="f-from" class="form-control datepicker" autocomplete="off" placeholder="From date">
              <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
            </div>
            <div class="input-group date" style="width:150px">
              <input type="text" id="f-to" class="form-control datepicker" autocomplete="off" placeholder="To date">
              <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
            </div>
            <select id="f-status" class="selectpicker" data-width="120px">
              <option value="">All Status</option>
              <option value="pending"><?php echo _l('hr_shift_status_pending'); ?></option>
              <option value="approved"><?php echo _l('hr_shift_status_approved'); ?></option>
              <option value="rejected"><?php echo _l('hr_shift_status_rejected'); ?></option>
            </select>
            <button type="button" id="btn-reset-filters" class="btn btn-default btn-sm" title="Reset filters">
              <i class="fa fa-rotate-left tw-mr-1"></i><?php echo _l('hr_reset_filters'); ?>
            </button>
            <?php if ($can_manage): ?>
            <a href="<?php echo admin_url('hr_module/shifts/apply'); ?>" class="btn btn-primary">
              <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_shift_add_request'); ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($can_approve)): ?>
            <a href="#" data-toggle="modal" data-target="#hrShiftBulkApproveModal" class="hide hr-bulk-approve-btn btn btn-default btn-sm">
              <i class="fa fa-check-double tw-mr-1"></i><?php echo _l('hr_shift_bulk_approve'); ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($can_soft_approve)): ?>
            <a href="#" data-toggle="modal" data-target="#hrShiftBulkSoftApproveModal" class="hide hr-bulk-soft-approve-btn btn btn-default btn-sm">
              <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_shift_bulk_soft_approve'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              '<div class="checkbox checkbox-primary mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="hr-shifts"><label></label></div>',
              _l('hr_shift_employee'), _l('hr_department'), _l('hr_shift_type'),
              _l('hr_shift_date_range'), _l('hr_status'), 'Submitted',
            ], 'hr-shifts'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($can_approve)): ?>
<div class="modal fade bulk_actions" id="hrShiftBulkApproveModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><?php echo _l('hr_shift_bulk_approve'); ?></h4>
      </div>
      <div class="modal-body">
        <p><?php echo _l('hr_shift_bulk_approve_confirm'); ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
        <a href="#" class="btn btn-primary hr-shift-bulk-approve-confirm"><?php echo _l('hr_shift_approve'); ?></a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($can_soft_approve)): ?>
<div class="modal fade bulk_actions" id="hrShiftBulkSoftApproveModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><?php echo _l('hr_shift_bulk_soft_approve'); ?></h4>
      </div>
      <div class="modal-body">
        <p><?php echo _l('hr_shift_bulk_soft_approve_confirm'); ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
        <a href="#" class="btn btn-primary hr-shift-bulk-soft-approve-confirm"><?php echo _l('hr_shift_soft_approve'); ?></a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

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
    // Built from these fields' live values - not window.location.href
    // directly - same as the Attendance list's equivalent buildFilterUrl().
    function buildFilterUrl() {
        var deptVal = $('#f-dept').length ? $('#f-dept').val() : '';
        return window.location.href.split('?')[0]
            + '?department_id=' + encodeURIComponent(deptVal || '')
            + '&shift_type_id=' + encodeURIComponent($('#f-shift').val() || '')
            + '&status='        + encodeURIComponent($('#f-status').val() || '')
            + '&from_date='     + encodeURIComponent($('#f-from').val() || '')
            + '&to_date='       + encodeURIComponent($('#f-to').val() || '');
    }
    initDataTable('.table-hr-shifts', buildFilterUrl(), [], [], [], [5,'desc']);
    function reload() {
        $('.table-hr-shifts').DataTable().ajax.url(buildFilterUrl()).load();
    }
    $('#f-dept,#f-shift,#f-status,#f-from,#f-to').on('change changed.bs.select', reload);

    // Reset every filter back to blank/"All" in one click, instead of
    // clearing each dropdown/date by hand - a single reload() at the end
    // picks up all of them at once.
    $('#btn-reset-filters').on('click', function(){
        if ($('#f-dept').length) $('#f-dept').val('').selectpicker('refresh');
        $('#f-shift').val('').selectpicker('refresh');
        $('#f-status').val('').selectpicker('refresh');
        $('#f-from').val('');
        $('#f-to').val('');
        reload();
    });

    function csrf_pair() {
        return '<?php echo $this->security->get_csrf_token_name(); ?>=<?php echo $this->security->get_csrf_hash(); ?>';
    }

    // Every DataTables redraw (sorting, paging, a filter dropdown changing,
    // reload() above) rebuilds the table body from scratch, including every
    // .hr-bulk-id checkbox - the browser has no memory a given row used to
    // be checked, so a plain :checked DOM query loses the selection (and the
    // Bulk Approve button) the moment any of that happens. hrShiftSelected
    // tracks the chosen ids independently of the DOM instead, and 'draw.dt'
    // (fired on every redraw, for any reason) re-applies it to whichever
    // matching checkboxes are on the page afterwards.
    var hrShiftSelected = {};
    function hrShiftSyncBtn() {
        var empty = $.isEmptyObject(hrShiftSelected);
        $('.hr-bulk-approve-btn').toggleClass('hide', empty);
        $('.hr-bulk-soft-approve-btn').toggleClass('hide', empty);
    }
    $(document).on('change', '.hr-bulk-id', function(){
        var id = $(this).val();
        if ($(this).prop('checked')) hrShiftSelected[id] = true; else delete hrShiftSelected[id];
        hrShiftSyncBtn();
    });
    // #mass_select_all's own core-provided handler (main.js) sets each row
    // checkbox's checked state directly via .prop(), which does not fire a
    // 'change' event on its own - this second, independent handler on the
    // same element keeps hrShiftSelected in sync with whatever core just did.
    $(document).on('change', '#mass_select_all[data-to-table="hr-shifts"]', function(){
        var checked = $(this).prop('checked');
        $('.table-hr-shifts .hr-bulk-id').each(function(){
            var id = $(this).val();
            if (checked) hrShiftSelected[id] = true; else delete hrShiftSelected[id];
        });
        hrShiftSyncBtn();
    });
    $('.table-hr-shifts').on('draw.dt', function(){
        $('.table-hr-shifts .hr-bulk-id').each(function(){
            if (hrShiftSelected[$(this).val()]) $(this).prop('checked', true);
        });
    });
    $(document).on('click', '.hr-shift-bulk-approve-confirm', function(e){
        e.preventDefault();
        var ids = Object.keys(hrShiftSelected);
        if (!ids.length) return;
        var params = csrf_pair();
        ids.forEach(function(id){ params += '&ids[]=' + encodeURIComponent(id); });
        var $btn = $(this).addClass('disabled');
        $.post('<?php echo admin_url('hr_module/shifts/bulk_approve'); ?>', params, function(r){
            $('#hrShiftBulkApproveModal').modal('hide');
            if (r.success) {
                alert_float('success', '<?php echo _l('hr_shift_approved_msg'); ?>');
                hrShiftSelected = {};
                $('.table-hr-shifts').DataTable().ajax.reload(null, false);
                $('.hr-bulk-approve-btn, .hr-bulk-soft-approve-btn').addClass('hide');
            } else {
                alert_float('danger', r.message);
            }
        }, 'json').always(function(){ $btn.removeClass('disabled'); });
    });

    // Bulk Soft Approve: same selection-tracking (hrShiftSelected) as Bulk
    // Approve above, just posted to the soft-approve endpoint instead - kept
    // as a fully separate handler/modal so the real Approve flow above is
    // untouched either way.
    $(document).on('click', '.hr-shift-bulk-soft-approve-confirm', function(e){
        e.preventDefault();
        var ids = Object.keys(hrShiftSelected);
        if (!ids.length) return;
        var params = csrf_pair();
        ids.forEach(function(id){ params += '&ids[]=' + encodeURIComponent(id); });
        var $btn = $(this).addClass('disabled');
        $.post('<?php echo admin_url('hr_module/shifts/bulk_soft_approve'); ?>', params, function(r){
            $('#hrShiftBulkSoftApproveModal').modal('hide');
            if (r.success) {
                alert_float('success', '<?php echo _l('hr_shift_soft_approve'); ?>');
                hrShiftSelected = {};
                $('.table-hr-shifts').DataTable().ajax.reload(null, false);
                $('.hr-bulk-approve-btn, .hr-bulk-soft-approve-btn').addClass('hide');
            } else {
                alert_float('danger', r.message);
            }
        }, 'json').always(function(){ $btn.removeClass('disabled'); });
    });

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

    // Soft Approve / Soft Reject: informational-only pre-approval, independent
    // of the real Approve/Reject above and never blocks it (mirrors the
    // buttons already on the shifts/view.php detail page).
    $(document).on('click', '.hr-shift-soft-approve', function(e){
        e.preventDefault();
        if (!confirm('<?php echo _l('hr_shift_soft_approve'); ?>?')) return;
        var id = $(this).data('id');
        $.post('<?php echo admin_url('hr_module/shifts/soft_approve/'); ?>' + id, csrf_pair(), function(r){
            if (r.success) { alert_float('success', '<?php echo _l('hr_shift_soft_approve'); ?>'); $('.table-hr-shifts').DataTable().ajax.reload(null, false); }
            else alert_float('danger', r.message);
        }, 'json');
    });
    $(document).on('click', '.hr-shift-soft-reject', function(e){
        e.preventDefault();
        if (!confirm('<?php echo _l('hr_shift_soft_reject'); ?>?')) return;
        var id = $(this).data('id');
        $.post('<?php echo admin_url('hr_module/shifts/soft_reject/'); ?>' + id, csrf_pair(), function(r){
            if (r.success) { alert_float('success', '<?php echo _l('hr_shift_soft_reject'); ?>'); $('.table-hr-shifts').DataTable().ajax.reload(null, false); }
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
