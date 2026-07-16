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
          <div class="panel-body">
            <p class="text-muted tw-text-sm">Payroll items are allowances and deductions automatically applied to every payroll.
              <strong>Fixed</strong> items use the exact amount; <strong>Percentage</strong> items are calculated as a % of basic salary.</p>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead><tr>
                  <th>Name</th><th>Type</th><th>Calculation</th><th>Value</th><th>Taxable</th><th>Status</th>
                </tr></thead>
                <tbody>
                  <?php foreach ($items as $item): ?>
                  <tr class="has-row-options">
                    <td><strong><?php echo htmlspecialchars($item->name); ?></strong>
                      <?php if ($item->description): ?><br><small class="text-muted"><?php echo htmlspecialchars($item->description); ?></small><?php endif; ?>
                      <div class="row-options">
                        <?php if (staff_can('edit','hr_payroll')): ?>
                        <a href="#" class="hr-edit-item" data-id="<?php echo $item->id; ?>"><?php echo _l('hr_edit'); ?></a>
                        <?php endif; ?>
                        <?php if (staff_can('edit','hr_payroll') && staff_can('delete','hr_payroll')): ?> | <?php endif; ?>
                        <?php if (staff_can('delete','hr_payroll')): ?>
                        <a href="<?php echo admin_url('hr_module/payroll_items/delete/'.$item->id); ?>" class="_delete text-danger"><?php echo _l('hr_delete'); ?></a>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td>
                      <?php if ($item->type === 'allowance'): ?>
                      <span class="label label-success">Allowance</span>
                      <?php else: ?>
                      <span class="label label-danger">Deduction</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo ucfirst($item->calculation_type); ?></td>
                    <td>
                      <?php if ($item->calculation_type === 'percentage'): ?>
                        <?php echo $item->value; ?>%
                      <?php else: ?>
                        <?php echo number_format($item->value, 2); ?>
                      <?php endif; ?>
                    </td>
                    <td><?php echo $item->taxable ? '<span class="label label-warning">Yes</span>' : '<span class="label label-default">No</span>'; ?></td>
                    <td><?php echo $item->status ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Inactive</span>'; ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (empty($items)): ?>
                  <tr><td colspan="6" class="tw-text-center text-muted">No payroll items configured yet. Add allowances and deductions to automate payroll.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
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
            <div class="form-group">
              <label>Type <span class="text-danger">*</span></label>
              <select name="type" id="item_type" class="form-control">
                <option value="allowance">Allowance</option>
                <option value="deduction">Deduction</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Calculation <span class="text-danger">*</span></label>
              <select name="calculation_type" id="item_calc" class="form-control">
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
          <input type="text" name="description" id="item_desc" class="form-control" placeholder="Optional description">
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
    $('#item_calc').on('change', function(){
        $('#val-unit').text($(this).val() === 'percentage' ? '(% of basic)' : '(amount)');
    });

    $('#btn-add-item').on('click', function(){
        $('#itemForm')[0].reset();
        $('#item_id').val('');
        $('#item_status').prop('checked', true);
        $('#item-modal-title').text('<?php echo _l('hr_payroll_item_add'); ?>');
        $('#itemModal').modal('show');
    });

    $(document).on('click', '.hr-edit-item', function(e){
        e.preventDefault();
        $.getJSON('<?php echo admin_url('hr_module/payroll_items/edit/'); ?>'+$(this).data('id'), function(d){
            $('#item_id').val(d.id); $('#item_name').val(d.name); $('#item_type').val(d.type);
            $('#item_calc').val(d.calculation_type).trigger('change'); $('#item_value').val(d.value);
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
            if(r.success){ alert_float('success', r.message); $('#itemModal').modal('hide'); location.reload(); }
            else alert_float('danger', r.message);
        }, 'json');
    });
});
</script>
