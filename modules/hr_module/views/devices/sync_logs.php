<?php defined('BASEPATH') or exit('No direct script access allowed');
$status_badge = ['success' => 'success', 'failed' => 'danger', 'partial' => 'warning'];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo $title; ?></h4>
          <div class="tw-flex tw-gap-2">
            <select id="f-device" class="form-control input-sm" style="width:180px">
              <option value="">All Devices</option>
              <?php foreach ($devices as $d): ?>
              <option value="<?php echo $d->id; ?>" <?php if($current_device==$d->id) echo 'selected'; ?>>
                <?php echo htmlspecialchars($d->name); ?>
              </option>
              <?php endforeach; ?>
            </select>
            <a href="<?php echo admin_url('hr_module/devices'); ?>" class="btn btn-default btn-sm">
              <i class="fa fa-arrow-left tw-mr-1"></i>Back to Devices
            </a>
          </div>
        </div>

        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Device</th>
                  <th>Sync Time</th>
                  <th>Fetched</th>
                  <th>Saved</th>
                  <th>Status</th>
                  <th>Error / Notes</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:30px">No sync logs yet.</td></tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                  <td><?php echo htmlspecialchars($log->device_name ?? 'Unknown'); ?>
                    <br><small class="text-muted"><?php echo htmlspecialchars($log->ip_address ?? ''); ?></small>
                  </td>
                  <td><?php echo date('d M Y H:i:s', strtotime($log->sync_at)); ?></td>
                  <td><strong><?php echo number_format($log->records_fetched); ?></strong></td>
                  <td><strong class="text-success"><?php echo number_format($log->records_saved); ?></strong></td>
                  <td>
                    <span class="label label-<?php echo $status_badge[$log->status] ?? 'default'; ?>">
                      <?php echo ucfirst($log->status); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($log->error_message): ?>
                    <small class="text-danger"><?php echo htmlspecialchars($log->error_message); ?></small>
                    <?php else: ?>
                    <small class="text-muted">-</small>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$('#f-device').on('change', function(){
    var id = $(this).val();
    window.location.href = '<?php echo admin_url('hr_module/devices/sync_logs'); ?>'
        + (id ? '?device_id=' + id : '');
});
</script>
