<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Leave_model');

$rows = $CI->Leave_model->get_type();

// The DataTable's own search box - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so its
// search[value] POST field has to be picked up and applied by hand.
// get_type() has no filter param (it's also used to fetch a single row by
// id elsewhere), so the search is applied here instead of in the model.
$search_value = $CI->input->post('search');
if (!empty($search_value['value'])) {
    $needle = mb_strtolower(trim($search_value['value']));
    $rows   = array_values(array_filter($rows, function ($row) use ($needle) {
        return mb_strpos(mb_strtolower($row->name), $needle) !== false;
    }));
}

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

foreach ($rows as $t) {
    $badge = $t->status == 1
        ? '<span class="label label-success">' . _l('hr_active') . '</span>'
        : '<span class="label label-default">' . _l('hr_inactive') . '</span>';

    // Standard Perfex row-options: plain text links under the name, not icon buttons
    $name  = htmlspecialchars($t->name);
    $name .= '<div class="row-options">';
    $first = true;
    if (staff_can('edit', 'hr_leave')) {
        $name .= '<a href="' . admin_url('hr_module/leave_types/edit/' . $t->id) . '">' . _l('hr_edit') . '</a>';
        $first = false;
    }
    if (staff_can('delete', 'hr_leave')) {
        $name .= ($first ? '' : ' | ') . '<a href="' . admin_url('hr_module/leave_types/delete/' . $t->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $name .= '</div>';

    $aRow = [
        $name,
        $t->days_per_year,
        $t->hours_per_day,
        $t->carry_forward ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-muted"></i>',
        $t->requires_attachment ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-muted"></i>',
        $t->allow_half_day ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-muted"></i>',
        $t->is_date_range ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-muted"></i>',
        $badge,
    ];
    $aRow['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $aRow;
}
