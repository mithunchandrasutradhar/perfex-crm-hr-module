<?php defined('BASEPATH') or exit('No direct script access allowed');
$e = $employee;
function ef($v, $d = '-') { return !empty($v) ? htmlspecialchars($v) : $d; }
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/employees'); ?>"><?php echo _l('hr_menu_employees'); ?></a></li>
          <li class="active"><?php echo ef($e->first_name) . ' ' . ef($e->last_name); ?></li>
        </ol>
      </div>
    </div>

    <div class="row">
      <!-- Left: Profile Card -->
      <div class="col-md-3">
        <div class="panel_s">
          <div class="panel-body tw-text-center tw-py-6">
            <?php if ($e->photo): ?>
            <img src="<?php echo base_url('uploads/hr_module/employees/' . $e->photo); ?>"
              class="img-circle tw-mb-3" width="100" height="100" style="object-fit:cover">
            <?php else: ?>
            <div class="tw-mx-auto tw-mb-3" style="width:100px;height:100px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;">
              <span style="font-size:2.5rem;color:#94a3b8;font-weight:700;"><?php echo strtoupper(substr($e->first_name,0,1)); ?></span>
            </div>
            <?php endif; ?>
            <h5 class="tw-font-bold tw-text-base"><?php echo ef($e->first_name) . ' ' . ef($e->last_name); ?></h5>
            <p class="text-muted tw-text-sm"><?php echo ef($e->designation_name); ?></p>
            <p><span class="label label-default"><?php echo ef($e->employee_code); ?></span></p>
            <?php if ($e->status == 1): ?>
            <span class="label label-success"><?php echo _l('hr_active'); ?></span>
            <?php else: ?>
            <span class="label label-danger"><?php echo _l('hr_inactive'); ?></span>
            <?php endif; ?>
            <hr>
            <?php if (staff_can('edit', 'hr_employees')): ?>
            <a href="<?php echo admin_url('hr_module/employees/edit/' . $e->id); ?>" class="btn btn-default btn-block btn-sm">
              <i class="fa fa-pencil-alt tw-mr-1"></i><?php echo _l('hr_edit'); ?>
            </a>
            <?php endif; ?>
            <?php if (staff_can('delete', 'hr_employees')): ?>
            <a href="<?php echo admin_url('hr_module/employees/delete/' . $e->id); ?>"
              class="btn btn-danger btn-block btn-sm _delete tw-mt-2">
              <i class="fa fa-times tw-mr-1"></i><?php echo _l('hr_delete'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Right: Details -->
      <div class="col-md-9">
        <div class="panel_s">
          <div class="panel-body">
            <ul class="nav nav-tabs tw-mb-4">
              <li class="active"><a href="#pv-work" data-toggle="tab"><i class="fa fa-briefcase tw-mr-1"></i><?php echo _l('hr_employee_work_info'); ?></a></li>
              <li><a href="#pv-personal" data-toggle="tab"><i class="fa fa-user tw-mr-1"></i><?php echo _l('hr_employee_personal_info'); ?></a></li>
              <li><a href="#pv-bank" data-toggle="tab"><i class="fa fa-university tw-mr-1"></i><?php echo _l('hr_employee_bank_info'); ?></a></li>
            </ul>
            <div class="tab-content">

              <!-- Work Info -->
              <div class="tab-pane active" id="pv-work">
                <table class="table table-condensed">
                  <tr><th style="width:35%"><?php echo _l('hr_employee_code'); ?></th><td><?php echo ef($e->employee_code); ?></td></tr>
                  <tr><th><?php echo _l('hr_department'); ?></th><td><?php echo ef($e->department_name); ?></td></tr>
                  <tr><th><?php echo _l('hr_designation'); ?></th><td><?php echo ef($e->designation_name); ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_joining_date'); ?></th><td><?php echo $e->joining_date ? _d($e->joining_date) : '-'; ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_end_date'); ?></th><td><?php echo $e->end_date ? _d($e->end_date) : '-'; ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_basic_salary'); ?></th><td><?php echo number_format($e->basic_salary, 2); ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_staff_linked'); ?></th><td><?php echo ef($e->staff_name); ?></td></tr>
                  <tr><th><?php echo _l('hr_notes'); ?></th><td><?php echo ef($e->notes); ?></td></tr>
                </table>
              </div>

              <!-- Personal Info -->
              <div class="tab-pane" id="pv-personal">
                <table class="table table-condensed">
                  <tr><th style="width:35%"><?php echo _l('hr_email'); ?></th><td><?php echo $e->email ? '<a href="mailto:' . $e->email . '">' . ef($e->email) . '</a>' : '-'; ?></td></tr>
                  <tr><th><?php echo _l('hr_phone'); ?></th><td><?php echo ef($e->phone); ?></td></tr>
                  <tr><th><?php echo _l('hr_gender'); ?></th><td><?php echo ef($e->gender); ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_dob'); ?></th><td><?php echo $e->date_of_birth ? _d($e->date_of_birth) : '-'; ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_blood_group'); ?></th><td><?php echo ef($e->blood_group); ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_marital_status'); ?></th><td><?php echo ef($e->marital_status); ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_religion'); ?></th><td><?php echo ef($e->religion); ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_nid'); ?></th><td><?php echo ef($e->nid_number); ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_passport'); ?></th><td><?php echo ef($e->passport_number); ?></td></tr>
                  <tr><th><?php echo _l('hr_address'); ?></th><td><?php echo ef($e->address); ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_emergency_contact'); ?></th>
                    <td><?php echo ef($e->emergency_contact_name); ?><?php if($e->emergency_contact_phone) echo ' &mdash; ' . ef($e->emergency_contact_phone); ?></td></tr>
                </table>
              </div>

              <!-- Bank Info -->
              <div class="tab-pane" id="pv-bank">
                <table class="table table-condensed">
                  <tr><th style="width:35%"><?php echo _l('hr_employee_bank_name'); ?></th><td><?php echo ef($e->bank_name); ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_bank_account'); ?></th><td><?php echo ef($e->bank_account); ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_bank_branch'); ?></th><td><?php echo ef($e->bank_branch); ?></td></tr>
                  <tr><th><?php echo _l('hr_employee_tin'); ?></th><td><?php echo ef($e->tin_number); ?></td></tr>
                </table>
              </div>

            </div><!-- /tab-content -->
          </div>
        </div>
      </div>
    </div><!-- /row -->
  </div>
</div>
<?php init_tail(); ?>
