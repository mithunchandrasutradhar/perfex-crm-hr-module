<?php defined('BASEPATH') or exit('No direct script access allowed');
$is_edit = isset($employee) && $employee;
$e       = $is_edit ? $employee : (object)[];
function ev($obj, $key, $default = '') {
    return isset($obj->$key) ? htmlspecialchars($obj->$key) : $default;
}

?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">

    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/employees'); ?>"><?php echo _l('hr_menu_employees'); ?></a></li>
          <li class="active"><?php echo $title; ?></li>
        </ol>
      </div>
    </div>

    <?php echo form_open_multipart(current_url(), ['id' => 'employee-form']); ?>

    <!-- ── Staff Selection Card ── -->
    <div class="panel_s tw-mb-4" style="border:2px solid #3b82f6">
      <div class="panel-body">
        <h5 class="tw-font-semibold tw-mb-3" style="color:#3b82f6">
          <i class="fa fa-user-tie tw-mr-1"></i>
          <?php echo $is_edit ? 'Linked Staff Member' : 'Select Staff Member'; ?>
          <span class="text-danger">*</span>
        </h5>
        <div class="row">
          <div class="col-md-5">
            <div class="form-group tw-mb-0">
              <select name="staff_id" id="staff_id_select" class="form-control selectpicker" required
                      data-live-search="true"
                      data-none-selected-text="— Choose a staff member —"
                      data-width="100%">
                <option value="">— Choose a staff member —</option>
                <?php foreach ($staff_members as $s): ?>
                <?php
                // Only emit a photo URL when the resized thumbnail actually exists on
                // disk - otherwise the <img> 404s and shows a broken-image icon.
                $s_photo_url = '';
                if (!empty($s->profile_image)) {
                    $s_photo_path = 'uploads/staff_profile_images/' . $s->staffid . '/small_' . $s->profile_image;
                    if (file_exists($s_photo_path)) {
                        $s_photo_url = base_url($s_photo_path);
                    }
                }
                ?>
                <option value="<?php echo $s->staffid; ?>"
                        data-firstname="<?php echo htmlspecialchars($s->firstname); ?>"
                        data-lastname="<?php echo htmlspecialchars($s->lastname); ?>"
                        data-email="<?php echo htmlspecialchars($s->email); ?>"
                        data-phone="<?php echo htmlspecialchars($s->phonenumber ?? ''); ?>"
                        data-photo="<?php echo htmlspecialchars($s_photo_url); ?>"
                        <?php echo ($is_edit && $e->staff_id == $s->staffid) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($s->firstname . ' ' . $s->lastname); ?>
                  (<?php echo htmlspecialchars($s->email); ?>)
                </option>
                <?php endforeach; ?>
              </select>
              <?php if (!$is_edit): ?>
              <p class="help-block tw-text-xs tw-mt-1">
                Only staff members without an existing HR profile are shown.
              </p>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-7">
            <div id="staff-preview" style="display:none" class="tw-flex tw-items-center tw-gap-4 tw-p-3 tw-rounded-lg" style="background:#f0f9ff">
              <div id="staff-photo-wrap">
                <img id="staff-photo-img" src="" class="img-circle" width="56" height="56" style="object-fit:cover;display:none">
                <span id="staff-photo-initials" class="tw-inline-flex tw-items-center tw-justify-center tw-rounded-full tw-font-bold" style="width:56px;height:56px;border-radius:50%;background:#dbeafe;color:#1d4ed8;font-size:20px;display:none"></span>
              </div>
              <div>
                <div class="tw-font-semibold tw-text-base" id="preview-name"></div>
                <div class="text-muted tw-text-sm" id="preview-email"></div>
                <div class="text-muted tw-text-sm" id="preview-phone"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs tw-mb-4" id="emp-tabs">
      <li class="active"><a href="#tab-work" data-toggle="tab"><i class="fa fa-briefcase tw-mr-1"></i><?php echo _l('hr_employee_work_info'); ?></a></li>
      <li><a href="#tab-personal" data-toggle="tab"><i class="fa fa-id-card tw-mr-1"></i><?php echo _l('hr_employee_personal_info'); ?></a></li>
      <li><a href="#tab-bank" data-toggle="tab"><i class="fa fa-university tw-mr-1"></i><?php echo _l('hr_employee_bank_info'); ?></a></li>
    </ul>

    <div class="tab-content">

      <!-- ── Tab 1: Work Info ── -->
      <div class="tab-pane active" id="tab-work">
        <div class="panel_s">
          <div class="panel-body">
            <div class="row">
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_code'); ?> <span class="text-danger">*</span></label>
                  <input type="text" name="employee_code" class="form-control" required
                    value="<?php echo $is_edit ? ev($e,'employee_code') : $next_code; ?>">
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_joining_date'); ?></label>
                  <div class="input-group date">
                    <input type="text" name="joining_date" class="form-control datepicker" autocomplete="off"
                      value="<?php echo $is_edit ? _d(ev($e,'joining_date')) : ''; ?>">
                    <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_end_date'); ?></label>
                  <div class="input-group date">
                    <input type="text" name="end_date" class="form-control datepicker" autocomplete="off"
                      value="<?php echo $is_edit ? _d(ev($e,'end_date')) : ''; ?>">
                    <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_basic_salary'); ?></label>
                  <input type="number" name="basic_salary" class="form-control" step="0.01" min="0"
                    value="<?php echo $is_edit ? ev($e,'basic_salary','0') : '0'; ?>">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 col-sm-6">
                <div class="form-group select-placeholder">
                  <label><?php echo _l('hr_department'); ?></label>
                  <select name="department_id" id="emp_dept" class="selectpicker" data-width="100%" data-live-search="true">
                    <option value=""><?php echo _l('hr_select'); ?></option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?php echo $d->id; ?>" <?php if($is_edit && $e->department_id == $d->id) echo 'selected'; ?>>
                      <?php echo htmlspecialchars($d->name); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6 col-sm-6">
                <div class="form-group select-placeholder">
                  <label><?php echo _l('hr_designation'); ?></label>
                  <select name="designation_id" id="emp_designation" class="selectpicker" data-width="100%" data-live-search="true">
                    <option value=""><?php echo _l('hr_select'); ?></option>
                    <?php foreach ($designations as $ds): ?>
                    <option value="<?php echo $ds->id; ?>" <?php if($is_edit && $e->designation_id == $ds->id) echo 'selected'; ?>>
                      <?php echo htmlspecialchars($ds->name); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label><?php echo _l('hr_notes'); ?></label>
              <textarea name="notes" class="form-control" rows="2"><?php echo $is_edit ? ev($e,'notes') : ''; ?></textarea>
            </div>
            <div class="form-group">
              <label class="tw-block tw-mb-1"><?php echo _l('hr_status'); ?></label>
              <?php if ($is_edit): ?>
                <?php if ($e->staff_active == 1): ?>
                <span class="label label-success"><?php echo _l('hr_active'); ?></span>
                <?php else: ?>
                <span class="label label-danger"><?php echo _l('hr_inactive'); ?></span>
                <?php endif; ?>
              <?php endif; ?>
              <p class="help-block tw-text-xs tw-mt-1 tw-mb-0">
                <?php echo _l('hr_employee_status_follows_staff'); ?>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Tab 2: Personal Info (HR-specific) ── -->
      <div class="tab-pane" id="tab-personal">
        <div class="panel_s">
          <div class="panel-body">
            <p class="text-info tw-text-sm tw-mb-3">
              <i class="fa fa-info-circle tw-mr-1"></i>
              Name, email, and phone are taken from the linked staff member above.
              Fill in the additional HR-specific personal details below.
            </p>

            <div class="row">
              <div class="col-md-3">
                <div class="form-group tw-text-center">
                  <label class="tw-block tw-mb-2">HR Profile Photo <small class="text-muted">(optional, overrides staff photo)</small></label>
                  <div id="photo-preview" class="tw-mb-2">
                    <?php if ($is_edit && $e->photo): ?>
                    <img src="<?php echo admin_url('hr_module/employees/photo/' . $e->id); ?>"
                      class="img-circle" width="80" height="80" style="object-fit:cover" id="preview-img">
                    <?php else: ?>
                    <div id="preview-img" style="width:80px;height:80px;border-radius:50%;background:#e2e8f0;display:inline-flex;align-items:center;justify-content:center;">
                      <i class="fa fa-camera fa-2x" style="color:#94a3b8"></i>
                    </div>
                    <?php endif; ?>
                  </div>
                  <label class="btn btn-default btn-xs">
                    <i class="fa fa-camera"></i> <?php echo _l('hr_employee_photo'); ?>
                    <input type="file" name="photo" id="photo-input" accept="image/*" style="display:none">
                  </label>
                </div>
              </div>
              <div class="col-md-9">
                <div class="row">
                  <div class="col-md-4 col-sm-6">
                    <div class="form-group select-placeholder">
                      <label><?php echo _l('hr_gender'); ?></label>
                      <select name="gender" class="selectpicker" data-width="100%">
                        <option value=""><?php echo _l('hr_select'); ?></option>
                        <option value="male"   <?php if($is_edit && $e->gender=='male')   echo 'selected'; ?>>Male</option>
                        <option value="female" <?php if($is_edit && $e->gender=='female') echo 'selected'; ?>>Female</option>
                        <option value="other"  <?php if($is_edit && $e->gender=='other')  echo 'selected'; ?>>Other</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                      <label><?php echo _l('hr_employee_dob'); ?></label>
                      <div class="input-group date">
                        <input type="text" name="date_of_birth" class="form-control datepicker" autocomplete="off"
                          value="<?php echo $is_edit ? _d(ev($e,'date_of_birth')) : ''; ?>">
                        <div class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4 col-sm-6">
                    <div class="form-group select-placeholder">
                      <label><?php echo _l('hr_employee_blood_group'); ?></label>
                      <select name="blood_group" class="selectpicker" data-width="100%">
                        <option value=""><?php echo _l('hr_select'); ?></option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                        <option value="<?php echo $bg; ?>" <?php if($is_edit && $e->blood_group==$bg) echo 'selected'; ?>><?php echo $bg; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4 col-sm-6">
                    <div class="form-group select-placeholder">
                      <label><?php echo _l('hr_employee_marital_status'); ?></label>
                      <select name="marital_status" class="selectpicker" data-width="100%">
                        <option value=""><?php echo _l('hr_select'); ?></option>
                        <?php foreach (['Single','Married','Divorced','Widowed'] as $ms): ?>
                        <option value="<?php echo strtolower($ms); ?>" <?php if($is_edit && $e->marital_status==strtolower($ms)) echo 'selected'; ?>><?php echo $ms; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                      <label><?php echo _l('hr_employee_religion'); ?></label>
                      <input type="text" name="religion" class="form-control" value="<?php echo $is_edit ? ev($e,'religion') : ''; ?>">
                    </div>
                  </div>
                  <div class="col-md-4 col-sm-6">
                    <div class="form-group">
                      <label><?php echo _l('hr_employee_nid'); ?></label>
                      <input type="text" name="nid_number" class="form-control" value="<?php echo $is_edit ? ev($e,'nid_number') : ''; ?>">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_passport'); ?></label>
                  <input type="text" name="passport_number" class="form-control" value="<?php echo $is_edit ? ev($e,'passport_number') : ''; ?>">
                </div>
              </div>
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_emergency_contact'); ?> (Name)</label>
                  <input type="text" name="emergency_contact_name" class="form-control" value="<?php echo $is_edit ? ev($e,'emergency_contact_name') : ''; ?>">
                </div>
              </div>
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_emergency_contact'); ?> (Phone)</label>
                  <input type="text" name="emergency_contact_phone" class="form-control" value="<?php echo $is_edit ? ev($e,'emergency_contact_phone') : ''; ?>">
                </div>
              </div>
            </div>

            <div class="form-group">
              <label><?php echo _l('hr_address'); ?></label>
              <textarea name="address" class="form-control" rows="2"><?php echo $is_edit ? ev($e,'address') : ''; ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Tab 3: Bank Info ── -->
      <div class="tab-pane" id="tab-bank">
        <div class="panel_s">
          <div class="panel-body">
            <div class="row">
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_bank_name'); ?></label>
                  <input type="text" name="bank_name" class="form-control" value="<?php echo $is_edit ? ev($e,'bank_name') : ''; ?>">
                </div>
              </div>
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_bank_account'); ?></label>
                  <input type="text" name="bank_account" class="form-control" value="<?php echo $is_edit ? ev($e,'bank_account') : ''; ?>">
                </div>
              </div>
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_bank_branch'); ?></label>
                  <input type="text" name="bank_branch" class="form-control" value="<?php echo $is_edit ? ev($e,'bank_branch') : ''; ?>">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_tin'); ?></label>
                  <input type="text" name="tin_number" class="form-control" value="<?php echo $is_edit ? ev($e,'tin_number') : ''; ?>">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /tab-content -->

    <div class="row tw-mt-2">
      <div class="col-md-12">
        <a href="<?php echo admin_url('hr_module/employees'); ?>" class="btn btn-default">
          <i class="fa fa-arrow-left tw-mr-1"></i><?php echo _l('hr_back'); ?>
        </a>
        <button type="submit" class="btn btn-primary">
          <?php echo _l('hr_save'); ?>
        </button>
      </div>
    </div>

    <?php echo form_close(); ?>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    var ajaxUrl = '<?php echo admin_url('hr_module/employees/get_staff_info'); ?>';

    function showStaffPreview(staffId, firstname, lastname, email, phone, photo) {
        $('#staff-preview').show();
        var fullname = firstname + ' ' + lastname;
        $('#preview-name').text(fullname);
        $('#preview-email').text(email || '');
        $('#preview-phone').text(phone || '');
        if (photo) {
            $('#staff-photo-img').attr('src', photo).show();
            $('#staff-photo-initials').hide();
        } else {
            $('#staff-photo-img').hide();
            $('#staff-photo-initials').text(firstname.charAt(0).toUpperCase()).show();
        }
    }

    // Staff dropdown change → show preview via inline data attributes
    $('#staff_id_select').on('change', function(){
        var $opt = $(this).find('option:selected');
        var staffId = $(this).val();
        if (!staffId) { $('#staff-preview').hide(); return; }

        var fn    = $opt.data('firstname');
        var ln    = $opt.data('lastname');
        var email = $opt.data('email');
        var phone = $opt.data('phone');
        var photo = $opt.data('photo');
        showStaffPreview(staffId, fn, ln, email, phone, photo);
    });

    // Trigger on load for edit mode
    <?php if ($is_edit && $e->staff_id): ?>
    $('#staff_id_select').trigger('change');
    <?php endif; ?>

    // Photo preview
    $('#photo-input').on('change', function(){
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(e){
            $('#photo-preview').html('<img src="' + e.target.result + '" class="img-circle" width="80" height="80" style="object-fit:cover" id="preview-img">');
        };
        reader.readAsDataURL(file);
    });
});
</script>
