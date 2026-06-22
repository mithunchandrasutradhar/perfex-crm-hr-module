<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/zkteco'); ?>"><?php echo _l('hr_zkteco_devices'); ?></a></li>
          <li class="active"><?php echo $title; ?></li>
        </ol>

        <div class="row">
          <!-- Add / Edit Mapping -->
          <div class="col-md-4">
            <div class="panel_s">
              <div class="panel-heading">
                <h5 class="tw-font-semibold tw-mb-0">Add / Update Mapping</h5>
              </div>
              <div class="panel-body">
                <?php echo form_open(admin_url('hr_module/zkteco/mapping')); ?>
                  <div class="form-group">
                    <label><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-control" required>
                      <option value=""><?php echo _l('hr_select'); ?></option>
                      <?php foreach ($employees as $id => $name): ?>
                      <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Device <span class="text-danger">*</span></label>
                    <select name="device_id" class="form-control" required>
                      <option value=""><?php echo _l('hr_select'); ?></option>
                      <?php foreach ($devices as $d): ?>
                      <option value="<?php echo $d->id; ?>"><?php echo htmlspecialchars($d->name); ?> (<?php echo $d->ip_address; ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Device User ID <span class="text-danger">*</span> <small class="text-muted">(ID shown on device)</small></label>
                    <input type="text" name="device_user_id" class="form-control" required
                           placeholder="e.g. 1, 100, EMP001">
                  </div>
                  <div class="alert alert-info" style="font-size:0.8rem">
                    <i class="fa fa-info-circle tw-mr-1"></i>
                    The Device User ID is the enrollment number assigned on the ZKTeco device.
                    Check the device's user management to find this number.
                  </div>
                  <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa fa-save tw-mr-1"></i>Save Mapping
                  </button>
                <?php echo form_close(); ?>
              </div>
            </div>
          </div>

          <!-- Mapping List -->
          <div class="col-md-8">
            <div class="panel_s">
              <div class="panel-heading">
                <h5 class="tw-font-semibold tw-mb-0">
                  Current Mappings
                  <span class="badge"><?php echo count($mappings); ?></span>
                </h5>
              </div>
              <div class="panel-body panel-table-full">
                <?php if (empty($mappings)): ?>
                <div class="text-center text-muted" style="padding:30px">
                  <i class="fa fa-unlink" style="font-size:2rem;opacity:0.3"></i>
                  <p class="tw-mt-2">No employee-device mappings yet.</p>
                </div>
                <?php else: ?>
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th><?php echo _l('hr_employee'); ?></th>
                      <th>Device</th>
                      <th>Device User ID</th>
                      <th>Mapped On</th>
                      <th><?php echo _l('hr_actions'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($mappings as $m): ?>
                    <tr>
                      <td>
                        <?php echo htmlspecialchars($m->first_name.' '.$m->last_name); ?>
                        <br><small class="text-muted"><?php echo $m->employee_code; ?></small>
                      </td>
                      <td>
                        <?php echo htmlspecialchars($m->device_name ?? 'Unknown'); ?>
                      </td>
                      <td><code><?php echo htmlspecialchars($m->device_user_id); ?></code></td>
                      <td><?php echo date('d M Y', strtotime($m->created_at)); ?></td>
                      <td>
                        <a href="<?php echo admin_url('hr_module/zkteco/delete_mapping/'.$m->id); ?>"
                           class="btn btn-default btn-xs _delete">
                          <i class="fa fa-trash"></i>
                        </a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
