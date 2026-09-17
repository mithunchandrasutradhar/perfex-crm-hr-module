<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Payroll_model');

$rows = $CI->Payroll_model->get_items();

// The DataTable's own column-header sort (the client sends order[column,dir]
// on every AJAX request) - server-side since rows here are built manually,
// not through the generic data_tables_init() helper.
hr_module_apply_datatable_order($rows, [
    0 => 'name', 1 => 'type', 2 => 'calculation_type',
    3 => 'value', 4 => 'taxable', 5 => 'status',
]);

// The DataTable's own pagination - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so start/length
// have to be applied by hand after the sorted set is ready.
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

foreach ($rows as $item) {
    $options = [];
    if (staff_can('edit', 'hr_payroll')) {
        $options[] = '<a href="#" class="hr-edit-item" data-id="' . $item->id . '">' . _l('hr_edit') . '</a>';
    }
    if (staff_can('delete', 'hr_payroll')) {
        $options[] = '<a href="' . admin_url('hr_module/payroll_items/delete/' . $item->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }

    $name_cell = '<strong>' . htmlspecialchars($item->name) . '</strong>';
    if ($item->description) {
        $name_cell .= '<br><small class="text-muted">' . htmlspecialchars($item->description) . '</small>';
    }
    if ($options) {
        $name_cell .= '<div class="row-options">' . implode(' | ', $options) . '</div>';
    }

    $type_cell = $item->type === 'allowance'
        ? '<span class="label label-success">Allowance</span>'
        : '<span class="label label-danger">Deduction</span>';

    $value_cell = $item->calculation_type === 'percentage'
        ? $item->value . '%'
        : number_format($item->value, 2);

    $row = [
        $name_cell,
        $type_cell,
        ucfirst($item->calculation_type),
        $value_cell,
        $item->taxable ? '<span class="label label-warning">Yes</span>' : '<span class="label label-default">No</span>',
        $item->status  ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Inactive</span>',
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
