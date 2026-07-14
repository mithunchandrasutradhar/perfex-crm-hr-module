<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Loans_model');

$filters = [];
foreach (['employee_id', 'department_id', 'status'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_loans')) {
    $filters['employee_id'] = hr_get_own_employee_id();
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
        $progress = '<small class="text-muted">' . $pct . '%</small>'
            . '<div class="progress tw-my-0 progress-bar-mini" style="min-width:80px">'
            . '<div class="progress-bar progress-bar-success no-percent-text not-dynamic" role="progressbar" '
            . 'aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100" '
            . 'style="width: ' . $pct . '%" data-percent="' . $pct . '"></div></div>';
    }
    $view_url = admin_url('hr_module/loans/view/' . $r->id);
    $employee_cell = '<a href="' . $view_url . '">' . htmlspecialchars($r->first_name . ' ' . $r->last_name) . '</a><br><small class="text-muted">' . $r->employee_code . '</small>';
    $options = [];
    $options[] = '<a href="' . $view_url . '">' . _l('hr_view') . '</a>';
    if (staff_can('delete', 'hr_loans') && !in_array($r->status, ['active', 'closed'])) {
        $options[] = '<a href="' . admin_url('hr_module/loans/delete/' . $r->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $employee_cell .= '<div class="row-options">' . implode(' | ', $options) . '</div>';

    $row = [
        $employee_cell,
        $r->department_name ? htmlspecialchars($r->department_name) : '-',
        number_format($r->amount, 2),
        number_format($r->monthly_installment, 2),
        number_format($r->outstanding, 2),
        $progress,
        $status,
        $r->disbursement_date ? date('d M Y', strtotime($r->disbursement_date)) : '-',
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
