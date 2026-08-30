<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Payroll_model');
$CI->load->model('hr_module/Loans_model');
$CI->load->model('hr_module/Shifts_model');

// Same active-items list (and order) views/payroll/index.php built its extra
// column headers from - one cell per item, per row, below.
$payroll_items = $CI->Payroll_model->get_items(true);

$filters = [];
foreach (['employee_id', 'department_id', 'pay_month', 'pay_year', 'status'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_payroll')) {
    $filters['employee_id'] = hr_get_own_employee_id();
}

// The DataTable's own search box - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so its
// search[value] POST field has to be picked up and applied by hand.
$search_value = $CI->input->post('search');
if (!empty($search_value['value'])) $filters['search'] = trim($search_value['value']);

$rows = $CI->Payroll_model->get_for_table($filters);

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

$badge = ['draft' => 'default', 'paid' => 'success'];

foreach ($rows as $r) {
    $status  = '<span class="label label-' . ($badge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>';
    $period  = date('F', mktime(0, 0, 0, $r->pay_month, 1)) . ' ' . $r->pay_year;

    $view_url = admin_url('hr_module/payroll/view/' . $r->id);
    $employee_cell = '<a href="' . $view_url . '">' . htmlspecialchars($r->first_name . ' ' . $r->last_name) . '</a><br><small class="text-muted">' . $r->employee_code . '</small>';
    $options = [];
    $options[] = '<a href="' . $view_url . '">' . _l('hr_view') . '</a>';
    $options[] = '<a href="' . admin_url('hr_module/payroll/slip/' . $r->id) . '" target="_blank">' . _l('hr_payroll_slip') . '</a>';
    if ($r->status === 'draft' && staff_can('edit', 'hr_payroll')) {
        $options[] = '<a href="#" class="hr-mark-paid" data-id="' . $r->id . '">' . _l('hr_payroll_mark_paid') . '</a>';
    }
    if (staff_can('delete', 'hr_payroll') && $r->status !== 'paid') {
        $options[] = '<a href="' . admin_url('hr_module/payroll/delete/' . $r->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $employee_cell .= '<div class="row-options">' . implode(' | ', $options) . '</div>';

    // Small pill: a colored label with two tight, round icon-buttons for approve/reject
    // right beside it, instead of bare colored <i> icons floating next to plain text.
    // $approve/$reject are each ['href' => ..., 'attrs' => 'extra html attrs (no class=)'] or null to omit.
    $deduct_chip = function ($labelClass, $text, $approve, $reject) {
        $chip = '<div class="tw-inline-flex tw-items-center tw-gap-1 tw-mt-1">'
            . '<span class="label ' . $labelClass . '" style="font-weight:500">' . $text . '</span>';
        if ($approve) {
            $chip .= '<a href="' . $approve['href'] . '" class="btn btn-xs btn-success ' . ($approve['class'] ?? '') . '" style="padding:1px 6px" title="Approve" ' . ($approve['attrs'] ?? '') . '><i class="fa fa-check"></i></a>';
        }
        if ($reject) {
            $chip .= '<a href="' . $reject['href'] . '" class="btn btn-xs btn-danger ' . ($reject['class'] ?? '') . '" style="padding:1px 6px" title="Reject" ' . ($reject['attrs'] ?? '') . '><i class="fa fa-times"></i></a>';
        }
        return $chip . '</div>';
    };

    $loan_deduction_cell = '<div class="tw-flex tw-flex-col">';
    $loan_deduction_cell .= $r->loan_deduction > 0
        ? '<span class="text-danger tw-font-medium">-' . number_format($r->loan_deduction, 2) . '</span>'
        : '<span>-</span>';

    // Loan-level requests the EMPLOYEE submitted on their own loan (change/skip) -
    // surfaced here too so HR doesn't have to jump to the Loans module to act on them.
    $employee_requests = $CI->Loans_model->get_pending_requests_for_period($r->employee_id, $r->pay_month, $r->pay_year);
    if ($employee_requests) {
        $carry_labels = ['next_month' => 'carry to next month', 'extend_term' => 'extend term by 1 month'];
        foreach ($employee_requests as $er) {
            $desc = $er->is_skip
                ? 'Skip (' . ($carry_labels[$er->carry_option] ?? $er->carry_option) . ')'
                : number_format($er->amount, 2);
            if (staff_can('edit', 'hr_loans')) {
                $loan_deduction_cell .= $deduct_chip(
                    'label-info',
                    'Employee requested: ' . $desc,
                    ['href' => '#', 'class' => 'hr-approve-loan-request', 'attrs' => 'data-id="' . $er->id . '"'],
                    ['href' => '#', 'class' => 'hr-reject-loan-request', 'attrs' => 'data-id="' . $er->id . '"']
                );
            } else {
                $loan_deduction_cell .= $deduct_chip('label-info', 'Employee requested: ' . $desc, null, null);
            }
        }
    }
    $loan_deduction_cell .= '</div>';

    // Once paid, nothing on this row should move again - it's an actual, already
    // -disbursed financial record, so Overtime/Shift/Gross/Net show exactly what
    // was frozen at mark_paid() time. Only a still-draft payroll (nothing paid
    // out yet) previews live figures from the CURRENT Settings (Weekend/Holiday
    // Overtime Rate, Overtime Day Divisor, Shift Allowance amounts) - none of
    // this is ever written back to the hr_payroll row either way.
    if ($r->status === 'paid') {
        $overtime_days   = (int) $r->overtime_days;
        $overtime_amount = (float) $r->overtime_amount;
        $shift_allowance = $CI->Payroll_model->get_shift_allowance_total($r->id);
        $live_totals     = ['gross' => (float) $r->gross_salary, 'net' => (float) $r->net_salary];
    } else {
        $live_overtime   = $CI->Payroll_model->calculate_live_overtime($r->employee_id, $r->pay_month, $r->pay_year);
        $overtime_days   = $live_overtime['days'];
        $overtime_amount = $live_overtime['amount'];
        $shift_allowance = $CI->Payroll_model->calculate_live_shift_allowance($r->employee_id, $r->pay_month, $r->pay_year);
        $live_totals     = $CI->Payroll_model->calculate_live_gross_net($r, $overtime_amount, $shift_allowance);
    }

    $overtime_cell = $overtime_days > 0
        ? $overtime_days . ' ' . ($overtime_days == 1 ? 'day' : 'days') . '<br><small class="text-muted">+' . number_format($overtime_amount, 2) . '</small>'
        : '-';

    $period_from = sprintf('%04d-%02d-01', $r->pay_year, $r->pay_month);
    $period_to   = date('Y-m-t', strtotime($period_from));
    $shift_cell  = htmlspecialchars($CI->Shifts_model->get_employee_shift_summary($r->employee_id, $period_from, $period_to));
    if ($shift_allowance > 0) {
        $shift_cell .= '<br><small class="text-muted">+' . number_format($shift_allowance, 2) . '</small>';
    }

    // One cell per active payroll item - this employee's actual detail-row amount
    // for that item if it applied to their payroll, '-' if it didn't.
    $details_by_item = [];
    foreach ($CI->Payroll_model->get_details($r->id) as $d) {
        if ($d->payroll_item_id) $details_by_item[$d->payroll_item_id] = $d->amount;
    }
    $item_cells = [];
    foreach ($payroll_items as $item) {
        $item_cells[] = isset($details_by_item[$item->id])
            ? ($item->type === 'deduction' ? '-' : '') . number_format($details_by_item[$item->id], 2)
            : '-';
    }

    $row = array_merge([
        $employee_cell,
        $r->department_name ? htmlspecialchars($r->department_name) : '-',
        $period,
        number_format($r->basic_salary, 2),
    ], $item_cells, [
        $overtime_cell,
        $shift_cell,
        number_format($live_totals['gross'], 2),
        $loan_deduction_cell,
        '<strong>' . number_format($live_totals['net'], 2) . '</strong>',
        $status,
        $r->payment_date ? date('d M Y', strtotime($r->payment_date)) : '-',
    ]);
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
