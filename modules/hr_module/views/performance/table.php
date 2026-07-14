<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Performance_model');

$filters = [];
foreach (['employee_id', 'department_id', 'status', 'year'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_performance')) {
    $filters['employee_id'] = hr_get_own_employee_id();
}

$rows = $CI->Performance_model->get_for_table($filters);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

$badge        = ['pending' => 'default', 'in_progress' => 'warning', 'completed' => 'success'];
$rating_color = ['Excellent' => 'success', 'Very Good' => 'info', 'Good' => 'primary', 'Average' => 'warning', 'Poor' => 'danger'];

foreach ($rows as $r) {
    $period  = date('d M Y', strtotime($r->review_period_from)) . ' &ndash; ' . date('d M Y', strtotime($r->review_period_to));
    $status  = '<span class="label label-' . ($badge[$r->status] ?? 'default') . '">' . ucfirst(str_replace('_', ' ', $r->status)) . '</span>';
    $score   = $r->final_score !== null ? $r->final_score . '%' : '-';
    $rating  = $r->rating ? '<span class="label label-' . ($rating_color[$r->rating] ?? 'default') . '">' . $r->rating . '</span>' : '-';

    $view_url = admin_url('hr_module/performance/view/' . $r->id);
    $employee_cell = '<a href="' . $view_url . '">' . htmlspecialchars($r->first_name . ' ' . $r->last_name) . '</a><br><small class="text-muted">' . $r->employee_code . '</small>';
    $options = [];
    $options[] = '<a href="' . $view_url . '">' . _l('hr_view') . '</a>';
    if (staff_can('edit', 'hr_performance')) {
        $options[] = '<a href="' . admin_url('hr_module/performance/edit/' . $r->id) . '">' . _l('hr_edit') . '</a>';
    }
    if (staff_can('delete', 'hr_performance')) {
        $options[] = '<a href="' . admin_url('hr_module/performance/delete/' . $r->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $employee_cell .= '<div class="row-options">' . implode(' | ', $options) . '</div>';

    $row = [
        $employee_cell,
        $r->department_name ? htmlspecialchars($r->department_name) : '-',
        $period,
        htmlspecialchars($r->reviewer_name ?? '-'),
        $score,
        $rating,
        $status,
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
