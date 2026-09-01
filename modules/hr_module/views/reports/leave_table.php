<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');

$f = [];
foreach (['department_id', 'leave_type_id', 'status', 'from_date', 'to_date'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}
if (!empty($f['from_date'])) $f['from_date'] = to_sql_date($f['from_date']);
if (!empty($f['to_date']))   $f['to_date']   = to_sql_date($f['to_date']);

$rows = $CI->Reports_model->leave($f);

$sbadge = ['approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', 'cancelled' => 'default'];

$counts = ['approved' => 0, 'pending' => 0, 'rejected' => 0, 'total_days' => 0];
foreach ($rows as $r) {
    if (isset($counts[$r->status])) $counts[$r->status]++;
    if ($r->status === 'approved') $counts['total_days'] += $r->days_requested;
}

// DataTables (serverSide:true) expects only the requested page's rows back -
// sums/totals above are still computed over the FULL $rows set, only aaData
// is limited to the current page.
$start      = (int) $CI->input->post('start');
$length     = (int) $CI->input->post('length');
$paged_rows = $length > 0 ? array_slice($rows, $start, $length) : $rows;

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
    'sums'                 => [
        'approved'      => $counts['approved'],
        'pending'       => $counts['pending'],
        'rejected'      => $counts['rejected'],
        'total_days'    => round($counts['total_days'], 2),
        'total_records' => count($rows),
    ],
];

foreach ($paged_rows as $r) {
    $output['aaData'][] = [
        htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . htmlspecialchars($r->employee_code) . '</small>',
        htmlspecialchars($r->department_name ?? '-'),
        htmlspecialchars($r->leave_type_name ?? '-'),
        date('d M Y', strtotime($r->from_date)),
        date('d M Y', strtotime($r->to_date)),
        round($r->days_requested, 2),
        '<span class="label label-' . ($sbadge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>',
    ];
}
