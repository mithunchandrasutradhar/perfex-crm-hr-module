<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Loans_model');

if (staff_cant('view', 'hr_loans')) access_denied('hr_loans');

$filters = [];
foreach (['status', 'pay_month', 'pay_year', 'employee_id'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

$rows = $CI->Loans_model->get_deduction_requests($filters);

// The DataTable's own search box - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so its
// search[value] POST field has to be picked up and applied by hand.
$search_param = $CI->input->post('search');
$search       = trim((string) ($search_param['value'] ?? ''));
if ($search !== '') {
    $needle = mb_strtolower($search);
    $rows = array_values(array_filter($rows, function ($r) use ($needle) {
        $haystack = mb_strtolower($r->first_name . ' ' . $r->last_name . ' ' . $r->employee_code);
        return mb_strpos($haystack, $needle) !== false;
    }));
}

hr_module_apply_datatable_order($rows, [
    0 => 'id',
    1 => function ($r) { return $r->first_name . ' ' . $r->last_name; },
    2 => 'loan_id',
    3 => function ($r) { return ((int) $r->pay_year) * 100 + (int) $r->pay_month; },
    4 => 'amount',
    5 => 'monthly_installment',
    6 => 'outstanding',
    7 => 'status',
    8 => 'reviewed_by_name',
    9 => null,
]);

// The DataTable's own pagination - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so start/length
// have to be applied by hand after the filtered set is fetched.
$total_filtered = count($rows);
$dt_start  = (int) $CI->input->post('start');
$dt_length = (int) $CI->input->post('length');
if ($dt_length > 0) $rows = array_slice($rows, $dt_start, $dt_length);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => $total_filtered,
    'iTotalDisplayRecords' => $total_filtered,
    'aaData'               => [],
];

$req_badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
$can_edit  = staff_can('edit', 'hr_loans');

foreach ($rows as $r) {
    $view_url = admin_url('hr_module/loans/view/' . $r->loan_id);

    $employee_cell = '<a href="' . $view_url . '">' . htmlspecialchars($r->first_name . ' ' . $r->last_name) . '</a>'
        . '<br><small class="text-muted">' . htmlspecialchars($r->employee_code) . '</small>';

    $loan_cell = '<a href="' . $view_url . '">#' . str_pad($r->loan_id, 4, '0', STR_PAD_LEFT) . '</a>';

    $month_cell = date('M Y', mktime(0, 0, 0, $r->pay_month, 1, $r->pay_year));

    if ($r->is_skip) {
        $requested_cell = '<span class="label label-default">Skip</span> '
            . '<small class="text-muted">' . ($r->carry_option === 'extend_term' ? '(+1 month)' : '(&rarr; next month)') . '</small>';
    } else {
        $requested_cell = number_format($r->amount, 2);
    }

    if ($r->status === 'approved' && $r->payroll_id) {
        $status_cell = '<span class="label label-info">Deducted</span>';
    } else {
        $status_cell = '<span class="label label-' . ($req_badge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>';
    }

    $notes_cell = $r->notes ? '<span title="' . htmlspecialchars($r->notes) . '"><i class="fa fa-comment-o"></i></span>' : '-';

    $row = [
        $r->id,
        $employee_cell,
        $loan_cell,
        $month_cell,
        '<span class="tw-font-semibold">' . $requested_cell . '</span>',
        '<span class="text-muted">' . number_format($r->monthly_installment, 2) . '</span>',
        '<span class="text-warning">' . number_format($r->outstanding, 2) . '</span>',
        $status_cell,
        $r->reviewed_by_name ? htmlspecialchars($r->reviewed_by_name) : '-',
        $notes_cell,
    ];

    if ($can_edit) {
        if ($r->status === 'pending') {
            $csrf = form_hidden($CI->security->get_csrf_token_name(), $CI->security->get_csrf_hash());
            $actions  = '<form method="post" action="' . admin_url('hr_module/loans/approve_deduction/' . $r->id) . '" style="display:inline">' . $csrf
                . '<button type="submit" class="btn btn-xs btn-success tw-mr-1" onclick="return confirm(\'Approve deduction of '
                . number_format($r->amount, 2) . ' for ' . date('M Y', mktime(0, 0, 0, $r->pay_month, 1, $r->pay_year)) . '?\')">'
                . '<i class="fa fa-check"></i> Approve</button></form>';
            $actions .= '<form method="post" action="' . admin_url('hr_module/loans/reject_deduction/' . $r->id) . '" style="display:inline">' . $csrf
                . '<button type="submit" class="btn btn-xs btn-danger" onclick="return confirm(\'Reject this deduction request?\')">'
                . '<i class="fa fa-times"></i> Reject</button></form>';
        } else {
            $actions = '<span class="text-muted">—</span>';
        }
        $row[] = $actions;
    }

    $output['aaData'][] = $row;
}
