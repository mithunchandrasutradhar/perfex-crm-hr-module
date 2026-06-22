<?php
defined('BASEPATH') or exit('No direct script access allowed');
$this->load->model('hr_module/Attendance_model');
$filters = array_filter([
    'department_id' => $this->input->get('department_id'),
    'employee_id'   => $this->input->get('employee_id'),
    'status'        => $this->input->get('status'),
    'from_date'     => $this->input->get('from_date'),
    'to_date'       => $this->input->get('to_date'),
], function($v){ return $v !== null && $v !== ''; });

$rows = $this->Attendance_model->get_for_table($filters);

$badge = ['present'=>'success','late'=>'warning','absent'=>'danger','half_day'=>'info'];
foreach ($rows as $r) {
    $status_badge = '<span class="label label-'.($badge[$r->status] ?? 'default').'">'.ucfirst(str_replace('_',' ',$r->status)).'</span>';
    $source_icon  = $r->source === 'zkteco' ? '<i class="fa fa-fingerprint text-info" title="ZKTeco"></i>' : '<i class="fa fa-keyboard text-muted" title="Manual"></i>';
    $actions = '';
    if (staff_can('edit',   'hr_attendance')) $actions .= '<a href="#" class="btn btn-default btn-xs hr-edit-att" data-id="'.$r->id.'"><i class="fa fa-pencil-alt"></i></a> ';
    if (staff_can('delete', 'hr_attendance')) $actions .= '<a href="'.admin_url('hr_module/attendance/delete/'.$r->id).'" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';

    echo '<tr>';
    echo '<td><a href="'.admin_url('hr_module/employees/view/'.$r->employee_id).'">'.htmlspecialchars($r->employee_name).'</a><br><small class="text-muted">'.$r->employee_code.'</small></td>';
    echo '<td>'.($r->department_name ? htmlspecialchars($r->department_name) : '-').'</td>';
    echo '<td>'.date('D, d M Y', strtotime($r->attendance_date)).'</td>';
    echo '<td>'.($r->in_time  ? substr($r->in_time, 0, 5) : '-').'</td>';
    echo '<td>'.($r->out_time ? substr($r->out_time, 0, 5) : '-').'</td>';
    echo '<td>'.($r->working_hours ? $r->working_hours.' h' : '-').'</td>';
    echo '<td>'.$status_badge.'</td>';
    echo '<td>'.$source_icon.'</td>';
    echo '<td>'.$actions.'</td>';
    echo '</tr>';
}
