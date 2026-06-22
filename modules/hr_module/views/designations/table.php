<?php
defined('BASEPATH') or exit('No direct script access allowed');

$this->load->model('hr_module/Designations_model');
$this->load->model('hr_module/Departments_model');
$rows = $this->Designations_model->get();

foreach ($rows as $row) {
    $dept  = '-';
    if ($row->department_id) {
        $d = $this->Departments_model->get($row->department_id);
        if ($d) $dept = htmlspecialchars($d->name);
    }
    $total = $this->Designations_model->total_employees($row->id);
    $badge = $row->status == 1
        ? '<span class="label label-success">' . _l('hr_active') . '</span>'
        : '<span class="label label-default">' . _l('hr_inactive') . '</span>';

    $actions = '';
    if (staff_can('edit', 'hr_departments')) {
        $actions .= '<a href="#" class="btn btn-default btn-xs hr-edit-desig" data-id="' . $row->id . '" title="' . _l('hr_edit') . '"><i class="fa fa-pencil-alt"></i></a> ';
    }
    if (staff_can('delete', 'hr_departments')) {
        $actions .= '<a href="#" class="btn btn-danger btn-xs hr-delete-desig" data-id="' . $row->id . '" data-name="' . htmlspecialchars($row->name) . '" title="' . _l('hr_delete') . '"><i class="fa fa-times"></i></a>';
    }

    echo '<tr>';
    echo '<td>' . htmlspecialchars($row->name) . '</td>';
    echo '<td>' . $dept . '</td>';
    echo '<td>' . $total . '</td>';
    echo '<td>' . $badge . '</td>';
    echo '<td>' . $actions . '</td>';
    echo '</tr>';
}
