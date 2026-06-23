<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Payroll_model');

$filters = [];
foreach (['employee_id', 'department_id', 'pay_month', 'pay_year', 'status'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_payroll')) {
    $filters['employee_id'] = hr_get_own_employee_id();
}

$rows = $CI->Payroll_model->get_for_table($filters);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

$badge = ['draft' => 'default', 'approved' => 'warning', 'paid' => 'success'];

foreach ($rows as $r) {
    $status  = '<span class="label label-' . ($badge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>';
    $period  = date('F', mktime(0, 0, 0, $r->pay_month, 1)) . ' ' . $r->pay_year;
    $actions = '<a href="' . admin_url('hr_module/payroll/view/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    $actions .= '<a href="' . admin_url('hr_module/payroll/slip/' . $r->id) . '" class="btn btn-info btn-xs" target="_blank"><i class="fa fa-print"></i></a> ';
    if (staff_can('delete', 'hr_payroll') && $r->status !== 'paid') {
        $actions .= '<a href="' . admin_url('hr_module/payroll/delete/' . $r->id) . '" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';
    }
    $output['aaData'][] = [
        htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . $r->employee_code . '</small>',
        $r->department_name ? htmlspecialchars($r->department_name) : '-',
        $period,
        number_format($r->basic_salary, 2),
        number_format($r->gross_salary, 2),
        number_format($r->net_salary, 2),
        $status,
        $r->payment_date ? date('d M Y', strtotime($r->payment_date)) : '-',
        $actions,
    ];
}
