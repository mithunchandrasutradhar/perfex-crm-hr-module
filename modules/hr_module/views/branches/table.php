<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Branches_model');

$rows = $CI->Branches_model->get();

// The DataTable's own search box - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so its
// search[value] POST field has to be picked up and applied by hand.
// get() has no filter param (it's also used to fetch a single row by id
// elsewhere), so the search is applied here instead of in the model.
$search_value = $CI->input->post('search');
if (!empty($search_value['value'])) {
    $needle = mb_strtolower(trim($search_value['value']));
    $rows   = array_values(array_filter($rows, function ($row) use ($needle) {
        return mb_strpos(mb_strtolower($row->name), $needle) !== false;
    }));
}

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

foreach ($paged_rows as $row) {
    $total = $CI->Branches_model->total_employees($row->id);
    $badge = $row->status == 1
        ? '<span class="label label-success">' . _l('hr_active') . '</span>'
        : '<span class="label label-default">' . _l('hr_inactive') . '</span>';

    // Standard Perfex row-options: plain text links under the name, not icon buttons
    $name  = htmlspecialchars($row->name);
    $name .= '<div class="row-options">';
    $first = true;
    if (staff_can('edit', 'hr_departments')) {
        $name .= '<a href="' . admin_url('hr_module/branches/edit/' . $row->id) . '">' . _l('hr_edit') . '</a>';
        $first = false;
    }
    if (staff_can('delete', 'hr_departments')) {
        $name .= ($first ? '' : ' | ') . '<a href="' . admin_url('hr_module/branches/delete/' . $row->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $name .= '</div>';

    $aRow = [$name, $total, $badge];
    $aRow['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $aRow;
}
