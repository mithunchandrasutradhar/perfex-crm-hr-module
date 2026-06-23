<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Hr_contracts_model');

$filters = [];
foreach (['employee_id', 'department_id', 'status', 'contract_type', 'expiring_soon'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

$rows = $CI->Hr_contracts_model->get_for_table($filters);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

$type_badge   = ['permanent' => 'success', 'fixed' => 'info', 'probation' => 'warning', 'internship' => 'default', 'casual' => 'primary'];
$status_badge = ['active' => 'success', 'expired' => 'default', 'terminated' => 'danger', 'pending' => 'warning'];

foreach ($rows as $r) {
    $expiry_warning = '';
    if ($r->end_date && $r->status === 'active') {
        $days_left = (strtotime($r->end_date) - time()) / 86400;
        if ($days_left >= 0 && $days_left <= 30) {
            $expiry_warning = ' <span class="label label-warning" title="Expiring soon">' . round($days_left) . 'd</span>';
        }
    }
    $actions = '<a href="' . admin_url('hr_module/hr_contracts/view/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    if (staff_can('edit',   'hr_contracts')) {
        $actions .= '<a href="' . admin_url('hr_module/hr_contracts/edit/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-edit"></i></a> ';
    }
    if (staff_can('delete', 'hr_contracts')) {
        $actions .= '<a href="' . admin_url('hr_module/hr_contracts/delete/' . $r->id) . '" class="btn btn-default btn-xs _delete"><i class="fa fa-trash"></i></a>';
    }
    $output['aaData'][] = [
        '<a href="' . admin_url('hr_module/hr_contracts/view/' . $r->id) . '">' . htmlspecialchars($r->title) . '</a>',
        htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . $r->employee_code . '</small>',
        htmlspecialchars($r->department_name ?? '-'),
        '<span class="label label-' . ($type_badge[$r->contract_type] ?? 'default') . '">' . ucfirst($r->contract_type) . '</span>',
        date('d M Y', strtotime($r->start_date)),
        $r->end_date ? date('d M Y', strtotime($r->end_date)) . $expiry_warning : '<span class="text-muted">-</span>',
        $r->value ? number_format($r->value, 2) : '-',
        '<span class="label label-' . ($status_badge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>',
        $r->signed ? '<i class="fa fa-check-circle text-success"></i> ' . ($r->signed_date ? date('d M Y', strtotime($r->signed_date)) : 'Yes') : '<span class="text-muted">Unsigned</span>',
        $actions,
    ];
}
