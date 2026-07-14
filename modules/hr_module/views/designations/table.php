<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Designations_model');

$rows = $CI->Designations_model->get();

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

foreach ($rows as $row) {
    $total = $CI->Designations_model->total_employees($row->id);
    $badge = $row->status == 1
        ? '<span class="label label-success">' . _l('hr_active') . '</span>'
        : '<span class="label label-default">' . _l('hr_inactive') . '</span>';

    // Standard Perfex row-options: plain text links under the name, not icon buttons
    $name  = htmlspecialchars($row->name);
    $name .= '<div class="row-options">';
    $first = true;
    if (staff_can('edit', 'hr_departments')) {
        $name .= '<a href="' . admin_url('hr_module/designations/edit/' . $row->id) . '">' . _l('hr_edit') . '</a>';
        $first = false;
    }
    if (staff_can('delete', 'hr_departments')) {
        $name .= ($first ? '' : ' | ') . '<a href="' . admin_url('hr_module/designations/delete/' . $row->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $name .= '</div>';

    $aRow = [$name, $total, $badge];
    $aRow['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $aRow;
}
