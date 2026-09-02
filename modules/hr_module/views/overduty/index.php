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
          </div>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
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
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-overduty', window.location.href, [], [2,'desc']);
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
