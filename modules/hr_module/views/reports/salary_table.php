<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');

$f = [];
foreach (['department_id', 'status'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}

$rows = $CI->Reports_model->salary($f);

$total_basic       = array_sum(array_column((array) $rows, 'basic_salary'));
$total_allowances  = array_sum(array_column((array) $rows, 'total_allowances'));
$total_deductions  = array_sum(array_column((array) $rows, 'total_deductions'));
$total_gross       = array_sum(array_column((array) $rows, 'gross_salary'));
$avg_gross         = count($rows) ? $total_gross / count($rows) : 0;

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
        'total_basic'      => number_format($total_basic, 2),
        'total_allowances' => number_format($total_allowances, 2),
        'total_deductions' => number_format($total_deductions, 2),
        'total_gross'      => number_format($total_gross, 2),
        'avg_gross'        => number_format($avg_gross, 2),
    ],
];

foreach ($paged_rows as $r) {
    $output['aaData'][] = [
        htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . htmlspecialchars($r->employee_code) . '</small>',
        htmlspecialchars($r->department_name ?? '-'),
        htmlspecialchars($r->designation_name ?? '-'),
        '<span class="text-right" style="display:block">' . number_format($r->basic_salary, 2) . '</span>',
        '<span class="text-right text-success" style="display:block">' . number_format($r->total_allowances ?? 0, 2) . '</span>',
        '<span class="text-right text-danger" style="display:block">' . number_format($r->total_deductions ?? 0, 2) . '</span>',
        '<span class="text-right" style="display:block"><strong>' . number_format($r->gross_salary, 2) . '</strong></span>',
    ];
}
