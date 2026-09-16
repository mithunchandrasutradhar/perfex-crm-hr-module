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

// Batched fetch (one query for the whole page, not one per row) of every
// approved leave day behind these balances' used_days - see
// get_used_minutes_exact()'s comment for why the exact minutes are used
// instead of the accumulated (and possibly drifted) used_days column.
$approved_days_by_employee_type = $rows
    ? $CI->Leave_model->get_approved_days_for_balances(
        array_column($rows, 'employee_id'), array_column($rows, 'leave_type_id'), $year
      )
    : [];

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

    // Used/Remaining computed from the exact minutes of this employee's
    // approved leave days for this type/year - see get_used_minutes_exact()'s
    // comment for why used_days itself can drift once hourly-leave is involved.
    $hpd_for_balance   = $b->hours_per_day ?: 8;
    $used_minutes      = hr_leave_days_exact_minutes($approved_days_by_employee_type[$b->employee_id][$b->leave_type_id] ?? [], $hpd_for_balance);
    $allocated_minutes = $total * $hpd_for_balance * 60;
    $remaining_minutes = max(0, $allocated_minutes - $used_minutes);

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
