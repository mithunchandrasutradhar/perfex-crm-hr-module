<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Overtime_model');

$filters = [];
foreach (['employee_id', 'department_id', 'status', 'from_date', 'to_date'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}
// The date filters now come from the same site-display-format datepicker
// widget Attendance's list already uses (see attendance/table.php) - convert
// back to the ISO format Overtime_model's date comparisons expect.
if (!empty($filters['from_date'])) $filters['from_date'] = to_sql_date($filters['from_date']);
if (!empty($filters['to_date']))   $filters['to_date']   = to_sql_date($filters['to_date']);

if (!is_admin() && !staff_can('view', 'hr_overtime')) {
    if (staff_can('view_department', 'hr_overtime')) {
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

$rows = $CI->Overtime_model->get_for_table($filters);

// The DataTable's own pagination - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so start/length
// have to be applied by hand after the filtered set is fetched.
$total_filtered = count($rows);
$dt_start  = (int) $CI->input->post('start');
$dt_length = (int) $CI->input->post('length');
if ($dt_length > 0) $rows = array_slice($rows, $dt_start, $dt_length);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => $total_filtered,
    'iTotalDisplayRecords' => $total_filtered,
    'aaData'               => [],
];

$badge = ['pending' => 'default', 'approved' => 'success', 'rejected' => 'danger'];
$day_type_labels = [
    'weekend'            => _l('hr_overtime_weekend'),
    'government_holiday' => _l('hr_overtime_government_holiday'),
    'company_holiday'    => _l('hr_overtime_company_holiday'),
];

// Self-service users (no global/"view" permission) may act on their own pending requests.
$own_emp_id = (!is_admin() && !staff_can('view', 'hr_overtime')) ? hr_get_own_employee_id() : 0;

foreach ($rows as $r) {
    $status  = '<span class="label label-' . ($badge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>';

    $day_type_cell = implode(', ', array_map(function ($t) use ($day_type_labels) {
        return $day_type_labels[$t] ?? $t;
    }, explode(',', $r->day_types)));

    $date_cell = $r->first_date === $r->last_date
        ? date('D, d M Y', strtotime($r->first_date))
        : date('d M', strtotime($r->first_date)) . ' - ' . date('d M Y', strtotime($r->last_date));
    $date_cell .= '<br><small class="text-muted">' . $r->day_count . ' ' . ($r->day_count == 1 ? 'day' : 'days') . '</small>';

    $is_own_pending = $own_emp_id && (int) $r->employee_id === $own_emp_id && $r->status === 'pending';

    $view_url = admin_url('hr_module/overtime/view/' . $r->id);
    $employee_cell = '<a href="' . $view_url . '">' . htmlspecialchars($r->first_name . ' ' . $r->last_name) . '</a><br><small class="text-muted">' . $r->employee_code . '</small>';
    $options = [];
    $options[] = '<a href="' . $view_url . '">' . _l('hr_view') . '</a>';
    if (staff_can('edit', 'hr_overtime') && $r->status === 'pending') {
        $options[] = '<a href="' . admin_url('hr_module/overtime/approve/' . $r->id) . '" class="text-success" onclick="return confirm(\'' . addslashes(_l('hr_overtime_approve_confirm')) . '\');">' . _l('hr_overtime_approve') . '</a>';
        $reject_form_id = 'hr-ot-reject-' . $r->id;
        $options[] = '<a href="#" class="text-danger hr-ot-reject" data-target="' . $reject_form_id . '">' . _l('hr_overtime_reject') . '</a>';
    }
    if ((staff_can('edit', 'hr_overtime') || $is_own_pending) && $r->status === 'pending') {
        $options[] = '<a href="' . admin_url('hr_module/overtime/edit/' . $r->id) . '">' . _l('hr_edit') . '</a>';
    }
    if ((staff_can('delete', 'hr_overtime') && $r->status !== 'approved') || $is_own_pending) {
        $options[] = '<a href="' . admin_url('hr_module/overtime/delete/' . $r->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $employee_cell .= '<div class="row-options">' . implode(' | ', $options) . '</div>';

    if (staff_can('edit', 'hr_overtime') && $r->status === 'pending') {
        $employee_cell .= '<form id="' . $reject_form_id . '" method="post" action="' . admin_url('hr_module/overtime/reject/' . $r->id) . '" style="display:none">'
            . form_hidden($CI->security->get_csrf_token_name(), $CI->security->get_csrf_hash())
            . '<input type="hidden" name="rejection_reason" value="">'
            . '</form>';
    }

    $row = [
        $employee_cell,
        $r->department_name ? htmlspecialchars($r->department_name) : '-',
        $date_cell,
        $day_type_cell,
        $status,
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
