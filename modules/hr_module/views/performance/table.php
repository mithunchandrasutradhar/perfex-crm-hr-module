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
    $filters['own_or_evaluator'] = ['employee_id' => hr_get_own_employee_id(), 'staff_id' => get_staff_user_id()];
    unset($filters['employee_id']);
}

$rows = $CI->Performance_model->get_for_table($filters);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

foreach ($rows as $r) {
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

    $count     = (int) $r->sub_target_count;
    $completed = (int) $r->completed_count;
    $pct       = $count > 0 ? round(($completed / $count) * 100) : 0;
    $progress_cell = $count > 0
        ? '<small class="text-muted">' . $completed . ' / ' . $count . '</small>'
            . '<div class="progress tw-my-0 progress-bar-mini" style="min-width:80px">'
            . '<div class="progress-bar progress-bar-success no-percent-text not-dynamic" role="progressbar" '
            . 'aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100" '
            . 'style="width: ' . $pct . '%" data-percent="' . $pct . '"></div></div>'
        : '-';

    $row = [
        $employee_cell,
        $r->department_name ? htmlspecialchars($r->department_name) : '-',
        htmlspecialchars($r->title),
        htmlspecialchars($r->assigned_by_name ?? '-'),
        $progress_cell,
        $r->due_date ? date('d M Y', strtotime($r->due_date)) : '-',
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
