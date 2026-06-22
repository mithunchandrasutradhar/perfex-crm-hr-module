<?php
defined('BASEPATH') or exit('No direct script access allowed');

$this->load->model('hr_module/Employees_model');
$dept_filter   = $this->input->get('department_id');
$status_filter = $this->input->get('status');
$filters       = [];
if ($dept_filter)              $filters['department_id'] = $dept_filter;
if ($status_filter !== null && $status_filter !== '') $filters['status'] = $status_filter;

$rows = $this->Employees_model->get_all_for_table($filters);

foreach ($rows as $emp) {
    $photo = $emp->photo
        ? '<img src="' . base_url('uploads/hr_module/employees/' . $emp->photo) . '" class="img-circle" width="32" height="32" style="object-fit:cover">'
        : '<span class="label-icon label-icon-info" style="width:32px;height:32px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#e2e8f0;color:#64748b;font-weight:700;">'
          . strtoupper(substr($emp->first_name, 0, 1)) . '</span>';

    $status_badge = $emp->status == 1
        ? '<span class="label label-success">' . _l('hr_active') . '</span>'
        : '<span class="label label-danger">' . _l('hr_inactive') . '</span>';

    $actions = '<a href="' . admin_url('hr_module/employees/view/' . $emp->id) . '" class="btn btn-default btn-xs" title="' . _l('hr_view') . '"><i class="fa fa-eye"></i></a> ';
    if (staff_can('edit', 'hr_employees')) {
        $actions .= '<a href="' . admin_url('hr_module/employees/edit/' . $emp->id) . '" class="btn btn-default btn-xs" title="' . _l('hr_edit') . '"><i class="fa fa-pencil-alt"></i></a> ';
    }
    if (staff_can('delete', 'hr_employees')) {
        $actions .= '<a href="' . admin_url('hr_module/employees/delete/' . $emp->id) . '" class="btn btn-danger btn-xs _delete" title="' . _l('hr_delete') . '"><i class="fa fa-times"></i></a>';
    }

    echo '<tr>';
    echo '<td>' . $photo . '</td>';
    echo '<td><a href="' . admin_url('hr_module/employees/view/' . $emp->id) . '">' . htmlspecialchars($emp->employee_code) . '</a></td>';
    echo '<td><a href="' . admin_url('hr_module/employees/view/' . $emp->id) . '">' . htmlspecialchars($emp->first_name . ' ' . $emp->last_name) . '</a></td>';
    echo '<td>' . ($emp->department_name ? htmlspecialchars($emp->department_name) : '-') . '</td>';
    echo '<td>' . ($emp->designation_name ? htmlspecialchars($emp->designation_name) : '-') . '</td>';
    echo '<td>' . ($emp->email ? '<a href="mailto:' . $emp->email . '">' . $emp->email . '</a>' : '-') . '</td>';
    echo '<td>' . ($emp->joining_date ? _d($emp->joining_date) : '-') . '</td>';
    echo '<td>' . $status_badge . '</td>';
    echo '<td>' . $actions . '</td>';
    echo '</tr>';
}
