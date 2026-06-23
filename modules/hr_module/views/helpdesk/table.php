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
    $actions  = '<a href="' . admin_url('hr_module/helpdesk/view/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    if (staff_can('delete', 'hr_helpdesk')) {
        $actions .= '<a href="' . admin_url('hr_module/helpdesk/delete/' . $r->id) . '" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';
    }
    $output['aaData'][] = [
        '<a href="' . admin_url('hr_module/helpdesk/view/' . $r->id) . '"><strong>#' . $r->id . '</strong> ' . htmlspecialchars($r->subject) . '</a>',
        htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . htmlspecialchars($r->department_name ?? '') . '</small>',
        $r->category ? htmlspecialchars($r->category) : '-',
        $priority,
        $replies,
        $r->assigned_name ? htmlspecialchars($r->assigned_name) : '<span class="text-muted">Unassigned</span>',
        $status,
        date('d M Y H:i', strtotime($r->created_at)),
        $actions,
    ];
}
