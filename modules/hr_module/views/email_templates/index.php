<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-4 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-3">
          <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
            <i class="fa fa-envelope-o tw-mr-2"></i><?php echo _l('hr_email_templates'); ?>
          </h4>
          <a href="<?php echo admin_url('hr_module/settings'); ?>" class="btn btn-default btn-sm">
            <i class="fa fa-arrow-left tw-mr-1"></i><?php echo _l('hr_back_to_settings'); ?>
          </a>
        </div>
        <p class="text-muted tw-mb-3"><?php echo _l('hr_email_templates_hint'); ?></p>

        <div class="panel_s">
          <div class="panel-body panel-table-full">
            <?php render_datatable([
              _l('hr_email_template_name'), _l('hr_email_template_subject'), _l('hr_email_template_updated'),
            ], 'hr-email-templates'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($can_edit): ?>
<div class="modal fade" id="editTemplateModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title" id="edit-template-name"><?php echo _l('hr_email_template_edit'); ?></h4>
    </div>
    <form id="editTemplateForm">
      <input type="hidden" name="id" value="">
      <div class="modal-body">
        <div class="form-group">
          <label><?php echo _l('hr_email_template_subject'); ?> <span class="text-danger">*</span></label>
          <input type="text" name="subject" class="form-control" required>
        </div>
        <div class="form-group">
          <label><?php echo _l('hr_email_template_body'); ?> <span class="text-danger">*</span></label>
          <textarea name="body" class="form-control" rows="12" required></textarea>
        </div>
        <div class="form-group tw-mb-0">
          <label class="tw-text-sm"><?php echo _l('hr_email_template_placeholders'); ?></label>
          <p class="tw-text-sm text-muted" id="edit-template-placeholders" style="font-family:monospace"></p>
        </div>
        <hr>
        <div class="form-group tw-mb-0">
          <label class="tw-text-sm"><?php echo _l('hr_email_template_send_test'); ?></label>
          <div class="input-group">
            <input type="email" id="test-email-address" class="form-control" placeholder="<?php echo _l('hr_email_template_test_email_placeholder'); ?>">
            <span class="input-group-btn">
              <button type="button" class="btn btn-default" id="send-test-email-btn"><?php echo _l('hr_email_template_send_test'); ?></button>
            </span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('hr_cancel'); ?></button>
        <button type="submit" class="btn btn-primary" id="save-template-btn"><?php echo _l('hr_save'); ?></button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>

<?php init_tail(); ?>
<script>
$(function(){
    initDataTable('.table-hr-email-templates', window.location.href, [], [0,'asc']);

    var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
    var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';

    $(document).on('click', '.hr-edit-template', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        $.getJSON('<?php echo admin_url('hr_module/email_templates/edit/'); ?>' + id, function(d){
            if (!d) return;
            $('#editTemplateForm input[name="id"]').val(d.id);
            $('#editTemplateForm input[name="subject"]').val(d.subject);
            $('#editTemplateForm textarea[name="body"]').val(d.body);
            $('#edit-template-name').text(d.name);
            $('#edit-template-placeholders').text(d.placeholders || '');
            $('#test-email-address').val('');
            $('#editTemplateModal').modal('show');
        });
    });

    $('#editTemplateForm').on('submit', function(e){
        e.preventDefault();
        var id  = $(this).find('input[name="id"]').val();
        var $btn = $('#save-template-btn').prop('disabled', true);
        $.post('<?php echo admin_url('hr_module/email_templates/edit/'); ?>' + id,
            $(this).serialize() + '&' + csrfName + '=' + csrfHash, function(r){
                if (r.success) {
                    alert_float('success', r.message);
                    $('#editTemplateModal').modal('hide');
                    $('.table-hr-email-templates').DataTable().ajax.reload(null, false);
                } else {
                    alert_float('danger', r.message);
                }
            }, 'json').always(function(){ $btn.prop('disabled', false); });
    });

    // Send Test Email - always sends the CURRENTLY EDITED subject/body (even if
    // not saved yet), so the admin can preview a change before committing it.
    $('#send-test-email-btn').on('click', function(){
        var id    = $('#editTemplateForm input[name="id"]').val();
        var email = $.trim($('#test-email-address').val());
        if (!email) {
            alert_float('danger', '<?php echo _l('hr_email_template_test_email_placeholder'); ?>');
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.post('<?php echo admin_url('hr_module/email_templates/send_test/'); ?>' + id,
            {
                test_email: email,
                subject: $('#editTemplateForm input[name="subject"]').val(),
                body: $('#editTemplateForm textarea[name="body"]').val(),
                [csrfName]: csrfHash
            }, function(r){
                alert_float(r.success ? 'success' : 'danger', r.message);
            }, 'json').always(function(){ $btn.prop('disabled', false); });
    });
});
</script>
