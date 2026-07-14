<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Leave_model');

$filters = [];
foreach (['status', 'leave_type_id', 'employee_id'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_leave')) {
    $filters['employee_id'] = hr_get_own_employee_id();
}

$rows = $CI->Leave_model->get_request(null, $filters);

// Fetch each request's day-type composition in one batched query (not per-row) so the
// list can show "Half Day (Before Lunch)" etc. instead of a bare total.
$day_types_by_request = [];
$ids = array_column($rows, 'id');
if ($ids) {
    $day_types_by_request = $CI->Leave_model->get_day_types_for_requests($ids);
}

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

$badge_map = [
    'pending'   => 'label-warning',
    'approved'  => 'label-success',
    'rejected'  => 'label-danger',
    'cancelled' => 'label-default',
];

foreach ($rows as $r) {
    $badge   = '<span class="label ' . ($badge_map[$r->status] ?? 'label-default') . '">' . ucfirst($r->status) . '</span>';
    $actions = '<a href="' . admin_url('hr_module/leave/view/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a>';
    if (staff_can('delete', 'hr_leave') && in_array($r->status, ['rejected', 'cancelled'])) {
        $actions .= ' <a href="' . admin_url('hr_module/leave/delete/' . $r->id) . '" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';
    }
    $types = $day_types_by_request[$r->id] ?? [];
    // For a single-day request, show exactly which half/type it is. For multi-day
    // requests, only call out the non-obvious types (half/hourly) - "Full" alone
    // on every day isn't worth repeating.
    $days_cell = $r->total_days;
    if (count($types) === 1) {
        $days_cell .= '<br><small class="text-muted">' . htmlspecialchars(hr_leave_day_type_label($types[0])) . '</small>';
    } else {
        $notable = array_diff(array_unique($types), ['full', 'bridge']);
        if ($notable) {
            $labels = array_map('hr_leave_day_type_label', $notable);
            $days_cell .= '<br><small class="text-muted">' . htmlspecialchars(implode(', ', $labels)) . '</small>';
        }
    }

    $output['aaData'][] = [
        $r->id,
        '<a href="' . admin_url('hr_module/employees/view/' . $r->employee_id) . '">' . htmlspecialchars($r->employee_name) . '</a><br><small class="text-muted">' . $r->employee_code . '</small>',
        htmlspecialchars($r->leave_type_name),
        _d($r->from_date),
        _d($r->to_date),
        $days_cell,
        $badge,
        _d($r->created_at),
        $actions,
    ];
}
