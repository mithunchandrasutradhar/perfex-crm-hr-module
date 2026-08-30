<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Training_model');

$filters = [];
foreach (['status', 'from_date', 'to_date'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}
// The date filters now come from the same site-display-format datepicker
// widget Attendance's list already uses (see attendance/table.php) - convert
// back to the ISO format Training_model's date comparisons expect.
if (!empty($filters['from_date'])) $filters['from_date'] = to_sql_date($filters['from_date']);
if (!empty($filters['to_date']))   $filters['to_date']   = to_sql_date($filters['to_date']);

if (!is_admin() && !staff_can('view', 'hr_training')) {
    if (staff_can('view_department', 'hr_training')) {
        $filters['department_id'] = hr_get_own_department_id();
    } else {
        $filters['own_or_instructor'] = ['employee_id' => hr_get_own_employee_id(), 'staff_id' => get_staff_user_id()];
    }
}

// The DataTable's own search box - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so its
// search[value] POST field has to be picked up and applied by hand.
$search_value = $CI->input->post('search');
if (!empty($search_value['value'])) $filters['search'] = trim($search_value['value']);

$rows = $CI->Training_model->get_for_table($filters);

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

$badge = ['scheduled' => 'default', 'ongoing' => 'warning', 'completed' => 'success', 'cancelled' => 'danger'];

foreach ($rows as $r) {
    $status   = '<span class="label label-' . ($badge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>';
    $capacity = $r->capacity ? $r->enrolled_count . '/' . $r->capacity : $r->enrolled_count . ' enrolled';
    $instructor = $r->instructor_name ?: ($r->trainer ?: '-');

    $view_url = admin_url('hr_module/training/view/' . $r->id);
    $title_cell = '<a href="' . $view_url . '">' . htmlspecialchars($r->title) . '</a>';
    $options = [];
    $options[] = '<a href="' . $view_url . '">' . _l('hr_view') . '</a>';
    if (staff_can('edit', 'hr_training')) {
        $options[] = '<a href="' . admin_url('hr_module/training/edit/' . $r->id) . '">' . _l('hr_edit') . '</a>';
    }
    if (staff_can('delete', 'hr_training')) {
        $options[] = '<a href="' . admin_url('hr_module/training/delete/' . $r->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $title_cell .= '<div class="row-options">' . implode(' | ', $options) . '</div>';

    $row = [
        $title_cell,
        htmlspecialchars($instructor),
        $r->venue   ? htmlspecialchars($r->venue)   : '-',
        date('d M Y', strtotime($r->start_date)),
        date('d M Y', strtotime($r->end_date)),
        number_format($r->cost, 2),
        $capacity,
        $status,
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
