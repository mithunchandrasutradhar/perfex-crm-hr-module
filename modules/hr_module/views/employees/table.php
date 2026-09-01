<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$aColumns = [
    "CONCAT(COALESCE(s.firstname, e.first_name), ' ', COALESCE(s.lastname, e.last_name)) as employee_name", // [0]
    'd.name as department_name',    // [1]
    'ds.name as designation_name',  // [2]
    "COALESCE(s.phonenumber, e.phone) as phone_col", // [3]
    'e.personal_email as personal_email_col', // [4]
    's.active as staff_active', // [5] employee status mirrors the linked staff account's status
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
    $where[] = 'AND s.active = ' . (int) $status_filter;
}
if (!staff_can('view', 'hr_employees') && staff_can('view_own', 'hr_employees')) {
    $where[] = 'AND e.staff_id = ' . (int) get_staff_user_id();
}

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['e.photo', 'e.staff_id', 'e.id']);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // [0] Full Name, with the standard Perfex row-options action links underneath
    $name  = '<a href="' . admin_url('hr_module/employees/view/' . $aRow['id']) . '">' . htmlspecialchars($aRow['employee_name'] ?? '') . '</a>';
    $name .= '<div class="row-options">';
    $name .= '<a href="' . admin_url('hr_module/employees/view/' . $aRow['id']) . '">' . _l('hr_view') . '</a>';
    if (staff_can('edit', 'hr_employees')) {
        $name .= ' | <a href="' . admin_url('hr_module/employees/edit/' . $aRow['id']) . '">' . _l('hr_edit') . '</a>';
    }
    if (staff_can('delete', 'hr_employees')) {
        $name .= ' | <a href="' . admin_url('hr_module/employees/delete/' . $aRow['id']) . '" class="_delete text-danger">' . _l('hr_deactivate') . '</a>';
    }
    $name .= '</div>';
    $row[] = $name;

    // [1] Department
    $row[] = !empty($aRow['department_name']) ? htmlspecialchars($aRow['department_name']) : '-';

    // [2] Designation
    $row[] = !empty($aRow['designation_name']) ? htmlspecialchars($aRow['designation_name']) : '-';

    // [3] Phone
    $phone = $aRow['phone_col'] ?? '';
    $row[] = $phone ? htmlspecialchars($phone) : '-';

    // [4] Personal Email
    $personal_email = $aRow['personal_email_col'] ?? '';
    $row[] = $personal_email ? '<a href="mailto:' . htmlspecialchars($personal_email) . '">' . htmlspecialchars($personal_email) . '</a>' : '-';

    // [5] Status badge - mirrors the linked staff account's active status
    $row[] = $aRow['staff_active'] == 1
        ? '<span class="label label-success">' . _l('hr_active') . '</span>'
        : '<span class="label label-danger">' . _l('hr_inactive') . '</span>';

    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}
