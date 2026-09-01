<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');

$f = [];
foreach (['department_id', 'status'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}

$rows = $CI->Reports_model->loans($f);

$sbadge = ['pending' => 'warning', 'approved' => 'info', 'active' => 'primary', 'closed' => 'success', 'rejected' => 'danger'];

// DataTables (serverSide:true) expects only the requested page's rows back -
// sums/totals below are still computed over the FULL $rows set, only aaData
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
        'total_loans'       => count($rows),
        'total_amount'      => number_format(array_sum(array_column((array) $rows, 'loan_amount')), 2),
        'total_outstanding' => number_format(array_sum(array_column((array) $rows, 'outstanding')), 2),
    ],
];

foreach ($paged_rows as $r) {
    $output['aaData'][] = [
        htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . htmlspecialchars($r->employee_code) . '</small>',
        htmlspecialchars($r->department_name ?? '-'),
        '<span class="text-right" style="display:block">' . number_format($r->loan_amount, 2) . '</span>',
        '<span class="text-right" style="display:block">' . number_format($r->monthly_installment, 2) . '</span>',
        '<span class="text-right text-danger" style="display:block">' . number_format($r->outstanding, 2) . '</span>',
        '<span class="text-right text-success" style="display:block">' . number_format($r->total_repaid, 2) . '</span>',
        '<span class="label label-' . ($sbadge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>',
        $r->approved_at ? _d($r->approved_at) : '-',
    ];
}
