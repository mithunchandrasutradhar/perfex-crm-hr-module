<?php defined('BASEPATH') or exit('No direct script access allowed');
$editing = !empty($device);
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/devices'); ?>"><?php echo _l('hr_zkteco_devices'); ?></a></li>
          <li class="active"><?php echo $title; ?></li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo $title; ?></h4>
            <?php
            $action = $editing
                ? admin_url('hr_module/devices/edit/' . $device->id)
                : admin_url('hr_module/devices/add');
            echo form_open($action);
            ?>
              <?php $device_type = $editing ? ($device->device_type ?? 'zkteco') : 'zkteco'; ?>
              <div class="row">
                <div class="col-md-5">
                  <div class="form-group">
                    <label>Device Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?php echo $editing ? htmlspecialchars($device->name) : ''; ?>"
                           placeholder="e.g. Main Entrance Device">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Device Type</label>
                    <select name="device_type" class="form-control">
                      <option value="zkteco" <?php if($device_type=='zkteco') echo 'selected'; ?>>ZKTeco (live push)</option>
                      <option value="ai07f" <?php if($device_type=='ai07f') echo 'selected'; ?>>AI07F Face Terminal (live push)</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-4">
                  <div class="form-group">
                    <label><?php echo _l('hr_zkteco_serial_number'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="serial_number" class="form-control" required
                           value="<?php echo $editing ? htmlspecialchars($device->serial_number ?? '') : ''; ?>"
                           placeholder="e.g. COAW221060606">
                    <p class="text-muted" style="font-size:0.8rem;margin-top:4px">
                      Must match the Serial Number of the physical device exactly - this is how the device
                      identifies itself when it pushes attendance data.
                    </p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Last Seen IP</label>
                    <input type="text" name="ip_address" class="form-control"
                           value="<?php echo $editing ? htmlspecialchars($device->ip_address ?? '') : ''; ?>"
                           placeholder="Optional - informational only">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" class="form-control"
                           value="<?php echo $editing ? htmlspecialchars($device->location ?? '') : ''; ?>"
                           placeholder="e.g. Main Gate">
                  </div>
                </div>
              </div>
              <input type="hidden" name="port" value="<?php echo $editing ? $device->port : 4370; ?>">

              <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="Device notes, configuration info..."><?php echo $editing ? htmlspecialchars($device->notes ?? '') : ''; ?></textarea>
              </div>

              <div class="alert alert-info" id="device-type-hint-zkteco"
                   style="font-size:0.85rem<?php echo $device_type!='zkteco' ? ';display:none' : ''; ?>">
                <i class="fa fa-info-circle tw-mr-1"></i>
                This device pushes attendance data to this server (ADMS protocol) - the server does not
                connect out to the device. On the device's own keypad, set <strong>Comm. &rarr; Cloud Server
                Setting / ADMS</strong>, point <strong>Server Address</strong> / <strong>Server Port</strong>
                at this server, and leave <strong>Request Path</strong> as <code>/iclock/cdata</code>.
              </div>
              <div class="alert alert-info" id="device-type-hint-ai07f"
                   style="font-size:0.85rem<?php echo $device_type!='ai07f' ? ';display:none' : ''; ?>">
                <i class="fa fa-info-circle tw-mr-1"></i>
                This device pushes attendance data to this server too (TIMY AiFace BS protocol) - on the
                device's own <strong>Comm. set &rarr; Server</strong> screen, set <strong>Server Req = Yes</strong>,
                <strong>Use domainNm = Yes</strong>, and point <strong>DomainNm</strong> / <strong>Port</strong>
                at this same CRM domain - no separate subdomain needed.
              </div>

              <div class="tw-flex tw-gap-2 tw-mt-3">
                <button type="submit" class="btn btn-primary">
                  <?php echo $editing ? _l('hr_save_changes') : 'Add Device'; ?>
                </button>
                <a href="<?php echo admin_url('hr_module/devices'); ?>" class="btn btn-default">
                  <?php echo _l('hr_cancel'); ?>
                </a>
              </div>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
$(function(){
    $('select[name="device_type"]').on('change', function(){
        var type = $(this).val();
        $('#device-type-hint-zkteco').toggle(type === 'zkteco');
        $('#device-type-hint-ai07f').toggle(type === 'ai07f');
    });
});
</script>
<?php init_tail(); ?>
