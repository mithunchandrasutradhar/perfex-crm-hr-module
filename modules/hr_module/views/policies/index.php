<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/** @var bool  $is_global        */
/** @var bool  $can_manage_own   */
/** @var array $departments      */
/** @var array $pending          */
/** @var array $pending_revisions */
if (!isset($is_global))         $is_global         = false;
if (!isset($can_manage_own))    $can_manage_own    = false;
if (!isset($departments))       $departments       = [];
if (!isset($pending))           $pending           = [];
if (!isset($pending_revisions)) $pending_revisions = [];
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

    <?php if ($is_global && (!empty($pending) || !empty($pending_revisions))): ?>
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading">
            <h5 class="tw-font-semibold tw-mb-0"><i class="fa fa-clock tw-mr-2 text-warning"></i>Pending Approval</h5>
          </div>
          <div class="panel-body panel-table-full">
            <table class="table table-condensed table-hover tw-mb-0">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Type</th>
                  <th>Visibility</th>
                  <th>Submitted By</th>
                  <th>Date</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pending as $p): ?>
                <tr>
                  <td><?php echo htmlspecialchars($p->title); ?></td>
                  <td><span class="label label-info">New Policy</span></td>
                  <td><?php echo $p->type === 'public' ? 'Public' : htmlspecialchars($p->department_names ?: '-'); ?></td>
                  <td><?php echo htmlspecialchars($p->created_by_name ?: '-'); ?></td>
                  <td><?php echo _dt($p->created_at); ?></td>
                  <td><a href="<?php echo admin_url('hr_module/policies/view/' . $p->id); ?>" class="btn btn-default btn-xs">Review</a></td>
                </tr>
                <?php endforeach; ?>
                <?php foreach ($pending_revisions as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r->policy_title); ?></td>
                  <td><span class="label label-warning">Update</span></td>
                  <td><?php echo $r->type === 'public' ? 'Public' : htmlspecialchars($r->department_names ?: '-'); ?></td>
                  <td><?php echo htmlspecialchars($r->submitted_by_name ?: '-'); ?></td>
                  <td><?php echo _dt($r->created_at); ?></td>
                  <td><a href="<?php echo admin_url('hr_module/policies/view/' . $r->policy_id); ?>" class="btn btn-default btn-xs">Review</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable(['Title', 'Type', 'Visibility', 'Content', 'Published'], 'hr-policies'); ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-policies', window.location.href, [], [4,'desc']);
    function reload() {
        var url = window.location.href.split('?')[0]
            + '?department_id=' + (typeof $('#f-dept').val() !== 'undefined' ? $('#f-dept').val() : '')
            + '&type='          + $('#f-type').val();
        $('.table-hr-policies').DataTable().ajax.url(url).load();
    }
    $('#f-dept,#f-type').on('change changed.bs.select', reload);
});
</script>
