<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo $title; ?></h4>
          <div class="tw-flex tw-gap-2">
            <a href="<?php echo admin_url('hr_module/zkteco/sync_logs'); ?>" class="btn btn-default btn-sm">
              <i class="fa fa-list tw-mr-1"></i><?php echo _l('hr_zkteco_sync_logs'); ?>
            </a>
            <a href="<?php echo admin_url('hr_module/zkteco/mapping'); ?>" class="btn btn-info btn-sm">
              <i class="fa fa-link tw-mr-1"></i><?php echo _l('hr_zkteco_mapping'); ?>
            </a>
            <?php if (staff_can('create', 'hr_zkteco')): ?>
            <a href="<?php echo admin_url('hr_module/zkteco/add'); ?>" class="btn btn-primary btn-sm">
              <i class="fa fa-plus tw-mr-1"></i><?php echo _l('hr_zkteco_add_device'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>

        <?php if (empty($devices)): ?>
        <div class="panel_s">
          <div class="panel-body text-center" style="padding:40px">
            <i class="fa fa-fingerprint" style="font-size:3rem;color:#cbd5e1"></i>
            <h5 class="tw-mt-3 text-muted">No ZKTeco devices configured.</h5>
            <?php if (staff_can('create', 'hr_zkteco')): ?>
            <a href="<?php echo admin_url('hr_module/zkteco/add'); ?>" class="btn btn-primary tw-mt-3">
              <i class="fa fa-plus tw-mr-1"></i>Add First Device
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
                  <tr><td class="text-muted" style="width:40%">IP Address</td><td><code><?php echo $device->ip_address; ?></code></td></tr>
                  <tr><td class="text-muted">Port</td><td><?php echo $device->port; ?></td></tr>
                  <tr><td class="text-muted">Last Sync</td>
                      <td><?php echo $device->last_sync_at ? date('d M Y H:i', strtotime($device->last_sync_at)) : '<span class="text-muted">Never</span>'; ?></td></tr>
                  <?php if ($device->serial_number): ?>
                  <tr><td class="text-muted">Serial #</td><td><?php echo htmlspecialchars($device->serial_number); ?></td></tr>
                  <?php endif; ?>
                </table>

                <div class="tw-flex tw-gap-1 tw-flex-wrap">
                  <button class="btn btn-success btn-xs btn-test-conn" data-id="<?php echo $device->id; ?>"
                          data-name="<?php echo htmlspecialchars($device->name); ?>">
                    <i class="fa fa-plug tw-mr-1"></i>Test
                  </button>
                  <button class="btn btn-primary btn-xs btn-sync" data-id="<?php echo $device->id; ?>"
                          data-name="<?php echo htmlspecialchars($device->name); ?>">
                    <i class="fa fa-refresh tw-mr-1"></i>Sync Now
                  </button>
                  <?php if (staff_can('edit', 'hr_zkteco')): ?>
                  <a href="<?php echo admin_url('hr_module/zkteco/edit/'.$device->id); ?>" class="btn btn-default btn-xs">
                    <i class="fa fa-edit"></i>
                  </a>
                  <?php endif; ?>
                  <?php if (staff_can('delete', 'hr_zkteco')): ?>
                  <a href="<?php echo admin_url('hr_module/zkteco/delete/'.$device->id); ?>" class="btn btn-default btn-xs _delete">
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
<script>
$(function(){
    // Test connection
    $(document).on('click', '.btn-test-conn', function(){
        var id   = $(this).data('id');
        var name = $(this).data('name');
        var $btn = $(this).prop('disabled', true).text('Testing...');
        $.getJSON('<?php echo admin_url('hr_module/zkteco/test_connection/'); ?>' + id, function(res){
            alert_float(res.success ? 'success' : 'danger', name + ': ' + res.message);
        }).always(function(){
            $btn.prop('disabled', false).html('<i class="fa fa-plug mr-1"></i>Test');
        });
    });

    // Sync
    $(document).on('click', '.btn-sync', function(){
        var id   = $(this).data('id');
        var name = $(this).data('name');
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spin fa-refresh mr-1"></i>Syncing...');
        $.ajax({
            url: '<?php echo admin_url('hr_module/zkteco/sync/'); ?>' + id,
            type: 'POST',
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function(res){
                alert_float(res.success ? 'success' : 'danger',
                    name + ': ' + res.message);
                if (res.success) setTimeout(function(){ location.reload(); }, 1500);
            },
            error: function(){ alert_float('danger', 'Sync request failed.'); }
        }).always(function(){
            $btn.prop('disabled', false).html('<i class="fa fa-refresh mr-1"></i>Sync Now');
        });
    });
});
</script>
