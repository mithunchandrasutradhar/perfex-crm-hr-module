<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo _l('hr_overtime_list'); ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <?php if (!empty($show_dept_filter)): ?>
            <select id="f-dept" class="selectpicker" data-width="150px" data-live-search="true">
              <option value=""><?php echo _l('hr_all'); ?> Dept</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <div class="input-group date" style="width:135px">
              <input type="text" id="f-from" class="form-control datepicker" autocomplete="off" placeholder="From date">
              <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
            </div>
            <div class="input-group date" style="width:135px">
              <input type="text" id="f-to" class="form-control datepicker" autocomplete="off" placeholder="To date">
              <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
            </div>
            <select id="f-status" class="selectpicker" data-width="110px">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
            <?php if (staff_can('create', 'hr_overtime')): ?>
            <a href="<?php echo admin_url('hr_module/overduty/request'); ?>" class="btn btn-primary">
              <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_overtime_add'); ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($can_approve)): ?>
            <a href="#" data-toggle="modal" data-target="#hrOverdutyBulkApproveModal" class="hide hr-bulk-approve-btn btn btn-default btn-sm">
              <i class="fa fa-check-double tw-mr-1"></i><?php echo _l('hr_overtime_bulk_approve'); ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($can_soft_approve)): ?>
            <a href="#" data-toggle="modal" data-target="#hrOverdutyBulkSoftApproveModal" class="hide hr-bulk-soft-approve-btn btn btn-default btn-sm">
              <i class="fa fa-check tw-mr-1"></i><?php echo _l('hr_overtime_bulk_soft_approve'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              '<div class="checkbox checkbox-primary mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="hr-overduty"><label></label></div>',
              _l('hr_employee'), _l('hr_department'),
              _l('hr_overtime_date'), _l('hr_overtime_day_type'),
              _l('hr_status'),
            ], 'hr-overduty'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($can_approve)): ?>
<div class="modal fade bulk_actions" id="hrOverdutyBulkApproveModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><?php echo _l('hr_overtime_bulk_approve'); ?></h4>
      </div>
      <div class="modal-body">
        <p><?php echo _l('hr_overtime_bulk_approve_confirm'); ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
        <a href="#" class="btn btn-primary hr-overduty-bulk-approve-confirm"><?php echo _l('hr_overtime_approve'); ?></a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($can_soft_approve)): ?>
<div class="modal fade bulk_actions" id="hrOverdutyBulkSoftApproveModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><?php echo _l('hr_overtime_bulk_soft_approve'); ?></h4>
      </div>
      <div class="modal-body">
        <p><?php echo _l('hr_overtime_bulk_soft_approve_confirm'); ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
        <a href="#" class="btn btn-primary hr-overduty-bulk-soft-approve-confirm"><?php echo _l('hr_overtime_soft_approve'); ?></a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-overduty', window.location.href, [], [], [], [2,'desc']);
    function reload(){
        var deptVal = $('#f-dept').length ? $('#f-dept').val() : '';
        var url = window.location.href.split('?')[0]
            + '?department_id=' + deptVal
            + '&status='        + $('#f-status').val()
            + '&from_date='     + $('#f-from').val()
            + '&to_date='       + $('#f-to').val();
        $('.table-hr-overduty').DataTable().ajax.url(url).load();
    }
    $('#f-dept,#f-status,#f-from,#f-to').on('change changed.bs.select', reload);

    // Pre-select the Status filter when landing here with ?status= in the
    // URL (e.g. the dashboard's "Pending Overduty Requests" quick action) -
    // the table itself is already filtered server-side by the initial
    // window.location.href load above, this just reflects it in the UI.
    (function(){
        var status = new URLSearchParams(window.location.search).get('status');
        if (status) {
            $('#f-status').val(status).selectpicker('refresh');
        }
    })();

    $(document).on('click', '.hr-ot-reject', function(e){
        e.preventDefault();
        var reason = prompt('<?php echo _l('hr_overtime_reject_reason_prompt'); ?>', '');
        if (reason === null) return;
        var $form = $('#' + $(this).data('target'));
        $form.find('[name="rejection_reason"]').val(reason);
        $form.trigger('submit');
    });

    function csrf_pair() {
        return '<?php echo $this->security->get_csrf_token_name(); ?>=<?php echo $this->security->get_csrf_hash(); ?>';
    }

    // Every DataTables redraw (sorting, paging, a filter dropdown changing,
    // reload() above) rebuilds the table body from scratch, including every
    // .hr-bulk-id checkbox - the browser has no memory a given row used to
    // be checked, so a plain :checked DOM query loses the selection (and the
    // Bulk Approve button) the moment any of that happens. hrOverdutySelected
    // tracks the chosen ids independently of the DOM instead, and 'draw.dt'
    // (fired on every redraw, for any reason) re-applies it to whichever
    // matching checkboxes are on the page afterwards.
    var hrOverdutySelected = {};
    function hrOverdutySyncBtn() {
        var empty = $.isEmptyObject(hrOverdutySelected);
        $('.hr-bulk-approve-btn').toggleClass('hide', empty);
        $('.hr-bulk-soft-approve-btn').toggleClass('hide', empty);
    }
    $(document).on('change', '.hr-bulk-id', function(){
        var id = $(this).val();
        if ($(this).prop('checked')) hrOverdutySelected[id] = true; else delete hrOverdutySelected[id];
        hrOverdutySyncBtn();
    });
    // #mass_select_all's own core-provided handler (main.js) sets each row
    // checkbox's checked state directly via .prop(), which does not fire a
    // 'change' event on its own - this second, independent handler on the
    // same element keeps hrOverdutySelected in sync with whatever core just did.
    $(document).on('change', '#mass_select_all[data-to-table="hr-overduty"]', function(){
        var checked = $(this).prop('checked');
        $('.table-hr-overduty .hr-bulk-id').each(function(){
            var id = $(this).val();
            if (checked) hrOverdutySelected[id] = true; else delete hrOverdutySelected[id];
        });
        hrOverdutySyncBtn();
    });
    $('.table-hr-overduty').on('draw.dt', function(){
        $('.table-hr-overduty .hr-bulk-id').each(function(){
            if (hrOverdutySelected[$(this).val()]) $(this).prop('checked', true);
        });
    });
    $(document).on('click', '.hr-overduty-bulk-approve-confirm', function(e){
        e.preventDefault();
        var ids = Object.keys(hrOverdutySelected);
        if (!ids.length) return;
        var params = csrf_pair();
        ids.forEach(function(id){ params += '&ids[]=' + encodeURIComponent(id); });
        var $btn = $(this).addClass('disabled');
        $.post('<?php echo admin_url('hr_module/overduty/bulk_approve'); ?>', params, function(r){
            $('#hrOverdutyBulkApproveModal').modal('hide');
            if (r.success) {
                alert_float('success', '<?php echo _l('hr_overtime_approve'); ?>');
                hrOverdutySelected = {};
                $('.table-hr-overduty').DataTable().ajax.reload(null, false);
                $('.hr-bulk-approve-btn, .hr-bulk-soft-approve-btn').addClass('hide');
            } else {
                alert_float('danger', r.message);
            }
        }, 'json').always(function(){ $btn.removeClass('disabled'); });
    });

    // Bulk Soft Approve: same selection-tracking (hrOverdutySelected) as Bulk
    // Approve above, just posted to the soft-approve endpoint instead - kept
    // as a fully separate handler/modal so the real Approve flow above is
    // untouched either way.
    $(document).on('click', '.hr-overduty-bulk-soft-approve-confirm', function(e){
        e.preventDefault();
        var ids = Object.keys(hrOverdutySelected);
        if (!ids.length) return;
        var params = csrf_pair();
        ids.forEach(function(id){ params += '&ids[]=' + encodeURIComponent(id); });
        var $btn = $(this).addClass('disabled');
        $.post('<?php echo admin_url('hr_module/overduty/bulk_soft_approve'); ?>', params, function(r){
            $('#hrOverdutyBulkSoftApproveModal').modal('hide');
            if (r.success) {
                alert_float('success', '<?php echo _l('hr_overtime_soft_approve'); ?>');
                hrOverdutySelected = {};
                $('.table-hr-overduty').DataTable().ajax.reload(null, false);
                $('.hr-bulk-approve-btn, .hr-bulk-soft-approve-btn').addClass('hide');
            } else {
                alert_float('danger', r.message);
            }
        }, 'json').always(function(){ $btn.removeClass('disabled'); });
    });

    // Soft Approve / Soft Reject: informational-only pre-approval, independent
    // of the real Approve/Reject above and never blocks it (mirrors the
    // buttons already on the overduty/view.php detail page).
    $(document).on('click', '.hr-ot-soft-approve', function(e){
        e.preventDefault();
        if (!confirm('<?php echo _l('hr_overtime_soft_approve'); ?>?')) return;
        var id = $(this).data('id');
        $.post('<?php echo admin_url('hr_module/overduty/soft_approve/'); ?>' + id, csrf_pair(), function(r){
            if (r.success) { alert_float('success', '<?php echo _l('hr_overtime_soft_approve'); ?>'); $('.table-hr-overduty').DataTable().ajax.reload(null, false); }
            else alert_float('danger', r.message);
        }, 'json');
    });
    $(document).on('click', '.hr-ot-soft-reject', function(e){
        e.preventDefault();
        if (!confirm('<?php echo _l('hr_overtime_soft_reject'); ?>?')) return;
        var id = $(this).data('id');
        $.post('<?php echo admin_url('hr_module/overduty/soft_reject/'); ?>' + id, csrf_pair(), function(r){
            if (r.success) { alert_float('success', '<?php echo _l('hr_overtime_soft_reject'); ?>'); $('.table-hr-overduty').DataTable().ajax.reload(null, false); }
            else alert_float('danger', r.message);
        }, 'json');
    });
});
</script>
