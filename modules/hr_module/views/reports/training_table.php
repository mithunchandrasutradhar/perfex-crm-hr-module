<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');

$f = [];
foreach (['status', 'year'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}

$rows = $CI->Reports_model->training($f);

$sbadge = ['scheduled' => 'info', 'ongoing' => 'primary', 'completed' => 'success', 'cancelled' => 'danger'];

$total_enrolled  = array_sum(array_column((array) $rows, 'enrolled'));
$total_completed = array_sum(array_column((array) $rows, 'present'));
$completion_rate = $total_enrolled > 0 ? round($total_completed / $total_enrolled * 100) : 0;

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
    'sums'                 => [
        'programs'  => count($rows),
        'enrolled'  => $total_enrolled,
        'completed' => $total_completed,
        'rate'      => $completion_rate,
    ],
];

foreach ($rows as $r) {
    $rate = $r->enrolled > 0 ? round($r->present / $r->enrolled * 100) : 0;

    $rate_cell = '<div style="background:#e2e8f0;border-radius:4px;height:8px;width:80px">'
        . '<div style="background:#059669;border-radius:4px;height:8px;width:' . $rate . '%"></div>'
        . '</div><small>' . $rate . '%</small>';

    $row = [
        '<a href="' . admin_url('hr_module/training/view/' . $r->id) . '">' . htmlspecialchars($r->title) . '</a>',
        htmlspecialchars(($r->instructor_name ?: $r->trainer) ?? '-'),
        date('d M Y', strtotime($r->start_date)),
        $r->end_date ? date('d M Y', strtotime($r->end_date)) : '-',
        '<div class="text-right">' . ($r->capacity ?: '&infin;') . '</div>',
        '<div class="text-right">' . $r->enrolled . '</div>',
        '<div class="text-right text-success">' . $r->present . '</div>',
        $rate_cell,
        '<span class="label label-' . ($sbadge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>',
    ];
    $output['aaData'][] = $row;
}
