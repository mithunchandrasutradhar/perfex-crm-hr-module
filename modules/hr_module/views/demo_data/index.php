<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-9 col-md-offset-1">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="tw-font-semibold"><i class="fa fa-database tw-mr-2 text-info"></i>HR Demo Data Seeder</h4>
          </div>
          <div class="panel-body">

            <div class="alert alert-info">
              <strong>What will be created:</strong>
              <table class="table table-condensed tw-mt-2 tw-mb-0" style="background:transparent">
                <thead><tr><th>Employee</th><th>Code</th><th>Role</th><th>Account</th></tr></thead>
                <tbody>
                  <tr class="success"><td><strong>Talha Ahmed</strong></td><td>ALPHA-EMP-001</td><td>HR Manager</td><td><code>talha@alpha.net.bd</code> (existing staff linked)</td></tr>
                  <tr><td>Alice Johnson</td><td>DEMO-EMP-001</td><td>Software Engineer</td><td><code>alice.johnson@demo.hr</code> / Demo@1234</td></tr>
                  <tr><td>Bob Smith</td><td>DEMO-EMP-002</td><td>Senior Accountant</td><td><code>bob.smith@demo.hr</code> / Demo@1234</td></tr>
                  <tr><td>Carol Williams</td><td>DEMO-EMP-003</td><td>UI/UX Designer</td><td><code>carol.williams@demo.hr</code> / Demo@1234</td></tr>
                  <tr><td>David Brown</td><td>DEMO-EMP-004</td><td>Sales Executive</td><td><code>david.brown@demo.hr</code> / Demo@1234</td></tr>
                </tbody>
              </table>
              <strong class="tw-mt-3 tw-block">Data seeded for all 5 employees:</strong>
              <div class="row tw-mt-2">
                <div class="col-md-6">
                  <ul class="tw-mb-0">
                    <li>Departments &amp; designations</li>
                    <li>Leave types + balances (6 types)</li>
                    <li>Leave requests (7 per employee, mixed statuses)</li>
                    <li>Attendance — last 35 working days</li>
                    <li>Payroll items (8 items)</li>
                    <li>Payroll slips — last 4 months (with breakdowns)</li>
                  </ul>
                </div>
                <div class="col-md-6">
                  <ul class="tw-mb-0">
                    <li>Loans with repayment history</li>
                    <li>Overduty entries (6 per employee)</li>
                    <li>Performance reviews (2 per employee)</li>
                    <li>4 training sessions, all enrolled</li>
                    <li>Helpdesk tickets (3 per employee, with replies)</li>
                    <li>Employment contracts</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="alert alert-warning">
              <i class="fa fa-info-circle tw-mr-1"></i>
              This creates real database records. Run on a fresh/test environment only.
              The <strong>talha@alpha.net.bd</strong> staff account will be linked but NOT deleted on Reset.
            </div>

            <?php
            $CI = get_instance();
            $demo_emp  = $CI->db->like('employee_code', 'DEMO-', 'after')->count_all_results(db_prefix() . 'hr_employees');
            $talha_emp = $CI->db->where('employee_code', 'ALPHA-EMP-001')->count_all_results(db_prefix() . 'hr_employees');
            if ($demo_emp > 0 || $talha_emp > 0): ?>
            <div class="alert alert-danger">
              <strong>Demo data already exists</strong> —
              <?php
                $parts = [];
                if ($demo_emp > 0) $parts[] = $demo_emp . ' DEMO- employee(s)';
                if ($talha_emp > 0) $parts[] = 'Talha (ALPHA-EMP-001)';
                echo implode(', ', $parts) . ' found.';
              ?>
              Use Reset to wipe and re-seed.
            </div>
            <a href="<?php echo admin_url('hr_module/demo_data/reset'); ?>"
               class="btn btn-danger"
               onclick="return confirm('This will delete all demo employee records and HR data. Talha staff account will be preserved. Continue?')">
              <i class="fa fa-trash tw-mr-1"></i>Reset Demo Data
            </a>
            <?php else: ?>
            <a href="<?php echo admin_url('hr_module/demo_data/run'); ?>"
               class="btn btn-success btn-lg"
               onclick="return confirm('Seed demo HR data for 5 employees now?')">
              <i class="fa fa-play-circle tw-mr-1"></i>Seed Demo Data
            </a>
            <?php endif; ?>

            <a href="<?php echo admin_url('hr_module/demo_data/status'); ?>" class="btn btn-info tw-ml-2">
              <i class="fa fa-database tw-mr-1"></i>Check DB Status
            </a>
            <a href="<?php echo admin_url('hr_module'); ?>" class="btn btn-default tw-ml-2">
              <i class="fa fa-arrow-left tw-mr-1"></i>Back to HR
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
