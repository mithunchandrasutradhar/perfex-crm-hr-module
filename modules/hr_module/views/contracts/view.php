<?php defined('BASEPATH') or exit('No direct script access allowed');
$type_badge = ['permanent'=>'success','fixed'=>'info','probation'=>'warning','internship'=>'default','casual'=>'primary'];
$status_badge = ['active'=>'success','expired'=>'default','terminated'=>'danger','pending'=>'warning'];

$days_left = null;
if ($contract->end_date && $contract->status === 'active') {
    $days_left = (strtotime($contract->end_date) - time()) / 86400;
}
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/hr_contracts'); ?>"><?php echo _l('hr_contract_list'); ?></a></li>
          <li class="active"><?php echo htmlspecialchars($contract->title); ?></li>
        </ol>
      </div>
    </div>

    <?php if ($days_left !== null && $days_left >= 0 && $days_left <= 30): ?>
    <div class="alert alert-warning">
      <i class="fa-regular fa-clock tw-mr-1"></i>
      This contract expires in <strong><?php echo round($days_left); ?> day(s)</strong>
      (<?php echo date('d M Y', strtotime($contract->end_date)); ?>).
    </div>
    <?php endif; ?>

    <div class="row">
      <!-- Main content -->
      <div class="col-md-8">

        <!-- Header Card -->
        <div class="panel_s">
          <div class="panel-body">
            <div class="tw-flex tw-justify-between tw-items-start">
              <div>
                <h4 class="tw-font-bold tw-mb-2"><?php echo htmlspecialchars($contract->title); ?></h4>
                <div class="tw-flex tw-gap-2 tw-flex-wrap tw-mb-2">
                  <span class="label label-<?php echo $type_badge[$contract->contract_type] ?? 'default'; ?>">
                    <?php echo ucfirst($contract->contract_type); ?>
                  </span>
                  <span class="label label-<?php echo $status_badge[$contract->status] ?? 'default'; ?>">
                    <?php echo ucfirst($contract->status); ?>
                  </span>
                  <?php if ($contract->signed): ?>
                  <span class="label label-success">
                    <i class="fa fa-check tw-mr-1"></i>Signed<?php if($contract->signed_date) echo ' – '.date('d M Y', strtotime($contract->signed_date)); ?>
                  </span>
                  <?php else: ?>
                  <span class="label label-default">Unsigned</span>
                  <?php endif; ?>
                </div>
                <a href="<?php echo admin_url('hr_module/employees/view/'.$contract->employee_id); ?>">
                  <strong><?php echo htmlspecialchars($contract->first_name.' '.$contract->last_name); ?></strong>
                </a>
                <span class="label label-default tw-ml-1"><?php echo $contract->employee_code; ?></span>
                <?php if ($contract->department_name): ?>
                <span class="text-muted"> · <?php echo htmlspecialchars($contract->department_name); ?></span>
                <?php endif; ?>
                <?php if ($contract->designation_name): ?>
                <span class="text-muted"> · <?php echo htmlspecialchars($contract->designation_name); ?></span>
                <?php endif; ?>
              </div>
              <?php if ($contract->value): ?>
              <div class="text-right">
                <div style="font-size:1.5rem;font-weight:700;color:#4f46e5"><?php echo number_format($contract->value, 2); ?></div>
                <small class="text-muted">Contract Value</small>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Contract Content -->
        <?php if ($contract->content): ?>
        <div class="panel_s">
          <div class="panel-heading">
            <h5 class="tw-font-semibold tw-mb-0">Contract Terms</h5>
          </div>
          <div class="panel-body">
            <div style="white-space:pre-wrap;font-family:inherit;line-height:1.7;border:1px solid #e2e8f0;border-radius:6px;padding:16px;background:#fafafa">
              <?php echo nl2br(htmlspecialchars($contract->content)); ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if ($contract->notes): ?>
        <div class="panel_s">
          <div class="panel-heading"><h5 class="tw-font-semibold tw-mb-0">Internal Notes</h5></div>
          <div class="panel-body">
            <p style="background:#fffbeb;border-left:3px solid #f59e0b;padding:10px 14px;border-radius:0 6px 6px 0;margin:0">
              <?php echo nl2br(htmlspecialchars($contract->notes)); ?>
            </p>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- Sidebar -->
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="tw-font-semibold tw-mb-3">Contract Details</h5>
            <table class="table table-condensed">
              <tr><td class="text-muted">Type</td>
                  <td><span class="label label-<?php echo $type_badge[$contract->contract_type] ?? 'default'; ?>"><?php echo ucfirst($contract->contract_type); ?></span></td></tr>
              <tr><td class="text-muted">Status</td>
                  <td><span class="label label-<?php echo $status_badge[$contract->status] ?? 'default'; ?>"><?php echo ucfirst($contract->status); ?></span></td></tr>
              <tr><td class="text-muted">Start Date</td>
                  <td><?php echo date('d M Y', strtotime($contract->start_date)); ?></td></tr>
              <tr><td class="text-muted">End Date</td>
                  <td><?php echo $contract->end_date ? date('d M Y', strtotime($contract->end_date)) : '<span class="text-muted">Open-ended</span>'; ?></td></tr>
              <?php if ($days_left !== null && $days_left >= 0): ?>
              <tr><td class="text-muted">Days Left</td>
                  <td><span class="label <?php echo $days_left <= 30 ? 'label-warning' : 'label-info'; ?>"><?php echo round($days_left); ?> days</span></td></tr>
              <?php endif; ?>
              <tr><td class="text-muted">Signed</td>
                  <td><?php echo $contract->signed ? '<span class="text-success"><i class="fa fa-check"></i> '.(($contract->signed_date) ? date('d M Y', strtotime($contract->signed_date)) : 'Yes').'</span>' : 'No'; ?></td></tr>
              <?php if ($contract->value): ?>
              <tr><td class="text-muted">Value</td><td><strong><?php echo number_format($contract->value, 2); ?></strong></td></tr>
              <?php endif; ?>
              <tr><td class="text-muted">Created By</td><td><?php echo htmlspecialchars($contract->created_by_name ?? ''); ?></td></tr>
              <tr><td class="text-muted">Created</td><td><?php echo date('d M Y', strtotime($contract->created_at)); ?></td></tr>
            </table>

            <?php if ($contract->attachment): ?>
            <a href="<?php echo admin_url('hr_module/hr_contracts/download/'.$contract->id); ?>" target="_blank"
               class="btn btn-default btn-block btn-sm tw-mb-2">
              <i class="fa fa-paperclip tw-mr-1"></i>View Attachment
            </a>
            <?php endif; ?>

            <!-- Sign button -->
            <?php if (!$contract->signed && staff_can('edit','hr_contracts')): ?>
            <button type="button" class="btn btn-success btn-block btn-sm tw-mb-2"
                    data-toggle="modal" data-target="#signModal">
              <i class="fa fa-pen tw-mr-1"></i>Mark as Signed
            </button>
            <?php endif; ?>

            <!-- Change status -->
            <?php if (staff_can('edit','hr_contracts') && $contract->status !== 'expired'): ?>
            <button type="button" class="btn btn-default btn-block btn-sm tw-mb-2"
                    data-toggle="modal" data-target="#statusModal">
              <i class="fa fa-exchange tw-mr-1"></i>Change Status
            </button>
            <?php endif; ?>

            <?php if (staff_can('edit','hr_contracts')): ?>
            <a href="<?php echo admin_url('hr_module/hr_contracts/edit/'.$contract->id); ?>"
               class="btn btn-default btn-block btn-sm tw-mb-2">
              <i class="fa fa-edit tw-mr-1"></i><?php echo _l('hr_contract_edit'); ?>
            </a>
            <?php endif; ?>

            <?php if (staff_can('delete','hr_contracts')): ?>
            <a href="<?php echo admin_url('hr_module/hr_contracts/delete/'.$contract->id); ?>"
               class="btn btn-default btn-block btn-sm _delete">
              <i class="fa fa-trash tw-mr-1"></i>Delete Contract
            </a>
            <?php endif; ?>

            <a href="<?php echo admin_url('hr_module/hr_contracts'); ?>" class="btn btn-default btn-block btn-sm tw-mt-2">
              <i class="fa fa-arrow-left tw-mr-1"></i>Back to List
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Sign Modal -->
<div class="modal fade" id="signModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Mark as Signed</h4>
      </div>
      <?php echo form_open(admin_url('hr_module/hr_contracts/sign/'.$contract->id)); ?>
      <div class="modal-body">
        <div class="form-group">
          <label>Signing Date</label>
          <div class="input-group date">
            <input type="text" name="signed_date" class="form-control datepicker" autocomplete="off" value="<?php echo _d(date('Y-m-d')); ?>">
            <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Confirm Signed</button>
      </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Change Status</h4>
      </div>
      <?php echo form_open(admin_url('hr_module/hr_contracts/set_status/'.$contract->id)); ?>
      <div class="modal-body">
        <div class="form-group select-placeholder">
          <label>New Status</label>
          <select name="status" class="selectpicker" data-width="100%">
            <?php
            $statuses = ['active'=>'Active','pending'=>'Pending','expired'=>'Expired','terminated'=>'Terminated'];
            foreach ($statuses as $val => $label):
            ?>
            <option value="<?php echo $val; ?>" <?php if($contract->status==$val) echo 'selected'; ?>>
              <?php echo $label; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Status</button>
      </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>

<?php init_tail(); ?>
