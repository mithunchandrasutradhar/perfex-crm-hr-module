<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Shifts_model');

$filters = [];
foreach (['employee_id', 'department_id', 'shift_type_id', 'status'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_shifts')) {
    if (staff_can('view_department', 'hr_shifts')) {
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

$rows = $CI->Shifts_model->get_all($filters);
$can_manage_any = is_admin() || staff_can('approve', 'hr_shifts') || staff_can('edit', 'hr_shifts');
$can_approve    = is_admin() || staff_can('approve', 'hr_shifts');

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

$badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];

foreach ($rows as $r) {
    $view_url = admin_url('hr_module/shifts/view/' . $r->id);
    $is_owner = (int) $r->employee_id === hr_get_own_employee_id();

    $employee_cell = '<a href="' . $view_url . '">' . htmlspecialchars($r->employee_name) . '</a><br><small class="text-muted">' . htmlspecialchars($r->employee_code) . '</small>';
    $options = ['<a href="' . $view_url . '">' . _l('hr_view') . '</a>'];
    if ($r->status === 'pending' && $can_approve) {
        $options[] = '<a href="#" class="hr-shift-approve" data-id="' . $r->id . '">' . _l('hr_shift_approve') . '</a>';
        $options[] = '<a href="#" class="hr-shift-reject" data-id="' . $r->id . '">' . _l('hr_shift_reject') . '</a>';
    }
    if ($r->status === 'pending' && ($can_manage_any || $is_owner)) {
        $options[] = '<a href="' . admin_url('hr_module/shifts/delete/' . $r->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $employee_cell .= '<div class="row-options">' . implode(' | ', $options) . '</div>';

    $status_cell = '<span class="label label-' . ($badge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>';
    $date_range  = date('d M Y', strtotime($r->from_date)) . ($r->to_date !== $r->from_date ? ' - ' . date('d M Y', strtotime($r->to_date)) : '');

    $row = [
        $employee_cell,
        $r->department_name ? htmlspecialchars($r->department_name) : '-',
        htmlspecialchars($r->shift_name),
        $date_range,
        $status_cell,
        date('d M Y', strtotime($r->created_at)),
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
