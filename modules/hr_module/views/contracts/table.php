<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Hr_contracts_model');

$filters = [];
foreach (['employee_id', 'department_id', 'status', 'contract_type', 'expiring_soon'] as $key) {
    $v = $CI->input->get($key);
    if ($v !== null && $v !== '') $filters[$key] = $v;
}

if (!is_admin() && !staff_can('view', 'hr_contracts')) {
    $filters['employee_id'] = hr_get_own_employee_id();
}

// The DataTable's own search box - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so its
// search[value] POST field has to be picked up and applied by hand.
$search_value = $CI->input->post('search');
if (!empty($search_value['value'])) $filters['search'] = trim($search_value['value']);

$rows = $CI->Hr_contracts_model->get_for_table($filters);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

$type_badge   = ['permanent' => 'success', 'fixed' => 'info', 'probation' => 'warning', 'internship' => 'default', 'casual' => 'primary'];
$status_badge = ['active' => 'success', 'expired' => 'default', 'terminated' => 'danger', 'pending' => 'warning'];

foreach ($rows as $r) {
    $expiry_warning = '';
    if ($r->end_date && $r->status === 'active') {
        $days_left = (strtotime($r->end_date) - time()) / 86400;
        if ($days_left >= 0 && $days_left <= 30) {
            $expiry_warning = ' <span class="label label-warning" title="Expiring soon">' . round($days_left) . 'd</span>';
        }
    }
    $view_url = admin_url('hr_module/hr_contracts/view/' . $r->id);
    $title_cell = '<a href="' . $view_url . '">' . htmlspecialchars($r->title) . '</a>';
    $options = [];
    $options[] = '<a href="' . $view_url . '">' . _l('hr_view') . '</a>';
    if (staff_can('edit', 'hr_contracts')) {
        $options[] = '<a href="' . admin_url('hr_module/hr_contracts/edit/' . $r->id) . '">' . _l('hr_edit') . '</a>';
    }
    if (staff_can('delete', 'hr_contracts')) {
        $options[] = '<a href="' . admin_url('hr_module/hr_contracts/delete/' . $r->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $title_cell .= '<div class="row-options">' . implode(' | ', $options) . '</div>';

    $row = [
        $title_cell,
        htmlspecialchars($r->first_name . ' ' . $r->last_name) . '<br><small class="text-muted">' . $r->employee_code . '</small>',
        htmlspecialchars($r->department_name ?? '-'),
        '<span class="label label-' . ($type_badge[$r->contract_type] ?? 'default') . '">' . ucfirst($r->contract_type) . '</span>',
        date('d M Y', strtotime($r->start_date)),
        $r->end_date ? date('d M Y', strtotime($r->end_date)) . $expiry_warning : '<span class="text-muted">-</span>',
        $r->value ? number_format($r->value, 2) : '-',
        '<span class="label label-' . ($status_badge[$r->status] ?? 'default') . '">' . ucfirst($r->status) . '</span>',
        $r->signed ? '<i class="fa fa-check-circle text-success"></i> ' . ($r->signed_date ? date('d M Y', strtotime($r->signed_date)) : 'Yes') : '<span class="text-muted">Unsigned</span>',
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
