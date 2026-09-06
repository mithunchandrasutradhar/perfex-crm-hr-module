<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/** @var bool  $is_global        */
/** @var bool  $can_manage_own   */
/** @var array $departments      */
/** @var bool  $has_pending      */
if (!isset($is_global))         $is_global         = false;
if (!isset($can_manage_own))    $can_manage_own    = false;
if (!isset($departments))       $departments       = [];
if (!isset($has_pending))       $has_pending       = false;
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">

    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-2 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-2">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
            <i class="fa fa-file-shield tw-mr-2 text-primary"></i>Policies
          </h4>
          <div class="tw-flex tw-flex-wrap tw-gap-2">
            <?php if ($is_global): ?>
            <select id="f-dept" class="selectpicker" data-width="150px" data-live-search="true">
              <option value="">All Visibility</option>
              <option value="public">Public</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?></option>
              <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <select id="f-type" class="selectpicker" data-width="120px">
              <option value="">All Types</option>
              <option value="public">Public</option>
              <option value="private">Private</option>
            </select>
            <?php if ($can_manage_own): ?>
            <a href="<?php echo admin_url('hr_module/policies/add'); ?>" class="btn btn-primary">
              <i class="fa-regular fa-plus tw-mr-1"></i>Add Policy
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <?php if ($is_global && $has_pending): ?>
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading">
            <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-clock tw-mr-2 text-warning"></i>Pending Approval</h5>
          </div>
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              'Title', 'Type', 'Visibility', 'Submitted By', 'Date', '',
            ], 'hr-policies-pending'); ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable(['Title', 'Type', 'Visibility', 'Status', 'Content', 'Published'], 'hr-policies'); ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    <?php if ($is_global && $has_pending): ?>
    initDataTable('.table-hr-policies-pending', '<?php echo admin_url('hr_module/policies/pending_table'); ?>', [], [], [], [4,'desc']);
    <?php endif; ?>
    initDataTable('.table-hr-policies', window.location.href, [], [], [], [5,'desc']);
    function reload() {
        var url = window.location.href.split('?')[0]
            + '?department_id=' + (typeof $('#f-dept').val() !== 'undefined' ? $('#f-dept').val() : '')
            + '&type='          + $('#f-type').val();
        $('.table-hr-policies').DataTable().ajax.url(url).load();
    }
    $('#f-dept,#f-type').on('change changed.bs.select', reload);
});
</script>
