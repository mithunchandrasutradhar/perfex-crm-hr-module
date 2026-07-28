<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/** @var object|null $policy         */
/** @var bool        $is_global      */
/** @var int|null    $own_department */
/** @var array       $departments    */
if (!isset($policy))         $policy         = null;
if (!isset($is_global))      $is_global      = false;
if (!isset($own_department)) $own_department = null;
if (!isset($departments))    $departments    = [];

$editing = (bool) $policy;
$action  = $editing
    ? admin_url('hr_module/policies/edit/' . $policy->id)
    : admin_url('hr_module/policies/add');

$cur_type           = $editing ? $policy->type : 'private';
$cur_department_ids = $editing ? $policy->department_id_list : ($own_department ? [(int) $own_department] : []);
$existing_attachments = $editing ? $this->Policies_model->decode_attachments($policy->attachment) : [];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="tw-font-semibold tw-mb-0"><?php echo $editing ? 'Edit Policy' : 'Add Policy'; ?></h4>
          </div>
          <div class="panel-body">
            <?php if ($editing): ?>
            <div class="alert alert-info">
              This will be submitted as an update for admin review. The current version stays visible to employees until it's approved.
            </div>
            <?php endif; ?>

            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>

              <div class="form-group">
                <label>Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required
                       value="<?php echo $editing ? htmlspecialchars($policy->title) : ''; ?>">
              </div>

              <?php if ($is_global): ?>
              <div class="form-group">
                <label>Visibility</label>
                <div class="radio radio-primary">
                  <input type="radio" name="type" value="public" id="policy-type-public" <?php echo $cur_type === 'public' ? 'checked' : ''; ?>>
                  <label for="policy-type-public">Public - visible to all employees</label>
                </div>
                <div class="radio radio-primary">
                  <input type="radio" name="type" value="private" id="policy-type-private" <?php echo $cur_type === 'private' ? 'checked' : ''; ?>>
                  <label for="policy-type-private">Private - visible to one or more departments</label>
                </div>
              </div>
              <div class="form-group" id="policy-department-group">
                <label>Departments <span class="text-danger">*</span></label>
                <select name="department_ids[]" multiple class="form-control selectpicker" data-live-search="true" data-actions-box="true">
                  <?php foreach ($departments as $d): ?>
                  <option value="<?php echo $d->id; ?>" <?php echo in_array((int) $d->id, $cur_department_ids, true) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($d->name); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php else: ?>
              <input type="hidden" name="type" value="private">
              <div class="form-group">
                <label>Department</label>
                <p class="form-control-static"><?php
                  $own_name = '-';
                  foreach ($departments as $d) { if ((int) $d->id === (int) $own_department) { $own_name = $d->name; break; } }
                  echo htmlspecialchars($own_name);
                ?></p>
                <p class="text-muted tw-text-sm">You can only manage policies for your own department.</p>
              </div>
              <?php endif; ?>

              <p class="text-muted tw-text-sm">Provide text content, a PDF file, or both.</p>

              <div class="form-group">
                <?= render_textarea('content', 'Text Content', $editing ? $policy->content : '', ['rows' => 12], [], '', 'tinymce'); ?>
              </div>

              <?php if (!empty($existing_attachments)): ?>
              <div class="form-group">
                <label>Current Files</label>
                <?php foreach ($existing_attachments as $a): ?>
                <div class="checkbox checkbox-danger">
                  <input type="checkbox" name="remove_attachments[]" value="<?php echo htmlspecialchars($a['file']); ?>"
                         id="remove-att-<?php echo md5($a['file']); ?>">
                  <label for="remove-att-<?php echo md5($a['file']); ?>">
                    <i class="fa fa-file-pdf tw-mr-1"></i><?php echo htmlspecialchars($a['name'] ?: $a['file']); ?>
                    <span class="text-danger tw-text-sm">(check to remove)</span>
                  </label>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <div class="form-group">
                <label>PDF File(s)</label>
                <input type="file" name="attachments[]" class="form-control" accept="application/pdf" multiple>
                <p class="text-muted tw-text-sm tw-mt-1">You can select multiple files. New files are added to any kept above.</p>
              </div>

              <hr>
              <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Submit Update' : 'Submit For Approval'; ?></button>
              <a href="<?php echo admin_url('hr_module/policies'); ?>" class="btn btn-default">Cancel</a>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    function toggleDeptGroup() {
        var isPrivate = $('input[name="type"]:checked').val() === 'private' || $('input[name="type"]').length === 0;
        $('#policy-department-group').toggle(isPrivate);
    }
    $('input[name="type"]').on('change', toggleDeptGroup);
    toggleDeptGroup();
});
</script>
