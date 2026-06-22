<?php
defined('BASEPATH') or exit('No direct script access allowed');

$this->load->model('hr_module/Departments_model');
$rows = $this->Departments_model->get();

foreach ($rows as $dept) {
    $head  = $this->Departments_model->get_head_name($dept->head_staff_id);
    $total = $this->Departments_model->total_employees($dept->id);
    $badge = $dept->status == 1
        ? '<span class="label label-success">' . _l('hr_active') . '</span>'
        : '<span class="label label-default">' . _l('hr_inactive') . '</span>';

    $parent = '-';
    if ($dept->parent_id) {
        $p = $this->Departments_model->get($dept->parent_id);
        if ($p) $parent = $p->name;
    }

    $actions = '';
    if (staff_can('edit', 'hr_departments')) {
        $actions .= '<a href="#" class="btn btn-default btn-xs hr-edit-dept" data-id="' . $dept->id . '" title="' . _l('hr_edit') . '"><i class="fa fa-pencil-alt"></i></a> ';
    }
    if (staff_can('delete', 'hr_departments')) {
        $actions .= '<a href="#" class="btn btn-danger btn-xs hr-delete-dept" data-id="' . $dept->id . '" data-name="' . htmlspecialchars($dept->name) . '" title="' . _l('hr_delete') . '"><i class="fa fa-times"></i></a>';
    }

    echo '<tr>';
    echo '<td>' . htmlspecialchars($dept->name) . '</td>';
    echo '<td>' . ($dept->code ? htmlspecialchars($dept->code) : '-') . '</td>';
    echo '<td>' . $parent . '</td>';
    echo '<td>' . $head . '</td>';
    echo '<td>' . $total . '</td>';
    echo '<td>' . $badge . '</td>';
    echo '<td>' . $actions . '</td>';
    echo '</tr>';
}
