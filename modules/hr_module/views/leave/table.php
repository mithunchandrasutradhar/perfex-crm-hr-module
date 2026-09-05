<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Leave_model');

$filters = [];
foreach (['status', 'leave_type_id', 'employee_id', 'department_id'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_leave')) {
    if (staff_can('view_department', 'hr_leave')) {
        $filters['department_id'] = hr_get_own_department_id();
    } else {
        $filters['employee_id'] = hr_get_own_employee_id();
    }
}

// The DataTable's own search box - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so its
// search[value] POST field has to be picked up and applied by hand.
$search_value = $CI->input->post('search');
if (!empty($search_value['value'])) $filters['search'] = trim($search_value['value']);

$rows = $CI->Leave_model->get_request(null, $filters);

// Fetch each request's day-type composition in one batched query (not per-row) so the
// list can show "Half Day (Before Lunch)" etc. instead of a bare total.
$total_filtered = count($rows);

// The DataTable's own pagination - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so start/length
// have to be applied by hand after the filtered set is fetched.
$dt_start  = (int) $CI->input->post('start');
$dt_length = (int) $CI->input->post('length');
if ($dt_length > 0) $rows = array_slice($rows, $dt_start, $dt_length);

$day_types_by_request = [];
$ids = array_column($rows, 'id');
if ($ids) {
    $day_types_by_request = $CI->Leave_model->get_day_types_for_requests($ids);
}

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => $total_filtered,
    'iTotalDisplayRecords' => $total_filtered,
    'aaData'               => [],
];

$badge_map = [
    'pending'   => 'label-warning',
    'approved'  => 'label-success',
    'rejected'  => 'label-danger',
    'cancelled' => 'label-default',
];

$can_approve      = is_admin() || staff_can('approve', 'hr_leave');
$can_soft_approve = is_admin() || staff_can('soft_approve', 'hr_leave');

foreach ($rows as $r) {
    $badge = '<span class="label ' . ($badge_map[$r->status] ?? 'label-default') . '">' . ucfirst($r->status) . '</span>';

    $view_url = admin_url('hr_module/leave/view/' . $r->id);
    $options  = [];
    $options[] = '<a href="' . $view_url . '">' . _l('hr_view') . '</a>';
    if ($can_approve && $r->status === 'pending') {
        $options[] = '<a href="#" class="hr-leave-approve" data-id="' . $r->id . '">' . _l('hr_leave_approve') . '</a>';
        $options[] = '<a href="#" class="hr-leave-reject" data-id="' . $r->id . '">' . _l('hr_leave_reject') . '</a>';
    }
    // Soft approve/reject: informational-only pre-approval, independent of the
    // real Approve/Reject above - shown to a soft-approver role regardless of
    // whether they also hold the full 'approve' capability (mirrors leave/view.php).
    if ($can_soft_approve && $r->status === 'pending' && empty($r->soft_approved_by)) {
        $options[] = '<a href="#" class="hr-leave-soft-approve" data-id="' . $r->id . '">' . _l('hr_leave_soft_approve') . '</a>';
        $options[] = '<a href="#" class="hr-leave-soft-reject" data-id="' . $r->id . '">' . _l('hr_leave_soft_reject') . '</a>';
    }
    if (staff_can('delete', 'hr_leave') && in_array($r->status, ['rejected', 'cancelled'])) {
        $options[] = '<a href="' . admin_url('hr_module/leave/delete/' . $r->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $employee_cell = '<a href="' . admin_url('hr_module/employees/view/' . $r->employee_id) . '">' . htmlspecialchars($r->employee_name) . '</a><br><small class="text-muted">' . $r->employee_code . '</small>';
    $employee_cell .= '<div class="row-options">' . implode(' | ', $options) . '</div>';

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

    $row = [
        $r->id,
        $employee_cell,
        htmlspecialchars($r->leave_type_name),
        _d($r->from_date),
        _d($r->to_date),
        $days_cell,
        $badge,
        _d($r->created_at),
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
