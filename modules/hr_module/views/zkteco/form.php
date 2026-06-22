<?php defined('BASEPATH') or exit('No direct script access allowed');
$editing = !empty($device);
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/zkteco'); ?>"><?php echo _l('hr_zkteco_devices'); ?></a></li>
          <li class="active"><?php echo $title; ?></li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo $title; ?></h4>
            <?php
            $action = $editing
                ? admin_url('hr_module/zkteco/edit/' . $device->id)
                : admin_url('hr_module/zkteco/add');
            echo form_open($action);
            ?>
              <div class="row">
                <div class="col-md-8">
                  <div class="form-group">
                    <label>Device Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?php echo $editing ? htmlspecialchars($device->name) : ''; ?>"
                           placeholder="e.g. Main Entrance Device">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_status'); ?></label>
                    <select name="status" class="form-control">
                      <option value="1" <?php if($editing && $device->status==1) echo 'selected'; ?>>Active</option>
                      <option value="0" <?php if($editing && $device->status==0) echo 'selected'; ?>>Inactive</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>IP Address <span class="text-danger">*</span></label>
                    <input type="text" name="ip_address" class="form-control" required
                           value="<?php echo $editing ? htmlspecialchars($device->ip_address) : ''; ?>"
                           placeholder="192.168.1.201">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Port <span class="text-danger">*</span></label>
                    <input type="number" name="port" class="form-control" required
                           value="<?php echo $editing ? $device->port : 4370; ?>"
                           min="1" max="65535">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" class="form-control"
                           value="<?php echo $editing ? htmlspecialchars($device->location ?? '') : ''; ?>"
                           placeholder="e.g. Main Gate">
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="Device notes, configuration info..."><?php echo $editing ? htmlspecialchars($device->notes ?? '') : ''; ?></textarea>
              </div>

              <div class="alert alert-info" style="font-size:0.85rem">
                <i class="fa fa-info-circle tw-mr-1"></i>
                Ensure the ZKTeco device is on the same network and the port is not blocked by a firewall.
                Default port for ZKTeco devices is <strong>4370</strong>.
              </div>

              <div class="tw-flex tw-gap-2 tw-mt-3">
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-save tw-mr-1"></i><?php echo $editing ? _l('hr_save_changes') : 'Add Device'; ?>
                </button>
                <a href="<?php echo admin_url('hr_module/zkteco'); ?>" class="btn btn-default">
                  <?php echo _l('hr_cancel'); ?>
                </a>
                <?php if ($editing): ?>
                <button type="button" id="btn-test" class="btn btn-success">
                  <i class="fa fa-plug tw-mr-1"></i>Test Connection
                </button>
                <?php endif; ?>
              </div>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<?php if ($editing): ?>
<script>
$('#btn-test').on('click', function(){
    var $btn = $(this).prop('disabled', true).text('Testing...');
    $.getJSON('<?php echo admin_url('hr_module/zkteco/test_connection/'.$device->id); ?>', function(res){
        alert_float(res.success ? 'success' : 'danger', res.message);
    }).always(function(){
        $btn.prop('disabled', false).html('<i class="fa fa-plug mr-1"></i>Test Connection');
    });
});
</script>
<?php endif; ?>
