<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Loans_model');

$filters = [];
foreach (['employee_id', 'department_id', 'status'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

$rows = $CI->Loans_model->get_for_table($filters);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

$badge = ['pending' => 'default', 'approved' => 'warning', 'active' => 'info', 'rejected' => 'danger', 'closed' => 'success'];

foreach ($rows as $r) {
    $status   = '<span class="label label-' . ($badge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>';
    $progress = '';
    if ($r->amount > 0) {
        $pct      = min(100, round(($r->total_repaid / $r->amount) * 100));
        $progress = '<div class="progress tw-mb-0" style="height:6px;min-width:80px"><div class="progress-bar progress-bar-success" style="width:' . $pct . '%"></div></div><small class="text-muted">' . $pct . '%</small>';
    }
    $actions = '<a href="' . admin_url('hr_module/loans/view/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    if (staff_can('delete', 'hr_loans') && !in_array($r->status, ['active', 'closed'])) {
        $actions .= '<a href="' . admin_url('hr_module/loans/delete/' . $r->id) . '" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';
    }
    $output['aaData'][] = [
        '<a href="' . admin_url('hr_module/loans/view/' . $r->id) . '">' . htmlspecialchars($r->first_name . ' ' . $r->last_name) . '</a><br><small class="text-muted">' . $r->employee_code . '</small>',
        $r->department_name ? htmlspecialchars($r->department_name) : '-',
        number_format($r->amount, 2),
        number_format($r->monthly_installment, 2),
        number_format($r->outstanding, 2),
        $progress,
        $status,
        $r->disbursement_date ? date('d M Y', strtotime($r->disbursement_date)) : '-',
        $actions,
    ];
}
