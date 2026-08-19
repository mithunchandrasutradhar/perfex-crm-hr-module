<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Leave_model');

$year    = $CI->input->get('year') ?: date('Y');
$dept_id = $CI->input->get('dept_id');

$rows = $CI->Leave_model->get_all_balances($year, $dept_id);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

foreach ($rows as $b) {
    $total     = $b->allocated_days + $b->carry_forward_days;
    $remaining = $total - $b->used_days;
    $pct       = $total > 0 ? min(100, round($b->used_days / $total * 100)) : 0;
    $color     = $pct >= 90 ? 'danger' : ($pct >= 60 ? 'warning' : 'success');

    $allocated_cell = $total;
    if ($b->carry_forward_days > 0) {
        $allocated_cell .= ' <small class="text-muted">(+' . $b->carry_forward_days . ' CF)</small>';
    }

    $remaining_cell = '<strong class="text-' . $color . '">' . $remaining . '</strong>'
        . '<div class="progress tw-my-0 progress-bar-mini">'
        . '<div class="progress-bar progress-bar-' . $color . ' no-percent-text not-dynamic" role="progressbar"'
        . ' aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100"'
        . ' style="width: ' . $pct . '%" data-percent="' . $pct . '"></div>'
        . '</div>';

    $employee_cell = htmlspecialchars($b->employee_name) . '<br><small class="text-muted">' . htmlspecialchars($b->employee_code) . '</small>';

    $row = [
        $employee_cell,
        htmlspecialchars($b->department_name ?? '-'),
        htmlspecialchars($b->leave_type_name),
        $allocated_cell,
        $b->used_days,
        $remaining_cell,
    ];
    $output['aaData'][] = $row;
}
