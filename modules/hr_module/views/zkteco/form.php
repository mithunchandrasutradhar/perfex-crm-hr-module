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

              <div class="alert alert-info" style="font-size:0.85rem">
                <i class="fa fa-info-circle tw-mr-1"></i>
                This device pushes attendance data to this server (ADMS protocol) - the server does not
                connect out to the device. On the device's own keypad, set <strong>Comm. &rarr; Cloud Server
                Setting / ADMS</strong>, point <strong>Server Address</strong> / <strong>Server Port</strong>
                at this server, and leave <strong>Request Path</strong> as <code>/iclock/cdata</code>.
              </div>

              <div class="tw-flex tw-gap-2 tw-mt-3">
                <button type="submit" class="btn btn-primary">
                  <?php echo $editing ? _l('hr_save_changes') : 'Add Device'; ?>
                </button>
                <a href="<?php echo admin_url('hr_module/zkteco'); ?>" class="btn btn-default">
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
<?php init_tail(); ?>
