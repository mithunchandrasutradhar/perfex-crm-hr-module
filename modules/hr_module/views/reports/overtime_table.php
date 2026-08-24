<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');

$f = [];
foreach (['department_id', 'status', 'from_date', 'to_date'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}
if (!empty($f['from_date'])) $f['from_date'] = to_sql_date($f['from_date']);
if (!empty($f['to_date']))   $f['to_date']   = to_sql_date($f['to_date']);

$rows = $CI->Reports_model->overtime($f);

$sbadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
$day_type_labels = [
    'weekend'            => _l('hr_overtime_weekend'),
    'government_holiday' => _l('hr_overtime_government_holiday'),
    'company_holiday'    => _l('hr_overtime_company_holiday'),
];

$total_amount = array_sum(array_column((array) $rows, 'total_amount'));

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
    'sums'                 => [
        'total_records' => count($rows),
        'total_amount'  => number_format($total_amount, 2),
    ],
];

foreach ($rows as $r) {
    $output['aaData'][] = [
        htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . htmlspecialchars($r->employee_code) . '</small>',
        htmlspecialchars($r->department_name ?? '-'),
        date('d M Y', strtotime($r->overtime_date)),
        ($day_type_labels[$r->day_type] ?? '-') . ($r->holiday_name ? '<br><small class="text-muted">' . htmlspecialchars($r->holiday_name) . '</small>' : ''),
        $r->rate_multiplier . 'x',
        '<span class="text-right" style="display:block">' . number_format($r->total_amount, 2) . '</span>',
        '<span class="label label-' . ($sbadge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>',
    ];
}
