<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');

$f = [];
foreach (['department_id'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}

$rows = $CI->Reports_model->headcount($f);

$total    = 0;
$active   = 0;
$inactive = 0;
$male     = 0;
$female   = 0;
foreach ($rows as $r) {
    $total    += $r->total;
    $active   += $r->active;
    $inactive += $r->inactive;
    $male     += $r->male;
    $female   += $r->female;
}

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
    'sums'                 => [
        'total'    => $total,
        'active'   => $active,
        'inactive' => $inactive,
        'male'     => $male,
        'female'   => $female,
    ],
];

foreach ($rows as $r) {
    $row = [
        '<strong>' . htmlspecialchars($r->department_name ?? 'Unassigned') . '</strong>',
        '<div class="text-right"><strong>' . $r->total . '</strong></div>',
        '<div class="text-right text-success">' . $r->active . '</div>',
        '<div class="text-right text-muted">' . $r->inactive . '</div>',
        '<div class="text-right">' . $r->male . '</div>',
        '<div class="text-right">' . $r->female . '</div>',
    ];
    $output['aaData'][] = $row;
}
