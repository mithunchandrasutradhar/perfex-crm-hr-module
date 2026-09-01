<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');

$f = [];
foreach (['department_id', 'status', 'month', 'year'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}
if (empty($f['year'])) $f['year'] = date('Y');

$rows = $CI->Reports_model->payroll($f);

$sbadge = ['draft' => 'default', 'approved' => 'info', 'paid' => 'success'];
$months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

$totals = ['gross' => 0, 'deductions' => 0, 'net' => 0];
foreach ($rows as $r) {
    $totals['gross']      += $r->gross_earnings ?? 0;
    $totals['deductions'] += $r->total_deductions ?? 0;
    $totals['net']        += $r->net_salary ?? 0;
}

// DataTables (serverSide:true) expects only the requested page's rows back -
// sums/totals above are still computed over the FULL $rows set, only aaData
// is limited to the current page.
$start      = (int) $CI->input->post('start');
$length     = (int) $CI->input->post('length');
$paged_rows = $length > 0 ? array_slice($rows, $start, $length) : $rows;

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
    'sums'                 => [
        'records'    => count($rows),
        'gross'      => number_format($totals['gross'], 2),
        'deductions' => number_format($totals['deductions'], 2),
        'net'        => number_format($totals['net'], 2),
    ],
];

foreach ($paged_rows as $r) {
    $output['aaData'][] = [
        htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . htmlspecialchars($r->employee_code) . '</small>',
        htmlspecialchars($r->department_name ?? '-'),
        $months[$r->pay_month] . ' ' . $r->pay_year,
        '<span style="display:block" class="text-right">' . number_format($r->basic_salary, 2) . '</span>',
        '<span style="display:block" class="text-right">' . number_format($r->gross_earnings, 2) . '</span>',
        '<span style="display:block" class="text-right text-danger">' . number_format($r->total_deductions, 2) . '</span>',
        '<span style="display:block" class="text-right"><strong>' . number_format($r->net_salary, 2) . '</strong></span>',
        '<span class="label label-' . ($sbadge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>',
    ];
}
