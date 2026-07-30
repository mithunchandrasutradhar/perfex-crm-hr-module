<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Email_templates_model');

$can_edit = staff_can('edit', 'hr_settings') || is_admin();
$rows     = $CI->Email_templates_model->get_all();

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

foreach ($rows as $tpl) {
    $name_cell = htmlspecialchars($tpl->name);
    if ($can_edit) {
        $name_cell .= '<div class="row-options">'
            . '<a href="#" class="hr-edit-template" data-id="' . $tpl->id . '">' . _l('hr_edit') . '</a>'
            . '</div>';
    }

    $row = [
        $name_cell,
        htmlspecialchars($tpl->subject),
        $tpl->updated_at ? date('d M Y', strtotime($tpl->updated_at)) : '-',
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
