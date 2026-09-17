<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Departments_model');

$rows = $CI->Departments_model->get();

// The DataTable's own column-header sort (the client sends order[column,dir]
// on every AJAX request) - server-side since rows here are built manually,
// not through the generic data_tables_init() helper.
hr_module_apply_datatable_order($rows, [
    0 => 'name',
]);

// The DataTable's own pagination - rows here are built manually (above)
// instead of through the generic data_tables_init() helper, so start/length
// have to be applied by hand after the full (sorted) set is ready.
$total_filtered = count($rows);
$dt_start  = (int) $CI->input->post('start');
$dt_length = (int) $CI->input->post('length');
$paged_rows = $dt_length > 0 ? array_slice($rows, $dt_start, $dt_length) : $rows;

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => $total_filtered,
    'iTotalDisplayRecords' => $total_filtered,
    'aaData'               => [],
];

foreach ($paged_rows as $dept) {
    $total = $CI->Departments_model->total_employees($dept->id);
    $output['aaData'][] = [
        htmlspecialchars($dept->name),
        $total,
    ];
}
