<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Overtime_model');

$filters = [];
foreach (['employee_id', 'department_id', 'status', 'from_date', 'to_date'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_overtime')) {
    $filters['employee_id'] = hr_get_own_employee_id();
}

$rows = $CI->Overtime_model->get_for_table($filters);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

$badge = ['pending' => 'default', 'approved' => 'success', 'rejected' => 'danger'];

foreach ($rows as $r) {
    $status  = '<span class="label label-' . ($badge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>';
    $actions = '<a href="' . admin_url('hr_module/overtime/view/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    if (staff_can('edit',   'hr_overtime') && $r->status === 'pending') {
        $actions .= '<a href="' . admin_url('hr_module/overtime/edit/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-pencil-alt"></i></a> ';
    }
    if (staff_can('delete', 'hr_overtime') && $r->status !== 'approved') {
        $actions .= '<a href="' . admin_url('hr_module/overtime/delete/' . $r->id) . '" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';
    }
    $output['aaData'][] = [
        '<a href="' . admin_url('hr_module/employees/view/' . $r->employee_id) . '">' . htmlspecialchars($r->first_name . ' ' . $r->last_name) . '</a><br><small class="text-muted">' . $r->employee_code . '</small>',
        $r->department_name ? htmlspecialchars($r->department_name) : '-',
        date('D, d M Y', strtotime($r->overtime_date)),
        $r->hours . ' hrs',
        $r->rate_multiplier . 'x',
        number_format($r->total_amount, 2),
        $status,
        $actions,
    ];
}
