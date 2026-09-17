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

$rows = $CI->Reports_model->performance_by_employee($f);

// The DataTable's own column-header sort (the client sends order[column,dir]
// on every AJAX request) - server-side since rows here are built manually,
// not through the generic data_tables_init() helper.
hr_module_apply_datatable_order($rows, [
    0 => function ($r) { return $r->first_name . ' ' . $r->last_name; },
    1 => 'department_name',
    2 => 'total_sub_targets',
    3 => 'completed_count',
    4 => 'in_progress_count',
    5 => 'partial_count',
    6 => 'pending_count',
    7 => 'avg_completion',
    8 => 'avg_rating',
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
    $output['aaData'][] = [
        htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . htmlspecialchars($r->employee_code) . '</small>',
        htmlspecialchars($r->department_name ?? '-'),
        '<span class="text-right" style="display:block">' . (int) $r->total_sub_targets . '</span>',
        '<span class="text-right" style="display:block"><span class="label label-success">' . (int) $r->completed_count . '</span></span>',
        '<span class="text-right" style="display:block"><span class="label label-warning">' . (int) $r->in_progress_count . '</span></span>',
        '<span class="text-right" style="display:block"><span class="label label-info">' . (int) $r->partial_count . '</span></span>',
        '<span class="text-right" style="display:block"><span class="label label-default">' . (int) $r->pending_count . '</span></span>',
        '<span class="text-right" style="display:block">' . ($r->avg_completion !== null ? round($r->avg_completion, 1) . '%' : '-') . '</span>',
        '<span class="text-right" style="display:block">' . ($r->avg_rating !== null ? $r->avg_rating . ' / 5' : '-') . '</span>',
    ];
}
