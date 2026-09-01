<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');

$f = [];
foreach (['department_id', 'status'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}

$rows = $CI->Reports_model->salary_summary_by_dept($f);

// DataTables (serverSide:true) expects only the requested page's rows back.
$start      = (int) $CI->input->post('start');
$length     = (int) $CI->input->post('length');
$paged_rows = $length > 0 ? array_slice($rows, $start, $length) : $rows;

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

foreach ($paged_rows as $d) {
    $output['aaData'][] = [
        '<strong>' . htmlspecialchars($d->department_name ?? 'Unassigned') . '</strong>',
        '<span class="text-right" style="display:block">' . $d->emp_count . '</span>',
        '<span class="text-right" style="display:block">' . number_format($d->avg_salary, 2) . '</span>',
        '<span class="text-right" style="display:block">' . number_format($d->min_salary, 2) . '</span>',
        '<span class="text-right" style="display:block">' . number_format($d->max_salary, 2) . '</span>',
    ];
}
