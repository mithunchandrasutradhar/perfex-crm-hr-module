<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');
$CI->load->model('hr_module/Employees_model');
$CI->load->model('hr_module/Holidays_model');

$f = [];
foreach (['employee_id', 'department_id', 'from_date', 'to_date'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}
if (empty($f['from_date'])) $f['from_date'] = date('Y-m-01');
else $f['from_date'] = to_sql_date($f['from_date']);
if (empty($f['to_date'])) $f['to_date'] = date('Y-m-d');
else $f['to_date'] = to_sql_date($f['to_date']);

$rows = $CI->Reports_model->attendance_summary_by_employee($f);

// This report is built from a per-employee PHP loop (Reports_model), not a
// single flat SQL query - so unlike the data_tables_init()-based reports,
// search and paging both have to be applied here manually.
$search_param = $CI->input->post('search');
$search       = trim((string) ($search_param['value'] ?? ''));
if ($search !== '') {
    $needle = mb_strtolower($search);
    $rows = array_values(array_filter($rows, function ($r) use ($needle) {
        $haystack = mb_strtolower($r->first_name . ' ' . $r->last_name . ' ' . $r->employee_code . ' ' . ($r->department_name ?? ''));
        return mb_strpos($haystack, $needle) !== false;
    }));
}

$total_present = array_sum(array_column((array) $rows, 'present'));
$total_late    = array_sum(array_column((array) $rows, 'late'));
$total_absent  = array_sum(array_column((array) $rows, 'absent'));

// DataTables (serverSide:true) expects only the requested page's rows back -
// totals above are computed over the full (post-search) $rows set, only
// aaData is limited to the current page.
$start      = (int) $CI->input->post('start');
$length     = (int) $CI->input->post('length');
$paged_rows = $length > 0 ? array_slice($rows, $start, $length) : $rows;

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
    'sums'                 => [
        'present' => $total_present,
        'late'    => $total_late,
        'absent'  => $total_absent,
    ],
];

foreach ($paged_rows as $r) {
    $output['aaData'][] = [
        htmlspecialchars($r->first_name . ' ' . $r->last_name),
        htmlspecialchars($r->employee_code),
        htmlspecialchars($r->department_name ?? '-'),
        '<span class="label label-success"><i class="fa fa-check tw-mr-1"></i>' . $r->present . '</span>',
        '<span class="label label-warning"><i class="fa fa-clock tw-mr-1"></i>' . $r->late . '</span>',
        '<span class="label label-danger"><i class="fa fa-xmark tw-mr-1"></i>' . $r->absent . '</span>',
    ];
}
