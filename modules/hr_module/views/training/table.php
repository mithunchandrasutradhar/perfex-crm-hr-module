<?php
defined('BASEPATH') or exit('No direct script access allowed');
$this->load->model('hr_module/Training_model');
$filters = array_filter([
    'status'    => $this->input->get('status'),
    'from_date' => $this->input->get('from_date'),
    'to_date'   => $this->input->get('to_date'),
], function($v){ return $v !== null && $v !== ''; });

$rows  = $this->Training_model->get_for_table($filters);
$badge = ['scheduled'=>'default','ongoing'=>'warning','completed'=>'success','cancelled'=>'danger'];

foreach ($rows as $r) {
    $status   = '<span class="label label-'.($badge[$r->status] ?? 'default').'">'.ucfirst($r->status).'</span>';
    $capacity = $r->capacity ? $r->enrolled_count.'/'.$r->capacity : $r->enrolled_count.' enrolled';
    $actions  = '<a href="'.admin_url('hr_module/training/view/'.$r->id).'" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    if (staff_can('edit','hr_training'))
        $actions .= '<a href="'.admin_url('hr_module/training/edit/'.$r->id).'" class="btn btn-default btn-xs"><i class="fa fa-pencil-alt"></i></a> ';
    if (staff_can('delete','hr_training'))
        $actions .= '<a href="'.admin_url('hr_module/training/delete/'.$r->id).'" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';

    echo '<tr>';
    echo '<td><a href="'.admin_url('hr_module/training/view/'.$r->id).'">'.htmlspecialchars($r->title).'</a></td>';
    echo '<td>'.($r->trainer ? htmlspecialchars($r->trainer) : '-').'</td>';
    echo '<td>'.($r->venue ? htmlspecialchars($r->venue) : '-').'</td>';
    echo '<td>'.date('d M Y', strtotime($r->start_date)).'</td>';
    echo '<td>'.date('d M Y', strtotime($r->end_date)).'</td>';
    echo '<td>'.number_format($r->cost, 2).'</td>';
    echo '<td>'.$capacity.'</td>';
    echo '<td>'.$status.'</td>';
    echo '<td>'.$actions.'</td>';
    echo '</tr>';
}
