<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo $title; ?></h4>
          <div class="tw-flex tw-gap-2">
            <a href="<?php echo admin_url('hr_module/devices/sync_logs'); ?>" class="btn btn-default btn-sm">
              <i class="fa fa-list tw-mr-1"></i><?php echo _l('hr_zkteco_sync_logs'); ?>
            </a>
            <?php if (staff_can('create', 'hr_zkteco')): ?>
            <a href="<?php echo admin_url('hr_module/devices/add'); ?>" class="btn btn-primary">
              <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_zkteco_add_device'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>

        <?php
        $device_type_label = ['zkteco' => 'ZKTeco', 'aiface' => 'AiFace'];
        ?>
        <?php if (empty($devices)): ?>
        <div class="panel_s">
          <div class="panel-body text-center" style="padding:40px">
            <i class="fa fa-fingerprint" style="font-size:3rem;color:#cbd5e1"></i>
            <h5 class="tw-mt-3 text-muted">No attendance devices configured.</h5>
            <?php if (staff_can('create', 'hr_zkteco')): ?>
            <a href="<?php echo admin_url('hr_module/devices/add'); ?>" class="btn btn-primary tw-mt-3">
              <i class="fa-regular fa-plus tw-mr-1"></i>Add First Device
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php else: ?>
        <div class="row">
          <?php foreach ($devices as $device): ?>
          <div class="col-md-4">
            <div class="panel_s">
              <div class="panel-body">
                <div class="tw-flex tw-justify-between tw-items-start tw-mb-3">
                  <div>
                    <h5 class="tw-font-bold tw-mb-1"><?php echo htmlspecialchars($device->name); ?></h5>
                    <div class="tw-text-sm text-muted">
                      <i class="fa fa-map-marker tw-mr-1"></i><?php echo htmlspecialchars($device->location ?: 'No location'); ?>
                    </div>
                  </div>
                  <span class="label label-<?php echo $device->status ? 'success' : 'default'; ?>">
                    <?php echo $device->status ? 'Active' : 'Inactive'; ?>
                  </span>
                </div>

                <table class="table table-condensed tw-mb-3">
                  <tr><td class="text-muted" style="width:40%">Type</td><td><?php echo htmlspecialchars($device_type_label[$device->device_type] ?? $device->device_type); ?></td></tr>
                  <tr><td class="text-muted">Serial #</td><td><code><?php echo htmlspecialchars($device->serial_number ?: '—'); ?></code></td></tr>
                  <tr><td class="text-muted">Last Contact</td>
                      <td>
                        <?php
                        // Devices heartbeat/re-register every 30-300s depending on
                        // brand; allow a few missed beats before flagging offline.
                        $is_online = $device->last_sync_at
                            && (time() - strtotime($device->last_sync_at)) <= 300;
                        ?>
                        <span class="label label-<?php echo $is_online ? 'success' : 'default'; ?>">
                          <?php echo $is_online ? 'Online' : 'Offline'; ?>
                        </span>
                        <?php echo $device->last_sync_at ? date('d M Y H:i', strtotime($device->last_sync_at)) : '<span class="text-muted">Never</span>'; ?>
                      </td></tr>
                  <?php if ($device->ip_address): ?>
                  <tr><td class="text-muted">Last Seen IP</td><td><code><?php echo htmlspecialchars($device->ip_address); ?></code></td></tr>
                  <?php endif; ?>
                </table>

                <div class="tw-flex tw-gap-1 tw-flex-wrap">
                  <?php if (staff_can('edit', 'hr_zkteco')): ?>
                  <a href="<?php echo admin_url('hr_module/devices/edit/'.$device->id); ?>" class="btn btn-default btn-xs">
                    <i class="fa fa-edit"></i>
                  </a>
                  <?php endif; ?>
                  <?php if (staff_can('delete', 'hr_zkteco')): ?>
                  <a href="<?php echo admin_url('hr_module/devices/delete/'.$device->id); ?>" class="btn btn-default btn-xs _delete">
                    <i class="fa fa-trash"></i>
                  </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
