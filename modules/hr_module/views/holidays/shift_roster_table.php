<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Shifts_model');

// Same GET param/conversion/fallback the page's own initial (non-AJAX) load
// already uses - see Holidays::index().
$roster_date = $CI->input->get('roster_date') ? to_sql_date($CI->input->get('roster_date')) : date('Y-m-d');
if (!$roster_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $roster_date)) {
    $roster_date = date('Y-m-d');
}

$roster_map = $CI->Shifts_model->get_shift_roster_for_date($roster_date);

// Flatten the [type_id => group] map into a plain row list (skipping the
// default "Day Shift" bucket, same as the page already excludes it), so the
// shared sort helper can operate on it like any other row list.
$rows = [];
foreach ($roster_map as $type_id => $group) {
    if ($type_id === 0) continue;
    $rows[] = (object) [
        'name'       => $group['name'],
        'start_time' => $group['start_time'],
        'end_time'   => $group['end_time'],
        'count'      => count($group['employees']),
        'employees'  => $group['employees'],
    ];
}

// The DataTable's own column-header sort (the client sends order[column,dir]
// on every AJAX request) - server-side since rows here are built manually,
// not through the generic data_tables_init() helper. Employees has no single
// sortable value (it's a list of names), so that column is left unsortable.
hr_module_apply_datatable_order($rows, [
    0 => 'name', 1 => 'count', 2 => null,
]);

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

foreach ($rows as $r) {
    $shift_cell = '<span class="label label-info">' . htmlspecialchars($r->name) . '</span>';
    if (!empty($r->start_time) && !empty($r->end_time)) {
        $shift_cell .= ' <span class="text-muted tw-text-sm">' . date('h:i A', strtotime($r->start_time)) . ' - ' . date('h:i A', strtotime($r->end_time)) . '</span>';
    }

    $emp_cell = '<span class="text-muted">-</span>';
    if (!empty($r->employees)) {
        $links = [];
        foreach ($r->employees as $emp) {
            $links[] = '<a href="' . admin_url('hr_module/employees/view/' . $emp['id']) . '">' . htmlspecialchars($emp['name']) . '</a>';
        }
        $emp_cell = implode(', ', $links);
    }

    $output['aaData'][] = [$shift_cell, $r->count, $emp_cell];
}
