<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');

$f = [];
foreach (['year', 'department_id'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}
if (empty($f['year'])) $f['year'] = date('Y');

$rows = $CI->Reports_model->turnover($f);

$total_joined = array_sum(array_column((array) $rows, 'joined'));
$total_left   = array_sum(array_column((array) $rows, 'left_count'));
$avg_rate     = count($rows) ? array_sum(array_column((array) $rows, 'turnover_rate')) / count($rows) : 0;

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
    'sums'                 => [
        'total_joined' => $total_joined,
        'total_left'   => $total_left,
        'net'          => ($total_joined - $total_left >= 0 ? '+' : '') . ($total_joined - $total_left),
        'avg_rate'     => number_format($avg_rate, 1),
    ],
    // Same series the Chart.js bar chart on the page plots (Hired / Left per
    // month) - the draw.dt handler in turnover.php feeds this back into the
    // existing Chart.js instance so filter changes update the chart too,
    // without a full page reload or recreating the chart.
    'chart' => [
        'labels' => array_map(fn($r) => date('M', mktime(0, 0, 0, $r->month, 1)), (array) $rows),
        'joined' => array_column((array) $rows, 'joined'),
        'left'   => array_column((array) $rows, 'left_count'),
    ],
];

foreach ($rows as $r) {
    $net = $r->joined - $r->left_count;
    $rate = $r->turnover_rate;
    $rate_class = $rate > 5 ? 'text-danger' : ($rate > 2 ? 'text-warning' : 'text-success');

    $output['aaData'][] = [
        date('F Y', mktime(0, 0, 0, $r->month, 1, $r->year)),
        '<span class="text-right text-success" style="display:block"><strong>' . $r->joined . '</strong></span>',
        '<span class="text-right text-danger" style="display:block"><strong>' . $r->left_count . '</strong></span>',
        '<span class="text-right ' . ($net >= 0 ? 'text-success' : 'text-danger') . '" style="display:block">' . ($net >= 0 ? '+' : '') . $net . '</span>',
        '<span class="text-right" style="display:block">' . ($r->headcount_end ?? '-') . '</span>',
        '<span class="text-right" style="display:block"><span class="' . $rate_class . '">' . number_format($rate, 1) . '%</span></span>',
    ];
}
