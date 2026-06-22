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

    <!-- Breadcrumb -->
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/employees'); ?>"><?php echo _l('hr_menu_employees'); ?></a></li>
          <li class="active"><?php echo $title; ?></li>
        </ol>
      </div>
    </div>

    <?php echo form_open_multipart(current_url(), ['id' => 'employee-form']); ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs tw-mb-4" id="emp-tabs">
      <li class="active"><a href="#tab-work" data-toggle="tab"><i class="fa fa-briefcase tw-mr-1"></i><?php echo _l('hr_employee_work_info'); ?></a></li>
      <li><a href="#tab-personal" data-toggle="tab"><i class="fa fa-user tw-mr-1"></i><?php echo _l('hr_employee_personal_info'); ?></a></li>
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
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_end_date'); ?></label>
                  <div class="input-group date">
                    <input type="text" name="end_date" class="form-control datepicker" autocomplete="off"
                      value="<?php echo $is_edit ? _d(ev($e,'end_date')) : ''; ?>">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
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
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_department'); ?></label>
                  <select name="department_id" id="emp_dept" class="form-control">
                    <option value=""><?php echo _l('hr_select'); ?></option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?php echo $d->id; ?>" <?php if($is_edit && $e->department_id == $d->id) echo 'selected'; ?>>
                      <?php echo htmlspecialchars($d->name); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_designation'); ?></label>
                  <select name="designation_id" id="emp_designation" class="form-control">
                    <option value=""><?php echo _l('hr_select'); ?></option>
                    <?php foreach ($designations as $ds): ?>
                    <option value="<?php echo $ds->id; ?>" <?php if($is_edit && $e->designation_id == $ds->id) echo 'selected'; ?>>
                      <?php echo htmlspecialchars($ds->name); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_staff_linked'); ?></label>
                  <select name="staff_id" class="form-control">
                    <option value=""><?php echo _l('hr_none'); ?></option>
                    <?php foreach ($staff_members as $s): ?>
                    <option value="<?php echo $s->staffid; ?>" <?php if($is_edit && $e->staff_id == $s->staffid) echo 'selected'; ?>>
                      <?php echo htmlspecialchars($s->fullname); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label><?php echo _l('hr_notes'); ?></label>
                  <textarea name="notes" class="form-control" rows="3"><?php echo $is_edit ? ev($e,'notes') : ''; ?></textarea>
                </div>
              </div>
            </div>

            <div class="form-group">
              <div class="checkbox">
                <label>
                  <input type="checkbox" name="status" value="1" <?php if(!$is_edit || $e->status == 1) echo 'checked'; ?>>
                  <?php echo _l('hr_active'); ?>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Tab 2: Personal Info ── -->
      <div class="tab-pane" id="tab-personal">
        <div class="panel_s">
          <div class="panel-body">
            <div class="row">
              <div class="col-md-3">
                <!-- Photo -->
                <div class="form-group tw-text-center">
                  <div id="photo-preview" class="tw-mb-2">
                    <?php if ($is_edit && $e->photo): ?>
                    <img src="<?php echo base_url('uploads/hr_module/employees/' . $e->photo); ?>"
                      class="img-circle" width="96" height="96" style="object-fit:cover" id="preview-img">
                    <?php else: ?>
                    <div id="preview-img" class="tw-w-24 tw-h-24 tw-rounded-full tw-bg-gray-200 tw-flex tw-items-center tw-justify-center tw-mx-auto" style="width:96px;height:96px;border-radius:50%;background:#e2e8f0;display:inline-flex;align-items:center;justify-content:center;">
                      <i class="fa fa-user fa-3x" style="color:#94a3b8"></i>
                    </div>
                    <?php endif; ?>
                  </div>
                  <label class="btn btn-default btn-sm">
                    <i class="fa fa-camera"></i> <?php echo _l('hr_employee_photo'); ?>
                    <input type="file" name="photo" id="photo-input" accept="image/*" style="display:none">
                  </label>
                </div>
              </div>
              <div class="col-md-9">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><?php echo _l('hr_employee_first_name'); ?> <span class="text-danger">*</span></label>
                      <input type="text" name="first_name" class="form-control" required
                        value="<?php echo $is_edit ? ev($e,'first_name') : ''; ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><?php echo _l('hr_employee_last_name'); ?> <span class="text-danger">*</span></label>
                      <input type="text" name="last_name" class="form-control" required
                        value="<?php echo $is_edit ? ev($e,'last_name') : ''; ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><?php echo _l('hr_email'); ?></label>
                      <input type="email" name="email" class="form-control"
                        value="<?php echo $is_edit ? ev($e,'email') : ''; ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><?php echo _l('hr_phone'); ?></label>
                      <input type="text" name="phone" class="form-control"
                        value="<?php echo $is_edit ? ev($e,'phone') : ''; ?>">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_gender'); ?></label>
                  <select name="gender" class="form-control">
                    <option value=""><?php echo _l('hr_select'); ?></option>
                    <option value="male"   <?php if($is_edit && $e->gender=='male')   echo 'selected'; ?>><?php echo _l('hr_male'); ?></option>
                    <option value="female" <?php if($is_edit && $e->gender=='female') echo 'selected'; ?>><?php echo _l('hr_female'); ?></option>
                    <option value="other"  <?php if($is_edit && $e->gender=='other')  echo 'selected'; ?>><?php echo _l('hr_other'); ?></option>
                  </select>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_dob'); ?></label>
                  <div class="input-group date">
                    <input type="text" name="date_of_birth" class="form-control datepicker" autocomplete="off"
                      value="<?php echo $is_edit ? _d(ev($e,'date_of_birth')) : ''; ?>">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_blood_group'); ?></label>
                  <select name="blood_group" class="form-control">
                    <option value=""><?php echo _l('hr_select'); ?></option>
                    <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                    <option value="<?php echo $bg; ?>" <?php if($is_edit && $e->blood_group==$bg) echo 'selected'; ?>><?php echo $bg; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_marital_status'); ?></label>
                  <select name="marital_status" class="form-control">
                    <option value=""><?php echo _l('hr_select'); ?></option>
                    <?php foreach (['Single','Married','Divorced','Widowed'] as $ms): ?>
                    <option value="<?php echo strtolower($ms); ?>" <?php if($is_edit && $e->marital_status==strtolower($ms)) echo 'selected'; ?>><?php echo $ms; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_religion'); ?></label>
                  <input type="text" name="religion" class="form-control"
                    value="<?php echo $is_edit ? ev($e,'religion') : ''; ?>">
                </div>
              </div>
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_nid'); ?></label>
                  <input type="text" name="nid_number" class="form-control"
                    value="<?php echo $is_edit ? ev($e,'nid_number') : ''; ?>">
                </div>
              </div>
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_passport'); ?></label>
                  <input type="text" name="passport_number" class="form-control"
                    value="<?php echo $is_edit ? ev($e,'passport_number') : ''; ?>">
                </div>
              </div>
            </div>

            <div class="form-group">
              <label><?php echo _l('hr_address'); ?></label>
              <textarea name="address" class="form-control" rows="2"><?php echo $is_edit ? ev($e,'address') : ''; ?></textarea>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_emergency_contact'); ?> (Name)</label>
                  <input type="text" name="emergency_contact_name" class="form-control"
                    value="<?php echo $is_edit ? ev($e,'emergency_contact_name') : ''; ?>">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_emergency_contact'); ?> (Phone)</label>
                  <input type="text" name="emergency_contact_phone" class="form-control"
                    value="<?php echo $is_edit ? ev($e,'emergency_contact_phone') : ''; ?>">
                </div>
              </div>
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
                  <input type="text" name="bank_name" class="form-control"
                    value="<?php echo $is_edit ? ev($e,'bank_name') : ''; ?>">
                </div>
              </div>
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_bank_account'); ?></label>
                  <input type="text" name="bank_account" class="form-control"
                    value="<?php echo $is_edit ? ev($e,'bank_account') : ''; ?>">
                </div>
              </div>
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_bank_branch'); ?></label>
                  <input type="text" name="bank_branch" class="form-control"
                    value="<?php echo $is_edit ? ev($e,'bank_branch') : ''; ?>">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4 col-sm-6">
                <div class="form-group">
                  <label><?php echo _l('hr_employee_tin'); ?></label>
                  <input type="text" name="tin_number" class="form-control"
                    value="<?php echo $is_edit ? ev($e,'tin_number') : ''; ?>">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /tab-content -->

    <div class="row">
      <div class="col-md-12">
        <a href="<?php echo admin_url('hr_module/employees'); ?>" class="btn btn-default">
          <i class="fa fa-arrow-left tw-mr-1"></i><?php echo _l('hr_back'); ?>
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-save tw-mr-1"></i><?php echo _l('hr_save'); ?>
        </button>
      </div>
    </div>

    <?php echo form_close(); ?>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    // Photo preview
    $('#photo-input').on('change', function(){
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(e){
            $('#photo-preview').html('<img src="' + e.target.result + '" class="img-circle" width="96" height="96" style="object-fit:cover" id="preview-img">');
        };
        reader.readAsDataURL(file);
    });

    // Filter designations by department
    $('#emp_dept').on('change', function(){
        var dept_id = $(this).val();
        $.getJSON('<?php echo admin_url('hr_module/employees/get_designations_by_dept'); ?>',
            { dept_id: dept_id }, function(data){
            var $sel = $('#emp_designation').empty().append('<option value=""><?php echo _l('hr_select'); ?></option>');
            $.each(data, function(i, d){ $sel.append('<option value="' + d.id + '">' + d.name + '</option>'); });
        });
    });

    // Show tab with error
    $('input[required], select[required]').closest('.tab-pane').each(function(){
        var id = $(this).attr('id');
        $('a[href="#' + id + '"]').tab('show');
    });
});
</script>
