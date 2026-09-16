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

// Batched fetch (one query for the whole filtered set, not one per row) of
// every approved leave day behind these balances' used_days - moved ahead of
// sorting/pagination below so "Used"/"Remaining" can be sorted by the same
// exact-minutes figure that's actually displayed (see get_used_minutes_exact()'s
// comment for why the exact minutes are used instead of the accumulated, and
// possibly drifted, used_days column).
$approved_days_by_employee_type = $rows
    ? $CI->Leave_model->get_approved_days_for_balances(
        array_column($rows, 'employee_id'), array_column($rows, 'leave_type_id'), $year
      )
    : [];

// Precomputed once per row (not per render) so the column sort just below and
// the render loop further down always agree on the exact same figures.
foreach ($rows as $b) {
    $hpd = $b->hours_per_day ?: 8;
    $b->_used_minutes_exact = hr_leave_days_exact_minutes($approved_days_by_employee_type[$b->employee_id][$b->leave_type_id] ?? [], $hpd);
    $b->_allocated_minutes  = ($b->allocated_days + $b->carry_forward_days) * $hpd * 60;
    $b->_remaining_minutes  = max(0, $b->_allocated_minutes - $b->_used_minutes_exact);
}

// The DataTable's own column-header sort (the client sends order[column,dir]
// on every AJAX request) - server-side since rows here are built manually,
// not through the generic data_tables_init() helper.
hr_module_apply_datatable_order($rows, [
    0 => 'employee_name', 1 => 'department_name', 2 => 'leave_type_name',
    3 => '_allocated_minutes', 4 => '_used_minutes_exact', 5 => '_remaining_minutes',
]);

// The DataTable's own pagination - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so start/length
// have to be applied by hand after the filtered/sorted set is ready.
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
    $total = $b->allocated_days + $b->carry_forward_days;
    $pct   = $total > 0 ? min(100, round($b->used_days / $total * 100)) : 0;
    $color = $pct >= 90 ? 'danger' : ($pct >= 60 ? 'warning' : 'success');

    // Used/Remaining - precomputed above (exact minutes from this employee's
    // approved leave days, see get_used_minutes_exact()'s comment for why
    // used_days itself can drift once hourly-leave is involved) so the sort
    // above and this render use the exact same figures.
    $hpd_for_balance   = $b->hours_per_day ?: 8;
    $used_minutes      = $b->_used_minutes_exact;
    $remaining_minutes = $b->_remaining_minutes;

    $allocated_cell = hr_format_day_duration($total, $b->hours_per_day);
    if ($b->carry_forward_days > 0) {
        $allocated_cell .= ' <small class="text-muted">(+' . hr_format_day_duration($b->carry_forward_days, $b->hours_per_day) . ' CF)</small>';
    }

    $remaining_cell = '<strong class="text-' . $color . '">' . hr_format_total_minutes_duration($remaining_minutes, $hpd_for_balance) . '</strong>'
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
        hr_format_total_minutes_duration($used_minutes, $hpd_for_balance),
        $remaining_cell,
    ];
    $output['aaData'][] = $row;
}
