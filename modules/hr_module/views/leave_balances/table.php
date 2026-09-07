<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Leave_model');

$year          = $CI->input->get('year') ?: date('Y');
$dept_id       = $CI->input->get('dept_id');
$leave_type_id = $CI->input->get('leave_type_id');

// The DataTable's own search box - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so its
// search[value] POST field has to be picked up and applied by hand.
$search_value = $CI->input->post('search');
$search       = !empty($search_value['value']) ? trim($search_value['value']) : null;

$rows = $CI->Leave_model->get_all_balances($year, $dept_id, $search, $leave_type_id);

// The DataTable's own pagination - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so start/length
// have to be applied by hand after the filtered set is fetched.
$total_filtered = count($rows);
$dt_start  = (int) $CI->input->post('start');
$dt_length = (int) $CI->input->post('length');
if ($dt_length > 0) $rows = array_slice($rows, $dt_start, $dt_length);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => $total_filtered,
    'iTotalDisplayRecords' => $total_filtered,
    'aaData'               => [],
];

foreach ($rows as $b) {
    $total     = $b->allocated_days + $b->carry_forward_days;
    $remaining = $total - $b->used_days;
    $pct       = $total > 0 ? min(100, round($b->used_days / $total * 100)) : 0;
    $color     = $pct >= 90 ? 'danger' : ($pct >= 60 ? 'warning' : 'success');

    $allocated_cell = hr_format_day_duration($total, $b->hours_per_day);
    if ($b->carry_forward_days > 0) {
        $allocated_cell .= ' <small class="text-muted">(+' . hr_format_day_duration($b->carry_forward_days, $b->hours_per_day) . ' CF)</small>';
    }

    $remaining_cell = '<strong class="text-' . $color . '">' . hr_format_day_duration($remaining, $b->hours_per_day) . '</strong>'
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
        hr_format_day_duration($b->used_days, $b->hours_per_day),
        $remaining_cell,
    ];
    $output['aaData'][] = $row;
}
