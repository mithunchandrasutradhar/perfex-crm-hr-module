<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Shifts_model');

$can_edit = is_admin() || staff_can('edit', 'hr_settings');
$rows     = $CI->Shifts_model->get_type();

// The DataTable's own column-header sort (the client sends order[column,dir]
// on every AJAX request) - server-side since rows here are built manually,
// not through the generic data_tables_init() helper.
hr_module_apply_datatable_order($rows, [
    0 => 'name', 1 => 'start_time', 2 => 'end_time',
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

$time_fmt = (get_option('time_format') == 24) ? 'H:i' : 'g:i A';

foreach ($rows as $s) {
    $row = [
        htmlspecialchars($s->name),
        date($time_fmt, strtotime($s->start_time)),
        date($time_fmt, strtotime($s->end_time)),
    ];
    if ($can_edit) {
        $row[] = '<a href="#" class="hr-edit-shift tw-mr-2"'
            . ' data-id="' . $s->id . '"'
            . ' data-name="' . htmlspecialchars($s->name, ENT_QUOTES) . '"'
            . ' data-start="' . date('H:i', strtotime($s->start_time)) . '"'
            . ' data-end="' . date('H:i', strtotime($s->end_time)) . '"'
            . ' title="' . _l('hr_edit') . '"><i class="fa fa-pencil"></i></a>'
            . '<a href="' . admin_url('hr_module/settings/delete_shift/' . $s->id) . '" class="_delete text-danger" title="' . _l('hr_delete') . '"><i class="fa fa-trash"></i></a>';
    }
    $output['aaData'][] = $row;
}
