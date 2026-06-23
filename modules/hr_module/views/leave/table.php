<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Leave_model');

$filters = [];
foreach (['status', 'leave_type_id', 'employee_id'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_leave')) {
    $filters['employee_id'] = hr_get_own_employee_id();
}

$rows = $CI->Leave_model->get_request(null, $filters);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

$badge_map = [
    'pending'   => 'label-warning',
    'approved'  => 'label-success',
    'rejected'  => 'label-danger',
    'cancelled' => 'label-default',
];

foreach ($rows as $r) {
    $badge   = '<span class="label ' . ($badge_map[$r->status] ?? 'label-default') . '">' . ucfirst($r->status) . '</span>';
    $actions = '<a href="' . admin_url('hr_module/leave/view/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a>';
    if (staff_can('delete', 'hr_leave') && in_array($r->status, ['rejected', 'cancelled'])) {
        $actions .= ' <a href="' . admin_url('hr_module/leave/delete/' . $r->id) . '" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';
    }
    $output['aaData'][] = [
        $r->id,
        '<a href="' . admin_url('hr_module/employees/view/' . $r->employee_id) . '">' . htmlspecialchars($r->employee_name) . '</a><br><small class="text-muted">' . $r->employee_code . '</small>',
        htmlspecialchars($r->leave_type_name),
        _d($r->from_date),
        _d($r->to_date),
        $r->total_days . ($r->is_half_day ? ' <span class="label label-info">Half</span>' : ''),
        $badge,
        _d($r->created_at),
        $actions,
    ];
}
