<?php defined('BASEPATH') or exit('No direct script access allowed');
$allowances = array_filter((array) $details, fn($d) => $d->item_type === 'allowance');
$deductions  = array_filter((array) $details, fn($d) => $d->item_type === 'deduction');
$company_name = get_option('companyname') ?: 'Company Name';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Pay Slip — <?php echo date('F Y',mktime(0,0,0,$payroll->pay_month,1,$payroll->pay_year)); ?></title>
<style>
  body{font-family:Arial,sans-serif;font-size:13px;color:#222;margin:0;padding:20px}
  .slip{max-width:720px;margin:0 auto;border:1px solid #ccc;padding:24px}
  .header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #333;padding-bottom:12px;margin-bottom:16px}
  .company h2{margin:0;font-size:18px} .company p{margin:2px 0;font-size:11px;color:#555}
  .slip-title{text-align:right} .slip-title h3{margin:0;font-size:15px} .slip-title p{margin:2px 0;font-size:11px}
  .emp-info{background:#f8f8f8;border:1px solid #e2e2e2;padding:10px 14px;margin-bottom:14px;border-radius:4px}
  .emp-info table{width:100%;border-collapse:collapse} .emp-info td{padding:2px 8px;font-size:12px}
  .emp-info td:nth-child(even){font-weight:600}
  .section-title{background:#333;color:#fff;padding:4px 10px;font-size:12px;font-weight:bold;margin:12px 0 4px}
  .pay-table{width:100%;border-collapse:collapse} .pay-table td,.pay-table th{padding:5px 10px;border:1px solid #ddd;font-size:12px}
  .pay-table th{background:#f0f0f0;font-weight:600} .pay-table .total{font-weight:700;background:#f8f8f8}
  .net-box{background:#1e3a5f;color:#fff;padding:10px 16px;border-radius:4px;display:flex;justify-content:space-between;align-items:center;margin-top:14px}
  .net-box .label{font-size:14px;font-weight:600} .net-box .amount{font-size:20px;font-weight:700}
  .footer{margin-top:20px;border-top:1px solid #ccc;padding-top:10px;display:flex;justify-content:space-between;font-size:11px;color:#888}
  @media print{body{padding:0} .no-print{display:none}}
</style>
</head>
<body>
<div class="no-print" style="text-align:center;margin-bottom:16px">
  <button onclick="window.print()" style="padding:8px 20px;font-size:14px;cursor:pointer">🖨 Print / Save PDF</button>
</div>
<div class="slip">
  <div class="header">
    <div class="company">
      <h2><?php echo htmlspecialchars($company_name); ?></h2>
      <p><?php echo get_option('company_city'); ?> <?php echo get_option('company_country'); ?></p>
    </div>
    <div class="slip-title">
      <h3>PAY SLIP</h3>
      <p><?php echo date('F Y',mktime(0,0,0,$payroll->pay_month,1,$payroll->pay_year)); ?></p>
      <p>Ref: #<?php echo str_pad($payroll->id,6,'0',STR_PAD_LEFT); ?></p>
    </div>
  </div>

  <div class="emp-info">
    <table>
      <tr>
        <td>Employee Name:</td><td><?php echo htmlspecialchars($payroll->first_name.' '.$payroll->last_name); ?></td>
        <td>Employee Code:</td><td><?php echo $payroll->employee_code; ?></td>
      </tr>
      <tr>
        <td>Department:</td><td><?php echo htmlspecialchars($payroll->department_name ?? '-'); ?></td>
        <td>Designation:</td><td><?php echo htmlspecialchars($payroll->designation_name ?? '-'); ?></td>
      </tr>
      <tr>
        <td>Joining Date:</td><td><?php echo $payroll->joining_date ? date('d M Y',strtotime($payroll->joining_date)) : '-'; ?></td>
        <td>Bank Account:</td><td><?php echo $payroll->bank_account_no ?: '-'; ?></td>
      </tr>
      <tr>
        <td>Pay Period:</td><td><?php echo date('F Y',mktime(0,0,0,$payroll->pay_month,1,$payroll->pay_year)); ?></td>
        <td>Working Days:</td><td><?php echo $payroll->present_days ?? '-'; ?> / <?php echo $payroll->working_days ?? '-'; ?></td>
      </tr>
    </table>
  </div>

  <div style="display:flex;gap:16px">
    <!-- Earnings -->
    <div style="flex:1">
      <div class="section-title">EARNINGS</div>
      <table class="pay-table">
        <tr><th>Item</th><th style="text-align:right">Amount</th></tr>
        <tr><td>Basic Salary</td><td style="text-align:right"><?php echo number_format($payroll->basic_salary,2); ?></td></tr>
        <?php foreach ($allowances as $d): ?>
        <tr><td><?php echo htmlspecialchars($d->item_name); ?></td><td style="text-align:right"><?php echo number_format($d->amount,2); ?></td></tr>
        <?php endforeach; ?>
        <?php if($payroll->overtime_amount > 0): ?>
        <tr><td>Overtime Pay</td><td style="text-align:right"><?php echo number_format($payroll->overtime_amount,2); ?></td></tr>
        <?php endif; ?>
        <?php if($payroll->bonus > 0): ?>
        <tr><td>Bonus</td><td style="text-align:right"><?php echo number_format($payroll->bonus,2); ?></td></tr>
        <?php endif; ?>
        <tr class="total"><td>Total Earnings</td><td style="text-align:right"><?php echo number_format($payroll->gross_salary,2); ?></td></tr>
      </table>
    </div>
    <!-- Deductions -->
    <div style="flex:1">
      <div class="section-title">DEDUCTIONS</div>
      <table class="pay-table">
        <tr><th>Item</th><th style="text-align:right">Amount</th></tr>
        <?php foreach ($deductions as $d): ?>
        <tr><td><?php echo htmlspecialchars($d->item_name); ?></td><td style="text-align:right"><?php echo number_format($d->amount,2); ?></td></tr>
        <?php endforeach; ?>
        <?php if($payroll->tax > 0): ?>
        <tr><td>Income Tax</td><td style="text-align:right"><?php echo number_format($payroll->tax,2); ?></td></tr>
        <?php endif; ?>
        <?php if($payroll->loan_deduction > 0): ?>
        <tr><td>Loan Deduction</td><td style="text-align:right"><?php echo number_format($payroll->loan_deduction,2); ?></td></tr>
        <?php endif; ?>
        <?php $total_ded = $payroll->total_deductions + $payroll->tax + $payroll->loan_deduction; ?>
        <tr class="total"><td>Total Deductions</td><td style="text-align:right"><?php echo number_format($total_ded,2); ?></td></tr>
      </table>
    </div>
  </div>

  <div class="net-box">
    <span class="label">NET SALARY PAYABLE</span>
    <span class="amount"><?php echo number_format($payroll->net_salary,2); ?></span>
  </div>

  <?php if($payroll->payment_date): ?>
  <p style="margin-top:10px;font-size:11px;color:#555">
    Paid via <?php echo ucfirst(str_replace('_',' ',$payroll->payment_method)); ?> on <?php echo date('d M Y',strtotime($payroll->payment_date)); ?>
  </p>
  <?php endif; ?>

  <div class="footer">
    <div>Generated: <?php echo date('d M Y H:i',strtotime($payroll->created_at)); ?></div>
    <div>This is a computer-generated payslip and does not require a signature.</div>
  </div>
</div>
</body>
</html>
