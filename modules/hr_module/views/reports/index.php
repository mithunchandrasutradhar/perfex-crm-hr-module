<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <h4 class="tw-font-semibold tw-text-lg tw-mb-4"><?php echo _l('hr_reports'); ?></h4>
        <?php
        $reports = [
          ['url'=>'attendance',  'icon'=>'fa-calendar-check',   'color'=>'#4f46e5', 'title'=>'Attendance Report',    'desc'=>'Daily attendance by employee, dept, status'],
          ['url'=>'leave',       'icon'=>'fa-plane',            'color'=>'#0891b2', 'title'=>'Leave Report',         'desc'=>'Leave taken by type, employee, department'],
          ['url'=>'payroll',     'icon'=>'fa-money-bill-wave',  'color'=>'#059669', 'title'=>'Payroll Report',       'desc'=>'Monthly salary, deductions, net pay totals'],
          ['url'=>'loan',        'icon'=>'fa-credit-card',      'color'=>'#d97706', 'title'=>'Loan Report',          'desc'=>'Active loans, outstanding amounts, repayment'],
          ['url'=>'overtime',    'icon'=>'fa-clock',            'color'=>'#7c3aed', 'title'=>'Overduty Report',      'desc'=>'Overduty hours and amounts by employee'],
          ['url'=>'performance', 'icon'=>'fa-tasks',            'color'=>'#db2777', 'title'=>'Performance Report',   'desc'=>'Assigned targets, evaluators, completion status'],
          ['url'=>'training',    'icon'=>'fa-graduation-cap',   'color'=>'#0284c7', 'title'=>'Training Report',      'desc'=>'Programs, enrollment, completion rates'],
          ['url'=>'headcount',   'icon'=>'fa-users',            'color'=>'#065f46', 'title'=>'Headcount Report',     'desc'=>'Employee count by dept, type, gender'],
          ['url'=>'department',  'icon'=>'fa-building',         'color'=>'#92400e', 'title'=>'Department Report',    'desc'=>'Per-employee leave, payroll by department'],
          ['url'=>'salary',      'icon'=>'fa-bar-chart',        'color'=>'#1e40af', 'title'=>'Salary Report',        'desc'=>'Salary ranges, averages by department'],
          ['url'=>'turnover',    'icon'=>'fa-exchange',         'color'=>'#9f1239', 'title'=>'Turnover Report',      'desc'=>'Monthly joining / leaving, turnover rate'],
        ];
        ?>
        <div class="row" style="display:flex;flex-wrap:wrap;align-items:stretch">
          <?php foreach ($reports as $r): ?>
          <div class="col-xs-12 col-sm-6 col-md-3" style="display:flex">
            <a href="<?php echo admin_url('hr_module/reports/'.$r['url']); ?>" style="text-decoration:none;display:flex;width:100%">
              <div class="panel_s" style="border-top:3px solid <?php echo $r['color']; ?>;transition:box-shadow .15s;width:100%;display:flex;flex-direction:column">
                <div class="panel-body" style="flex:1">
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <div style="width:44px;height:44px;flex-shrink:0;border-radius:10px;background:<?php echo $r['color'].'22'; ?>;display:flex;align-items:center;justify-content:center">
                      <i class="fa <?php echo $r['icon']; ?>" style="font-size:1.2rem;color:<?php echo $r['color']; ?>"></i>
                    </div>
                    <div style="min-width:0">
                      <div style="font-weight:600;color:#1e293b;font-size:0.9rem"><?php echo $r['title']; ?></div>
                      <div style="font-size:0.75rem;color:#94a3b8;margin-top:2px"><?php echo $r['desc']; ?></div>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
