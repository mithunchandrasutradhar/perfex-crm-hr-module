<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/devices'); ?>"><?php echo _l('hr_zkteco_devices'); ?></a></li>
          <li class="active"><?php echo $title; ?></li>
        </ol>

        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo $title; ?></h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <select id="f-device" class="selectpicker" data-width="220px" data-live-search="true"
                    data-none-selected-text="All Devices">
              <option value="">All Devices</option>
              <?php foreach ($devices as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <a href="<?php echo admin_url('hr_module/devices'); ?>" class="btn btn-default btn-sm">
              <i class="fa fa-arrow-left tw-mr-1"></i>Back to Devices
            </a>
          </div>
        </div>

        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              'Device', 'Sync Time', 'Fetched', 'Saved', 'Status', 'Error / Notes',
            ], 'device-sync-logs'); ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-device-sync-logs', window.location.href, [], [1, 'desc']);

    function reload() {
        var url = window.location.href.split('?')[0]
            + '?device_id=' + $('#f-device').val();
        $('.table-device-sync-logs').DataTable().ajax.url(url).load();
    }
    $('#f-device').on('change changed.bs.select', reload);
});
</script>
