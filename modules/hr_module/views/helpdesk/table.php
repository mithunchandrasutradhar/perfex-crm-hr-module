<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Helpdesk_model');

$filters = [];
foreach (['employee_id', 'department_id', 'status', 'priority'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_helpdesk')) {
    $filters['employee_id'] = hr_get_own_employee_id();
}

$rows   = $CI->Helpdesk_model->get_for_table($filters);
$sbadge = ['open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'info', 'closed' => 'default'];
$pbadge = ['low' => 'default', 'medium' => 'warning', 'high' => 'danger'];

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

foreach ($rows as $r) {
    $status   = '<span class="label label-' . ($sbadge[$r->status] ?? 'default') . '">' . ucfirst(str_replace('_', ' ', $r->status)) . '</span>';
    $priority = '<span class="label label-' . ($pbadge[$r->priority] ?? 'default') . '">' . ucfirst($r->priority) . '</span>';
    $replies  = $r->reply_count > 0 ? '<span class="badge badge-secondary">' . $r->reply_count . '</span>' : '-';

    $view_url = admin_url('hr_module/helpdesk/view/' . $r->id);
    $subject_cell = '<a href="' . $view_url . '"><strong>#' . $r->id . '</strong> ' . htmlspecialchars($r->subject) . '</a>';
    $options = [];
    $options[] = '<a href="' . $view_url . '">' . _l('hr_view') . '</a>';
    if (staff_can('delete', 'hr_helpdesk')) {
        $options[] = '<a href="' . admin_url('hr_module/helpdesk/delete/' . $r->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $subject_cell .= '<div class="row-options">' . implode(' | ', $options) . '</div>';

    $submitter = $r->is_anonymous
        ? '<span class="text-muted"><i class="fa fa-user-secret tw-mr-1"></i>' . _l('hr_helpdesk_anonymous') . '</span>'
        : htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . htmlspecialchars($r->department_name ?? '') . '</small>';

    $row = [
        $subject_cell,
        $submitter,
        $r->category ? htmlspecialchars($r->category) : '-',
        $priority,
        $replies,
        $r->assigned_name ? htmlspecialchars($r->assigned_name) : '<span class="text-muted">Unassigned</span>',
        $status,
        date('d M Y H:i', strtotime($r->created_at)),
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
