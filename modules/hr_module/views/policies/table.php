<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Policies_model');
$CI->load->model('hr_module/Employees_model');

$is_global = is_admin() || staff_can('view', 'hr_policies');

$own_dept = null;
if (!$is_global) {
    $emp_id = hr_get_own_employee_id();
    $emp    = $emp_id ? $CI->Employees_model->get($emp_id) : null;
    $own_dept = $emp ? $emp->department_id : null;
}

$type_filter = $CI->input->get('type');
$dept_filter = $CI->input->get('department_id');

if ($is_global) {
    $filters = ['status' => 'published'];
    if ($dept_filter === 'public') {
        $filters['type'] = 'public';
    } elseif ($dept_filter !== null && $dept_filter !== '') {
        $filters['department_id'] = (int) $dept_filter;
    }
    if ($type_filter !== null && $type_filter !== '') {
        $filters['type'] = $type_filter;
    }
    $rows = $CI->Policies_model->get_all($filters);
} else {
    $rows = $CI->Policies_model->get_visible_for_department($own_dept);
    if ($type_filter !== null && $type_filter !== '') {
        $rows = array_values(array_filter($rows, function ($r) use ($type_filter) {
            return $r->type === $type_filter;
        }));
    }
}

// Same department-scoping rule as Policies::_can_manage_departments() - kept in
// sync manually since this file is included standalone, not via the controller.
$can_manage_any = $is_global || staff_can('create', 'hr_policies') || staff_can('edit', 'hr_policies');

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

foreach ($rows as $p) {
    $view_url   = admin_url('hr_module/policies/view/' . $p->id);
    $can_manage = $is_global || ($can_manage_any && $own_dept && in_array((int) $own_dept, $p->department_id_list, true));
    $atts       = $CI->Policies_model->decode_attachments($p->attachment);

    $title_cell = '<a href="' . $view_url . '">' . htmlspecialchars($p->title) . '</a>';
    $options    = ['<a href="' . $view_url . '">' . _l('hr_view') . '</a>'];
    if ($can_manage) {
        $options[] = '<a href="' . admin_url('hr_module/policies/edit/' . $p->id) . '">' . _l('hr_edit') . '</a>';
        $options[] = '<a href="' . admin_url('hr_module/policies/delete/' . $p->id) . '" class="_delete text-danger">' . _l('hr_delete') . '</a>';
    }
    $title_cell .= '<div class="row-options">' . implode(' | ', $options) . '</div>';

    $type_cell = $p->type === 'public'
        ? '<span class="label label-success">Public</span>'
        : '<span class="label label-default">Private</span>';

    $visibility_cell = $p->type === 'public' ? 'All Employees' : ($p->department_names ? htmlspecialchars($p->department_names) : '-');

    $content_cell = [];
    if (!empty($atts)) {
        $content_cell[] = '<i class="fa fa-file-pdf tw-mr-1"></i>PDF' . (count($atts) > 1 ? ' (' . count($atts) . ')' : '');
    }
    if ($p->content && trim(strip_tags($p->content)) !== '') {
        $content_cell[] = '<i class="fa fa-align-left tw-mr-1"></i>Text';
    }

    $row = [
        $title_cell,
        $type_cell,
        $visibility_cell,
        implode(' + ', $content_cell) ?: '-',
        $p->published_at ? date('d M Y', strtotime($p->published_at)) : '-',
    ];
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
