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

              <div class="form-group">
                <label>Effective Date <i class="fa-solid fa-circle-info tw-text-neutral-400" data-toggle="tooltip" data-title="The date this policy is meant to take effect. Defaults to today - only set an earlier date if this policy is meant to apply retroactively." style="cursor:help;"></i></label>
                <div class="input-group date">
                  <input type="text" name="effective_date" class="form-control datepicker" autocomplete="off"
                         value="<?php echo $editing && $policy->effective_date ? _d($policy->effective_date) : _d(date('Y-m-d')); ?>">
                  <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                </div>
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
                <?php // "tinymce-manual" opts this one out of the app-wide init_editor()
                      // call every admin page already makes for any plain ".tinymce"
                      // textarea, so it can be initialized below instead with a Bangla
                      // font option added to the font-family dropdown - the global
                      // editor config (and every other textarea using it) is untouched. ?>
                <?= render_textarea('content', 'Text Content', $editing ? $policy->content : '', ['rows' => 12], [], '', 'tinymce tinymce-manual'); ?>
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

    // Same defaults init_editor() (main.js) already applies everywhere else
    // (toolbar, plugins, height, etc.) - font_family_formats is TinyMCE's
    // full dropdown list, which an override replaces rather than merges
    // into, so the standard set is repeated here with a Bangla option
    // appended, rather than losing the existing Latin fonts.
    init_editor('#content', {
        font_family_formats:
            "Andale Mono=andale mono,times;" +
            "Arial=arial,helvetica,sans-serif;" +
            "Arial Black=arial black,avant garde;" +
            "Book Antiqua=book antiqua,palatino;" +
            "Comic Sans MS=comic sans ms,sans-serif;" +
            "Courier New=courier new,courier;" +
            "Georgia=georgia,palatino;" +
            "Helvetica=helvetica;" +
            "Impact=impact,chicago;" +
            "Symbol=symbol;" +
            "Tahoma=tahoma,arial,helvetica,sans-serif;" +
            "Terminal=terminal,monaco;" +
            "Times New Roman=times new roman,times;" +
            "Trebuchet MS=trebuchet ms,geneva;" +
            "Verdana=verdana,geneva;" +
            "Webdings=webdings;" +
            "Wingdings=wingdings,zapf dingbats;" +
            "Bangla='Noto Sans Bengali',SolaimanLipi,Kalpurush,Nikosh,sans-serif;",
        // The editor's text area is its own iframe - a font loaded on this
        // parent page (or the view page's stylesheet) never reaches it, so
        // without this the "Bangla" choice above silently falls back to
        // whatever generic font the browser/OS substitutes, which usually
        // doesn't match what actually renders once published. content_style
        // is TinyMCE's own way to inject CSS straight into that iframe. The
        // body rule matches policies/view.php's .policy-content default, so
        // typing here already looks the same as the published result -
        // Bengali script renders in this font, anything else (English words
        // mixed in) falls through to Arial automatically.
        content_style: "@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali&display=swap'); body { font-family: 'Noto Sans Bengali', Arial, sans-serif; }",
    });
});
</script>
