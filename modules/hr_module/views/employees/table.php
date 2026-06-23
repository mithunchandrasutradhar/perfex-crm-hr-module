<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$aColumns = [
    'e.id',                // [0] proxy for photo column (not sortable)
    'e.employee_code',     // [1] employee code
    "CONCAT(COALESCE(s.firstname, e.first_name), ' ', COALESCE(s.lastname, e.last_name)) as employee_name", // [2]
    'd.name as department_name',    // [3]
    'ds.name as designation_name',  // [4]
    "COALESCE(s.email, e.email) as email_col", // [5]
    'e.joining_date',      // [6]
    'e.status',            // [7]
];

$sIndexColumn = 'e.id';
$sTable       = db_prefix() . 'hr_employees e';

$join = [
    'LEFT JOIN ' . db_prefix() . 'departments d ON d.departmentid = e.department_id',
    'LEFT JOIN ' . db_prefix() . 'hr_designations ds ON ds.id = e.designation_id',
    'LEFT JOIN ' . db_prefix() . 'staff s ON s.staffid = e.staff_id',
];

$where = [];

$dept_filter   = $CI->input->get('department_id');
$status_filter = $CI->input->get('status');

if (!empty($dept_filter)) {
    $where[] = 'AND e.department_id = ' . (int) $dept_filter;
}
if ($status_filter !== null && $status_filter !== '') {
    $where[] = 'AND e.status = ' . (int) $status_filter;
}

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['e.photo', 'e.staff_id']);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // [0] Photo — use Perfex's staff_profile_image() when linked to a staff member,
    // fall back to HR-uploaded photo or initials avatar
    if (!empty($aRow['staff_id'])) {
        $row[] = staff_profile_image($aRow['staff_id'], ['img-circle'], 'small', ['width' => '32', 'height' => '32', 'style' => 'object-fit:cover']);
    } elseif (!empty($aRow['photo'])) {
        $row[] = '<img src="' . base_url('uploads/hr_module/employees/' . $aRow['photo']) . '" class="img-circle" width="32" height="32" style="object-fit:cover">';
    } else {
        $initial = strtoupper(substr($aRow['employee_name'] ?? '', 0, 1)) ?: '?';
        $row[] = '<span style="width:32px;height:32px;border-radius:50%;background:#dbeafe;color:#1d4ed8;font-weight:700;display:inline-flex;align-items:center;justify-content:center">' . $initial . '</span>';
    }

    // [1] Employee Code
    $row[] = '<a href="' . admin_url('hr_module/employees/view/' . $aRow['id']) . '">' . htmlspecialchars($aRow['employee_code']) . '</a>';

    // [2] Full Name
    $row[] = '<a href="' . admin_url('hr_module/employees/view/' . $aRow['id']) . '">' . htmlspecialchars($aRow['employee_name'] ?? '') . '</a>';

    // [3] Department
    $row[] = !empty($aRow['department_name']) ? htmlspecialchars($aRow['department_name']) : '-';

    // [4] Designation
    $row[] = !empty($aRow['designation_name']) ? htmlspecialchars($aRow['designation_name']) : '-';

    // [5] Email
    $email = $aRow['email_col'] ?? '';
    $row[] = $email ? '<a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a>' : '-';

    // [6] Joining Date
    $row[] = !empty($aRow['joining_date']) ? _d($aRow['joining_date']) : '-';

    // [7] Status badge
    $row[] = $aRow['status'] == 1
        ? '<span class="label label-success">' . _l('hr_active') . '</span>'
        : '<span class="label label-danger">' . _l('hr_inactive') . '</span>';

    // [8] Actions
    $actions = '<a href="' . admin_url('hr_module/employees/view/' . $aRow['id']) . '" class="btn btn-default btn-xs" title="' . _l('hr_view') . '"><i class="fa fa-eye"></i></a> ';
    if (staff_can('edit', 'hr_employees')) {
        $actions .= '<a href="' . admin_url('hr_module/employees/edit/' . $aRow['id']) . '" class="btn btn-default btn-xs" title="' . _l('hr_edit') . '"><i class="fa fa-pencil-alt"></i></a> ';
    }
    if (staff_can('delete', 'hr_employees')) {
        $actions .= '<a href="' . admin_url('hr_module/employees/delete/' . $aRow['id']) . '" class="btn btn-danger btn-xs _delete" title="' . _l('hr_delete') . '"><i class="fa fa-times"></i></a>';
    }
    $row[] = $actions;

    $output['aaData'][] = $row;
}
