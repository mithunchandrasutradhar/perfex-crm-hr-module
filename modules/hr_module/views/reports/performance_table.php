<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');

$f = [];
foreach (['department_id', 'year', 'status'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}
if (empty($f['year'])) $f['year'] = date('Y');

$rows = $CI->Reports_model->performance($f);

// The DataTable's own column-header sort (the client sends order[column,dir]
// on every AJAX request) - server-side since rows here are built manually,
// not through the generic data_tables_init() helper.
hr_module_apply_datatable_order($rows, [
    0 => function ($r) { return $r->first_name . ' ' . $r->last_name; },
    1 => 'department_name',
    2 => 'target_title',
    3 => 'sub_target_title',
    4 => 'assigned_by_name',
    5 => 'evaluator_names',
    6 => 'due_date',
    7 => 'completion_percentage',
    8 => 'status',
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

$sbadge = ['pending' => 'default', 'in_progress' => 'warning', 'partially_completed' => 'info', 'completed' => 'success'];

foreach ($rows as $r) {
    $output['aaData'][] = [
        htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . htmlspecialchars($r->employee_code) . '</small>',
        htmlspecialchars($r->department_name ?? '-'),
        htmlspecialchars($r->target_title),
        htmlspecialchars($r->sub_target_title),
        htmlspecialchars($r->assigned_by_name ?? '-'),
        $r->evaluator_names ? htmlspecialchars($r->evaluator_names) : '-',
        $r->due_date ? date('d M Y', strtotime($r->due_date)) : '-',
        '<span class="text-right" style="display:block">' . ($r->completion_percentage !== null ? rtrim(rtrim(number_format($r->completion_percentage, 2), '0'), '.') . '%' : '-') . '</span>',
        '<span class="label label-' . ($sbadge[$r->status] ?? 'default') . '">' . ucfirst(str_replace('_', ' ', $r->status)) . '</span>',
    ];
}
