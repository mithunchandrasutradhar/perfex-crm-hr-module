<?php
defined('BASEPATH') or exit('No direct script access allowed');
$this->load->model('hr_module/Overtime_model');
$filters = array_filter([
    'employee_id'   => $this->input->get('employee_id'),
    'department_id' => $this->input->get('department_id'),
    'status'        => $this->input->get('status'),
    'from_date'     => $this->input->get('from_date'),
    'to_date'       => $this->input->get('to_date'),
], function($v){ return $v !== null && $v !== ''; });

$rows  = $this->Overtime_model->get_for_table($filters);
$badge = ['pending'=>'default','approved'=>'success','rejected'=>'danger'];

foreach ($rows as $r) {
    $status  = '<span class="label label-'.($badge[$r->status] ?? 'default').'">'.ucfirst($r->status).'</span>';
    $actions = '<a href="'.admin_url('hr_module/overtime/view/'.$r->id).'" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    if (staff_can('edit',   'hr_overtime') && $r->status === 'pending')
        $actions .= '<a href="'.admin_url('hr_module/overtime/edit/'.$r->id).'" class="btn btn-default btn-xs"><i class="fa fa-pencil-alt"></i></a> ';
    if (staff_can('delete', 'hr_overtime') && $r->status !== 'approved')
        $actions .= '<a href="'.admin_url('hr_module/overtime/delete/'.$r->id).'" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';

    echo '<tr>';
    echo '<td><a href="'.admin_url('hr_module/employees/view/'.$r->id).'">'.htmlspecialchars($r->first_name.' '.$r->last_name).'</a><br><small class="text-muted">'.$r->employee_code.'</small></td>';
    echo '<td>'.($r->department_name ? htmlspecialchars($r->department_name) : '-').'</td>';
    echo '<td>'.date('D, d M Y', strtotime($r->overtime_date)).'</td>';
    echo '<td>'.$r->hours.' hrs</td>';
    echo '<td>'.$r->rate_multiplier.'x</td>';
    echo '<td>'.number_format($r->total_amount, 2).'</td>';
    echo '<td>'.$status.'</td>';
    echo '<td>'.$actions.'</td>';
    echo '</tr>';
}
