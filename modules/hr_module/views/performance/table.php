<?php
defined('BASEPATH') or exit('No direct script access allowed');
$this->load->model('hr_module/Performance_model');
$filters = array_filter([
    'employee_id'   => $this->input->get('employee_id'),
    'department_id' => $this->input->get('department_id'),
    'status'        => $this->input->get('status'),
    'year'          => $this->input->get('year'),
], function($v){ return $v !== null && $v !== ''; });

$rows  = $this->Performance_model->get_for_table($filters);
$badge = ['pending'=>'default','in_progress'=>'warning','completed'=>'success'];
$rating_color = ['Excellent'=>'success','Very Good'=>'info','Good'=>'primary','Average'=>'warning','Poor'=>'danger'];

foreach ($rows as $r) {
    $period  = date('d M Y', strtotime($r->review_period_from)) . ' &ndash; ' . date('d M Y', strtotime($r->review_period_to));
    $status  = '<span class="label label-'.($badge[$r->status] ?? 'default').'">'.ucfirst(str_replace('_',' ',$r->status)).'</span>';
    $score   = $r->final_score !== null ? $r->final_score.'%' : '-';
    $rating  = $r->rating ? '<span class="label label-'.($rating_color[$r->rating] ?? 'default').'">'.$r->rating.'</span>' : '-';
    $actions = '<a href="'.admin_url('hr_module/performance/view/'.$r->id).'" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    if (staff_can('edit','hr_performance'))
        $actions .= '<a href="'.admin_url('hr_module/performance/edit/'.$r->id).'" class="btn btn-default btn-xs"><i class="fa fa-pencil-alt"></i></a> ';
    if (staff_can('delete','hr_performance'))
        $actions .= '<a href="'.admin_url('hr_module/performance/delete/'.$r->id).'" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';

    echo '<tr>';
    echo '<td><a href="'.admin_url('hr_module/performance/view/'.$r->id).'">'.htmlspecialchars($r->first_name.' '.$r->last_name).'</a><br><small class="text-muted">'.$r->employee_code.'</small></td>';
    echo '<td>'.($r->department_name ? htmlspecialchars($r->department_name) : '-').'</td>';
    echo '<td>'.$period.'</td>';
    echo '<td>'.htmlspecialchars($r->reviewer_name ?? '-').'</td>';
    echo '<td>'.$score.'</td>';
    echo '<td>'.$rating.'</td>';
    echo '<td>'.$status.'</td>';
    echo '<td>'.$actions.'</td>';
    echo '</tr>';
}
