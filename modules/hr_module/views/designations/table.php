<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Designations_model');
$CI->load->model('hr_module/Departments_model');

$rows = $CI->Designations_model->get();

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

foreach ($rows as $row) {
    $dept = '-';
    if ($row->department_id) {
        $d = $CI->Departments_model->get($row->department_id);
        if ($d) $dept = htmlspecialchars($d->name);
    }
    $total = $CI->Designations_model->total_employees($row->id);
    $badge = $row->status == 1
        ? '<span class="label label-success">' . _l('hr_active') . '</span>'
        : '<span class="label label-default">' . _l('hr_inactive') . '</span>';

    $actions = '';
    if (staff_can('edit', 'hr_departments')) {
        $actions .= '<a href="#" class="btn btn-default btn-xs hr-edit-desig" data-id="' . $row->id . '" title="' . _l('hr_edit') . '"><i class="fa fa-pencil-alt"></i></a> ';
    }
    if (staff_can('delete', 'hr_departments')) {
        $actions .= '<a href="#" class="btn btn-danger btn-xs hr-delete-desig" data-id="' . $row->id . '" data-name="' . htmlspecialchars($row->name) . '" title="' . _l('hr_delete') . '"><i class="fa fa-times"></i></a>';
    }

    $output['aaData'][] = [
        htmlspecialchars($row->name),
        $dept,
        $total,
        $badge,
        $actions,
    ];
}
