<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-10 col-md-offset-1">
        <div class="tw-mb-2 tw-flex tw-items-center tw-justify-between">
          <div>
            <ol class="breadcrumb" style="margin-bottom:4px">
              <li><a href="<?php echo admin_url('hr_module/payroll'); ?>"><?php echo _l('hr_payroll_list'); ?></a></li>
              <li class="active"><?php echo _l('hr_payroll_items_list'); ?></li>
            </ol>
          </div>
          <?php if (staff_can('create','hr_payroll')): ?>
          <button class="btn btn-primary" id="btn-add-item">
            <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_payroll_item_add'); ?>
          </button>
          <?php endif; ?>
        </div>

        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <p class="text-muted tw-text-sm">Payroll items are allowances and deductions automatically applied to every payroll.
              <strong>Fixed</strong> items use the exact amount; <strong>Percentage</strong> items are calculated as a % of basic salary.</p>
            <?php render_datatable([
              'Name', 'Type', 'Calculation', 'Value', 'Taxable', 'Status',
            ], 'hr-payroll-items'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title" id="item-modal-title"><?php echo _l('hr_payroll_item_add'); ?></h4>
    </div>
    <form id="itemForm">
      <div class="modal-body">
        <input type="hidden" id="item_id">
        <div class="row">
          <div class="col-md-8">
            <div class="form-group">
              <label>Name <span class="text-danger">*</span></label>
              <input type="text" name="name" id="item_name" class="form-control" required placeholder="e.g. House Rent Allowance">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group select-placeholder">
              <label>Type <span class="text-danger">*</span></label>
              <select name="type" id="item_type" class="selectpicker" data-width="100%">
                <option value="allowance">Allowance</option>
                <option value="deduction">Deduction</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group select-placeholder">
              <label>Calculation <span class="text-danger">*</span></label>
              <select name="calculation_type" id="item_calc" class="selectpicker" data-width="100%">
                <option value="fixed">Fixed Amount</option>
                <option value="percentage">% of Basic Salary</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Value <span class="text-danger">*</span> <small id="val-unit">(amount)</small></label>
              <input type="number" step="0.01" min="0" name="value" id="item_value" class="form-control" required>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <div class="checkbox checkbox-primary">
                <input type="checkbox" name="taxable" id="item_taxable" value="1">
                <label for="item_taxable">Taxable</label>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <div class="checkbox checkbox-primary">
                <input type="checkbox" name="status" id="item_status" value="1">
                <label for="item_status">Active</label>
              </div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="item_desc" class="form-control" rows="2" placeholder="Optional description"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div></div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-payroll-items', window.location.href, [], [], [], [0, 'asc']);

    $('#item_calc').on('change', function(){
        $('#val-unit').text($(this).val() === 'percentage' ? '(% of basic)' : '(amount)');
    });

    $('#btn-add-item').on('click', function(){
        $('#itemForm')[0].reset();
        $('#item_id').val('');
        $('#item_type, #item_calc').selectpicker('refresh');
        $('#item_status').prop('checked', true);
        $('#item-modal-title').text('<?php echo _l('hr_payroll_item_add'); ?>');
        $('#itemModal').modal('show');
    });

    $(document).on('click', '.hr-edit-item', function(e){
        e.preventDefault();
        $.getJSON('<?php echo admin_url('hr_module/payroll_items/edit/'); ?>'+$(this).data('id'), function(d){
            $('#item_id').val(d.id); $('#item_name').val(d.name);
            $('#item_type').val(d.type).selectpicker('refresh');
            $('#item_calc').val(d.calculation_type).selectpicker('refresh').trigger('change'); $('#item_value').val(d.value);
            $('#item_taxable').prop('checked', parseInt(d.taxable)===1); $('#item_status').prop('checked', parseInt(d.status)===1);
            $('#item_desc').val(d.description);
            $('#item-modal-title').text('<?php echo _l('hr_payroll_item_edit'); ?>');
            $('#itemModal').modal('show');
        });
    });

    $('#itemForm').on('submit', function(e){
        e.preventDefault();
        var id  = $('#item_id').val();
        var url = id ? '<?php echo admin_url('hr_module/payroll_items/edit/'); ?>'+id
                     : '<?php echo admin_url('hr_module/payroll_items/add'); ?>';
        $.post(url, $(this).serialize()+'&<?php echo $this->security->get_csrf_token_name(); ?>=<?php echo $this->security->get_csrf_hash(); ?>', function(r){
            if(r.success){ alert_float('success', r.message); $('#itemModal').modal('hide'); $('.table-hr-payroll-items').DataTable().ajax.reload(null, false); }
            else alert_float('danger', r.message);
        }, 'json');
    });
});
</script>
