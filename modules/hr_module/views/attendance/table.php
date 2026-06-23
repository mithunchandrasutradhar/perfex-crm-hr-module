<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Attendance_model');

$filters = [];
foreach (['department_id', 'employee_id', 'status', 'from_date', 'to_date'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_attendance')) {
    $filters['employee_id'] = hr_get_own_employee_id();
}

$rows = $CI->Attendance_model->get_for_table($filters);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

$badge = ['present' => 'success', 'late' => 'warning', 'absent' => 'danger', 'half_day' => 'info'];

foreach ($rows as $r) {
    $status_badge = '<span class="label label-' . ($badge[$r->status] ?? 'default') . '">' . ucfirst(str_replace('_', ' ', $r->status)) . '</span>';
    $source_icon  = $r->source === 'zkteco'
        ? '<i class="fa fa-fingerprint text-info" title="ZKTeco"></i>'
        : '<i class="fa fa-keyboard text-muted" title="Manual"></i>';
    $actions = '';
    if (staff_can('edit',   'hr_attendance')) $actions .= '<a href="#" class="btn btn-default btn-xs hr-edit-att" data-id="' . $r->id . '"><i class="fa fa-pencil-alt"></i></a> ';
    if (staff_can('delete', 'hr_attendance')) $actions .= '<a href="' . admin_url('hr_module/attendance/delete/' . $r->id) . '" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';

    $output['aaData'][] = [
        '<a href="' . admin_url('hr_module/employees/view/' . $r->employee_id) . '">' . htmlspecialchars($r->employee_name) . '</a><br><small class="text-muted">' . $r->employee_code . '</small>',
        $r->department_name ? htmlspecialchars($r->department_name) : '-',
        date('D, d M Y', strtotime($r->attendance_date)),
        $r->in_time  ? substr($r->in_time, 0, 5)  : '-',
        $r->out_time ? substr($r->out_time, 0, 5) : '-',
        $r->working_hours ? $r->working_hours . ' h' : '-',
        $status_badge,
        $source_icon,
        $actions,
    ];
}
