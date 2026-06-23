<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/helpdesk'); ?>"><?php echo _l('hr_helpdesk_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_helpdesk_add'); ?></li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo _l('hr_helpdesk_add'); ?></h4>
            <?php echo form_open_multipart(admin_url('hr_module/helpdesk/submit')); ?>
              <div class="form-group">
                <label><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-control" required
                  <?php if (!empty($own_only)) echo 'disabled'; ?>>
                  <option value=""><?php echo _l('hr_select'); ?></option>
                  <?php foreach ($employees as $id => $name): ?>
                  <option value="<?php echo $id; ?>" <?php if (!empty($own_only)) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($name); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
                <?php if (!empty($own_only)): ?>
                <input type="hidden" name="employee_id" value="<?php echo (int) $own_emp_id; ?>">
                <?php endif; ?>
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_helpdesk_subject'); ?> <span class="text-danger">*</span></label>
                <input type="text" name="subject" class="form-control" required
                       placeholder="Brief description of your issue">
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?php echo _l('hr_helpdesk_category'); ?></label>
                    <select name="category" class="form-control">
                      <option value="">-- Select Category --</option>
                      <option value="Payroll">Payroll</option>
                      <option value="Leave">Leave</option>
                      <option value="Attendance">Attendance</option>
                      <option value="Policy">Policy</option>
                      <option value="Benefits">Benefits</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?php echo _l('hr_helpdesk_priority'); ?></label>
                    <select name="priority" class="form-control">
                      <option value="low">Low</option>
                      <option value="medium" selected>Medium</option>
                      <option value="high">High</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_helpdesk_message'); ?> <span class="text-danger">*</span></label>
                <textarea name="message" class="form-control" rows="6" required
                          placeholder="Describe your issue in detail..."></textarea>
              </div>
              <div class="form-group">
                <label>Attachment <small class="text-muted">(PDF/DOC/Image, max 5MB)</small></label>
                <input type="file" name="attachment" class="form-control"
                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
              </div>
              <div class="tw-flex tw-gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane tw-mr-1"></i>Submit Ticket</button>
                <a href="<?php echo admin_url('hr_module/helpdesk'); ?>" class="btn btn-default">Cancel</a>
              </div>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
