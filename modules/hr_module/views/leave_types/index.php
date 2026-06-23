<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-gap-3">
            <a href="<?php echo admin_url('hr_module/leave'); ?>" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i></a>
            <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700 tw-mb-0"><?php echo _l('hr_leave_types_list'); ?></h4>
          </div>
          <?php if (staff_can('create', 'hr_leave')): ?>
          <button class="btn btn-primary btn-sm" id="btn-add-ltype">
            <i class="fa fa-plus tw-mr-1"></i><?php echo _l('hr_leave_type_add'); ?>
          </button>
          <?php endif; ?>
        </div>
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_name'), _l('hr_leave_max_days'),
              _l('hr_leave_carry_forward'), _l('hr_leave_requires_attachment'),
              _l('hr_leave_half_day'), _l('hr_status'), _l('hr_actions'),
            ], 'hr-ltypes'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="ltypeModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title" id="ltype-modal-title"><?php echo _l('hr_leave_type_add'); ?></h4>
    </div>
    <form id="ltypeForm">
      <div class="modal-body">
        <input type="hidden" id="ltype_id" name="ltype_id">
        <div class="row">
          <div class="col-md-8">
            <div class="form-group"><label><?php echo _l('hr_name'); ?> <span class="text-danger">*</span></label>
              <input type="text" name="name" id="ltype_name" class="form-control" required></div>
          </div>
          <div class="col-md-4">
            <div class="form-group"><label><?php echo _l('hr_leave_max_days'); ?></label>
              <input type="number" name="days_per_year" id="ltype_days" class="form-control" min="0" value="0"></div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label><?php echo _l('hr_leave_carry_forward'); ?> (max days)</label>
              <input type="number" name="max_carry_forward_days" id="ltype_carry_days" class="form-control" min="0" value="0"></div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group"><div class="checkbox checkbox-primary">
              <input type="checkbox" name="carry_forward" id="ltype_carry" value="1">
              <label for="ltype_carry"><?php echo _l('hr_leave_carry_forward'); ?></label>
            </div></div>
          </div>
          <div class="col-md-4">
            <div class="form-group"><div class="checkbox checkbox-primary">
              <input type="checkbox" name="requires_attachment" id="ltype_attach" value="1">
              <label for="ltype_attach"><?php echo _l('hr_leave_requires_attachment'); ?></label>
            </div></div>
          </div>
          <div class="col-md-4">
            <div class="form-group"><div class="checkbox checkbox-primary">
              <input type="checkbox" name="allow_half_day" id="ltype_half" value="1">
              <label for="ltype_half"><?php echo _l('hr_leave_half_day'); ?></label>
            </div></div>
          </div>
        </div>
        <div class="form-group"><label><?php echo _l('hr_description'); ?></label>
          <textarea name="description" id="ltype_desc" class="form-control" rows="2"></textarea></div>
        <div class="form-group"><div class="checkbox checkbox-primary">
          <input type="checkbox" name="status" id="ltype_status" value="1">
          <label for="ltype_status"><?php echo _l('hr_active'); ?></label>
        </div></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
        <button type="submit" class="btn btn-primary" id="ltype-submit-btn"><i class="fa fa-save tw-mr-1"></i><?php echo _l('hr_save'); ?></button>
      </div>
    </form>
  </div></div>
</div>

<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-ltypes', window.location.href, [], []);

    $('#btn-add-ltype').on('click', function(){
        $('#ltypeForm')[0].reset(); $('#ltype_id').val('');
        $('#ltype_half, #ltype_status').prop('checked', true);
        $('#ltype-modal-title').text('<?php echo _l('hr_leave_type_add'); ?>');
        $('#ltypeModal').modal('show');
    });

    $(document).on('click', '.hr-edit-ltype', function(e){
        e.preventDefault();
        $.getJSON('<?php echo admin_url('hr_module/leave_types/edit/'); ?>'+$(this).data('id'), function(d){
            $('#ltype_id').val(d.id); $('#ltype_name').val(d.name); $('#ltype_days').val(d.days_per_year);
            $('#ltype_carry_days').val(d.max_carry_forward_days);
            $('#ltype_carry').prop('checked', parseInt(d.carry_forward)===1);
            $('#ltype_attach').prop('checked', parseInt(d.requires_attachment)===1);
            $('#ltype_half').prop('checked', parseInt(d.allow_half_day)===1);
            $('#ltype_desc').val(d.description); $('#ltype_status').prop('checked', parseInt(d.status)===1);
            $('#ltype-modal-title').text('<?php echo _l('hr_leave_type_edit'); ?>');
            $('#ltypeModal').modal('show');
        });
    });

    $('#ltypeForm').on('submit', function(e){
        e.preventDefault();
        var id = $('#ltype_id').val();
        var url = id ? '<?php echo admin_url('hr_module/leave_types/edit/'); ?>'+id : '<?php echo admin_url('hr_module/leave_types/add'); ?>';
        var $btn = $('#ltype-submit-btn').prop('disabled', true);
        $.post(url, $(this).serialize(), function(r){
            if(r.success){ alert_float('success', r.message); $('#ltypeModal').modal('hide'); $('.table-hr-ltypes').DataTable().ajax.reload(); }
            else alert_float('danger', r.message);
        }, 'json').always(function(){ $btn.prop('disabled', false); });
    });

    $(document).on('click', '.hr-del-ltype', function(e){
        e.preventDefault();
        if(!confirm('<?php echo _l('hr_delete'); ?> "'+$(this).data('name')+'"?')) return;
        $.post('<?php echo admin_url('hr_module/leave_types/delete/'); ?>'+$(this).data('id'), {
            '<?php echo $this->security->get_csrf_token_name(); ?>':'<?php echo $this->security->get_csrf_hash(); ?>'
        }, function(r){
            if(r.success){ alert_float('success', r.message); $('.table-hr-ltypes').DataTable().ajax.reload(); }
            else alert_float('danger', r.message);
        }, 'json');
    });
});
</script>
