<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Holidays_model');

$year      = (int) ($CI->input->get('year') ?: date('Y'));
$can_edit  = is_admin() || staff_can('edit', 'hr_holidays');

$rows = $CI->Holidays_model->get_all($year);

// The DataTable's own column-header sort (the client sends order[column,dir]
// on every AJAX request) - server-side since rows here are built manually,
// not through the generic data_tables_init() helper. "Day" has no independent
// sort key of its own (it's derived from holiday_date), so it sorts by the
// same date, same as the render below.
hr_module_apply_datatable_order($rows, [
    0 => 'holiday_date', 1 => 'name', 2 => 'holiday_date',
    3 => 'type', 4 => 'announcement_sent_at',
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

foreach ($rows as $h) {
    $is_range = !empty($h->end_date) && $h->end_date !== $h->holiday_date;

    $date_cell = '<strong>' . date('d M Y', strtotime($h->holiday_date));
    if ($is_range) $date_cell .= ' - ' . date('d M Y', strtotime($h->end_date));
    $date_cell .= '</strong>';

    $day_cell = $is_range
        ? date('D', strtotime($h->holiday_date)) . ' - ' . date('D', strtotime($h->end_date))
        : date('l', strtotime($h->holiday_date));

    $type_cell = $h->type === 'government'
        ? '<span class="label label-danger">Government</span>'
        : '<span class="label label-info">Company</span>';

    $announcement_cell = !empty($h->announcement_sent_at)
        ? '<span class="label label-success" title="' . htmlspecialchars(_dt($h->announcement_sent_at)) . '"><i class="fa fa-check tw-mr-1"></i>' . _l('hr_holiday_announcement_sent_label') . '</span>'
        : '<span class="label label-default">' . _l('hr_holiday_announcement_not_sent_label') . '</span>';

    $row = [
        $date_cell,
        htmlspecialchars($h->name),
        '<span class="text-muted">' . $day_cell . '</span>',
        $type_cell,
        '<span class="announcement-status-cell">' . $announcement_cell . '</span>',
    ];
    if ($can_edit) {
        $row[] = '<a href="#" class="text-muted btn-edit-holiday tw-text-sm tw-mr-2" data-id="' . $h->id . '" title="' . _l('hr_edit') . '"><i class="fa fa-pencil"></i></a>'
            . '<a href="#" class="text-primary btn-send-announcement tw-text-sm tw-mr-2" data-id="' . $h->id . '" title="' . _l('hr_holiday_send_announcement') . '"><i class="fa fa-paper-plane"></i></a>'
            . '<a href="#" class="text-danger btn-delete-holiday tw-text-sm" data-id="' . $h->id . '" title="Delete"><i class="fa fa-trash"></i></a>';
    }
    $output['aaData'][] = $row;
}
