<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// Variables injected by CI controller via $data array
/** @var bool  $success */
/** @var array $log */
if (!isset($success)) $success = false;
if (!isset($log))     $log     = [];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="tw-font-semibold">
              <?php if (!empty($success)): ?>
              <i class="fa fa-check-circle text-success tw-mr-2"></i>Seeding Complete
              <?php else: ?>
              <i class="fa fa-exclamation-triangle text-warning tw-mr-2"></i>Seeder Result
              <?php endif; ?>
            </h4>
          </div>
          <div class="panel-body">
            <div class="<?php echo !empty($success) ? 'alert alert-success' : 'alert alert-warning'; ?> tw-mb-4">
              <?php if (!empty($success)): ?>
                <strong>All demo data has been seeded successfully!</strong>
              <?php else: ?>
                <strong>Seeder completed with issues.</strong>
                <?php if (!empty($log[0])): echo ' ' . htmlspecialchars($log[0]); endif; ?>
              <?php endif; ?>
            </div>

            <h5 class="tw-font-semibold tw-mb-2">Seed log:</h5>
            <div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;padding:14px 18px;font-family:monospace;font-size:13px;line-height:1.8;">
              <?php foreach ($log as $line): ?>
              <div style="<?php echo (strpos($line, '[ERROR]') !== false) ? 'color:#c0392b;font-weight:bold' : ''; ?>">
                <?php echo htmlspecialchars($line); ?>
              </div>
              <?php endforeach; ?>
            </div>

            <?php if (!empty($success)): ?>

            <div class="tw-mt-4">
              <h5 class="tw-font-semibold">Quick links to test:</h5>
              <div class="tw-flex tw-flex-wrap tw-gap-2 tw-mt-2">
                <a href="<?php echo admin_url('hr_module/employees'); ?>"   class="btn btn-default btn-sm"><i class="fa fa-users tw-mr-1"></i>Employees</a>
                <a href="<?php echo admin_url('hr_module/leave'); ?>"       class="btn btn-default btn-sm"><i class="fa fa-calendar tw-mr-1"></i>Leave</a>
                <a href="<?php echo admin_url('hr_module/attendance'); ?>"  class="btn btn-default btn-sm"><i class="fa fa-clock tw-mr-1"></i>Attendance</a>
                <a href="<?php echo admin_url('hr_module/payroll'); ?>"     class="btn btn-default btn-sm"><i class="fa fa-money-bill tw-mr-1"></i>Payroll</a>
                <a href="<?php echo admin_url('hr_module/loans'); ?>"       class="btn btn-default btn-sm"><i class="fa fa-hand-holding-usd tw-mr-1"></i>Loans</a>
                <a href="<?php echo admin_url('hr_module/overtime'); ?>"    class="btn btn-default btn-sm"><i class="fa fa-stopwatch tw-mr-1"></i>Overtime</a>
                <a href="<?php echo admin_url('hr_module/performance'); ?>" class="btn btn-default btn-sm"><i class="fa fa-star tw-mr-1"></i>Performance</a>
                <a href="<?php echo admin_url('hr_module/training'); ?>"    class="btn btn-default btn-sm"><i class="fa fa-graduation-cap tw-mr-1"></i>Training</a>
                <a href="<?php echo admin_url('hr_module/helpdesk'); ?>"    class="btn btn-default btn-sm"><i class="fa fa-ticket-alt tw-mr-1"></i>Helpdesk</a>
                <a href="<?php echo admin_url('hr_module/hr_contracts'); ?>" class="btn btn-default btn-sm"><i class="fa fa-file-contract tw-mr-1"></i>Contracts</a>
              </div>
            </div>

            <div class="tw-mt-4 alert alert-info">
              <strong>Staff login credentials</strong><br>
              Email: <code>alice.johnson@demo.hr</code> / <code>bob.smith@demo.hr</code> / <code>carol.williams@demo.hr</code> / <code>david.brown@demo.hr</code> / <code>eva.davis@demo.hr</code><br>
              Password: <strong>Demo@1234</strong>
            </div>

            <a href="<?php echo admin_url('hr_module/demo_data/reset'); ?>"
               class="btn btn-danger btn-sm tw-mt-2"
               onclick="return confirm('This will permanently delete all DEMO- data. Continue?')">
              <i class="fa fa-trash tw-mr-1"></i>Reset Demo Data
            </a>
            <?php endif; ?>

            <a href="<?php echo admin_url('hr_module/demo_data/status'); ?>" class="btn btn-info tw-mt-2 tw-ml-2">
              <i class="fa fa-database tw-mr-1"></i>Check DB Status
            </a>
            <a href="<?php echo admin_url('hr_module'); ?>" class="btn btn-primary tw-mt-2 tw-ml-2">
              <i class="fa fa-home tw-mr-1"></i>Go to HR Dashboard
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
