<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Reports_model');

$f = [];
foreach (['department_id', 'year'] as $k) {
    $v = $CI->input->get($k);
    if ($v !== null && $v !== '') $f[$k] = $v;
}
if (empty($f['year'])) $f['year'] = date('Y');

// Reports_model::department() itself returns [] when no department_id is
// given - this passes filters through unchanged, matching that existing
// "nothing is queried until a department is picked" behavior.
$rows = $CI->Reports_model->department($f);

$total_leave  = round(array_sum(array_column((array) $rows, 'total_leave_days')), 2);
$total_salary = array_sum(array_column((array) $rows, 'total_salary'));

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
    'sums'                 => [
        'total_employees'  => count($rows),
        'department_name'  => !empty($rows) ? ($rows[0]->department_name ?? '') : '',
        'total_leave_days' => $total_leave,
        'total_salary'     => number_format($total_salary, 2),
    ],
];

foreach ($rows as $r) {
    $output['aaData'][] = [
        '<a href="' . admin_url('hr_module/employees/view/' . $r->id) . '">' . htmlspecialchars($r->first_name . ' ' . $r->last_name) . '</a>'
            . '<br><small class="text-muted">' . htmlspecialchars($r->employee_code) . '</small>',
        htmlspecialchars($r->designation_name ?? '-'),
        ucfirst(str_replace('_', ' ', $r->employment_type ?? '-')),
        $r->hire_date ? date('d M Y', strtotime($r->hire_date)) : '-',
        '<span class="text-right" style="display:block">' . round($r->total_leave_days ?? 0, 2) . '</span>',
        '<span class="text-right" style="display:block">' . number_format($r->total_salary ?? 0, 2) . '</span>',
    ];
}
