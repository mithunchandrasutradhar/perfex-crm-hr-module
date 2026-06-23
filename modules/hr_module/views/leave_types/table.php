<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Leave_model');

$rows = $CI->Leave_model->get_type();

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

foreach ($rows as $t) {
    $badge   = $t->status == 1
        ? '<span class="label label-success">' . _l('hr_active') . '</span>'
        : '<span class="label label-default">' . _l('hr_inactive') . '</span>';
    $actions = '';
    if (staff_can('edit', 'hr_leave')) {
        $actions .= '<a href="#" class="btn btn-default btn-xs hr-edit-ltype" data-id="' . $t->id . '"><i class="fa fa-pencil-alt"></i></a> ';
    }
    if (staff_can('delete', 'hr_leave')) {
        $actions .= '<a href="#" class="btn btn-danger btn-xs hr-del-ltype" data-id="' . $t->id . '" data-name="' . htmlspecialchars($t->name) . '"><i class="fa fa-times"></i></a>';
    }
    $output['aaData'][] = [
        htmlspecialchars($t->name),
        $t->days_per_year,
        $t->carry_forward ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-muted"></i>',
        $t->requires_attachment ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-muted"></i>',
        $t->allow_half_day ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-muted"></i>',
        $badge,
        $actions,
    ];
}
