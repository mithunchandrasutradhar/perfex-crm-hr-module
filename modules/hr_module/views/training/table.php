<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Training_model');

$filters = [];
foreach (['status', 'from_date', 'to_date'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!staff_can('view', 'hr_training') && staff_can('view_own', 'hr_training')) {
    $filters['participant_employee_id'] = hr_get_own_employee_id();
}

$rows = $CI->Training_model->get_for_table($filters);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

$badge = ['scheduled' => 'default', 'ongoing' => 'warning', 'completed' => 'success', 'cancelled' => 'danger'];

foreach ($rows as $r) {
    $status   = '<span class="label label-' . ($badge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>';
    $capacity = $r->capacity ? $r->enrolled_count . '/' . $r->capacity : $r->enrolled_count . ' enrolled';
    $actions  = '<a href="' . admin_url('hr_module/training/view/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    if (staff_can('edit',   'hr_training')) {
        $actions .= '<a href="' . admin_url('hr_module/training/edit/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-pencil-alt"></i></a> ';
    }
    if (staff_can('delete', 'hr_training')) {
        $actions .= '<a href="' . admin_url('hr_module/training/delete/' . $r->id) . '" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';
    }
    $output['aaData'][] = [
        '<a href="' . admin_url('hr_module/training/view/' . $r->id) . '">' . htmlspecialchars($r->title) . '</a>',
        $r->trainer ? htmlspecialchars($r->trainer) : '-',
        $r->venue   ? htmlspecialchars($r->venue)   : '-',
        date('d M Y', strtotime($r->start_date)),
        date('d M Y', strtotime($r->end_date)),
        number_format($r->cost, 2),
        $capacity,
        $status,
        $actions,
    ];
}
