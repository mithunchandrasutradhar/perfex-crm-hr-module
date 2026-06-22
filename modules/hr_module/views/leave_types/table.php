<?php
defined('BASEPATH') or exit('No direct script access allowed');
$this->load->model('hr_module/Leave_model');
$rows = $this->Leave_model->get_type();
foreach ($rows as $t) {
    $badge   = $t->status == 1 ? '<span class="label label-success">'._l('hr_active').'</span>' : '<span class="label label-default">'._l('hr_inactive').'</span>';
    $actions = '';
    if (staff_can('edit', 'hr_leave'))
        $actions .= '<a href="#" class="btn btn-default btn-xs hr-edit-ltype" data-id="'.$t->id.'"><i class="fa fa-pencil-alt"></i></a> ';
    if (staff_can('delete', 'hr_leave'))
        $actions .= '<a href="#" class="btn btn-danger btn-xs hr-del-ltype" data-id="'.$t->id.'" data-name="'.htmlspecialchars($t->name).'"><i class="fa fa-times"></i></a>';
    echo '<tr>';
    echo '<td>'.htmlspecialchars($t->name).'</td>';
    echo '<td>'.$t->days_per_year.'</td>';
    echo '<td>'.($t->carry_forward ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-muted"></i>').'</td>';
    echo '<td>'.($t->requires_attachment ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-muted"></i>').'</td>';
    echo '<td>'.($t->allow_half_day ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-muted"></i>').'</td>';
    echo '<td>'.$badge.'</td>';
    echo '<td>'.$actions.'</td>';
    echo '</tr>';
}
