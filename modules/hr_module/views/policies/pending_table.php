<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Policies_model');

// Same gating as Policies::_is_global_manager() - only a global policy
// manager ever sees this queue, matching index()'s own condition for
// showing the "Pending Approval" panel at all.
$is_global = is_admin() || staff_can('view', 'hr_policies');

$rows = [];
if ($is_global) {
    foreach ($CI->Policies_model->get_pending() as $p) {
        $rows[] = (object) [
            'title'            => $p->title,
            'kind'             => 'new',
            'type'             => $p->type,
            'department_names' => $p->department_names ?? null,
            'submitted_by'     => $p->created_by_name,
            'created_at'       => $p->created_at,
            'effective_date'   => $p->effective_date,
            'review_url'       => admin_url('hr_module/policies/view/' . $p->id),
        ];
    }
    foreach ($CI->Policies_model->get_pending_revisions() as $r) {
        $rows[] = (object) [
            'title'            => $r->policy_title,
            'kind'             => 'update',
            'type'             => $r->type,
            'department_names' => $r->department_names ?? null,
            'submitted_by'     => $r->submitted_by_name,
            'created_at'       => $r->created_at,
            'effective_date'   => $r->effective_date,
            'review_url'       => admin_url('hr_module/policies/view/' . $r->policy_id),
        ];
    }
}

// The DataTable's own search box - rows here are built manually (below)
// instead of through the generic data_tables_init() helper, so its
// search[value] POST field has to be picked up and applied by hand.
$search_value = $CI->input->post('search');
if (!empty($search_value['value'])) {
    $needle = mb_strtolower(trim($search_value['value']));
    $rows   = array_values(array_filter($rows, function ($r) use ($needle) {
        return mb_strpos(mb_strtolower($r->title), $needle) !== false
            || mb_strpos(mb_strtolower((string) $r->submitted_by), $needle) !== false;
    }));
}

hr_module_apply_datatable_order($rows, [
    0 => 'title', 1 => 'kind', 2 => null, 3 => 'submitted_by', 4 => 'created_at',
]);

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

foreach ($rows as $r) {
    $kind_badge = $r->kind === 'new'
        ? '<span class="label label-info">New Policy</span>'
        : '<span class="label label-warning">Update</span>';
    $visibility = $r->type === 'public' ? 'Public' : ($r->department_names ? htmlspecialchars($r->department_names) : '-');

    $title_cell = htmlspecialchars($r->title);
    if ($CI->Policies_model->is_backdated($r->effective_date, $r->created_at)) {
        $title_cell .= ' <span class="label label-warning" title="Effective date is earlier than the submission date"><i class="fa fa-triangle-exclamation tw-mr-1"></i>Backdated</span>';
    }

    $output['aaData'][] = [
        $title_cell,
        $kind_badge,
        $visibility,
        htmlspecialchars($r->submitted_by ?: '-'),
        _dt($r->created_at),
        '<a href="' . $r->review_url . '" class="btn btn-default btn-xs">Review</a>',
    ];
}
