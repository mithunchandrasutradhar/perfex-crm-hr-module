<?php
defined('BASEPATH') or exit('No direct script access allowed');
$this->load->model('hr_module/Helpdesk_model');
$filters = array_filter([
    'employee_id'   => $this->input->get('employee_id'),
    'department_id' => $this->input->get('department_id'),
    'status'        => $this->input->get('status'),
    'priority'      => $this->input->get('priority'),
], function($v){ return $v !== null && $v !== ''; });

$rows  = $this->Helpdesk_model->get_for_table($filters);
$sbadge  = ['open'=>'danger','in_progress'=>'warning','resolved'=>'info','closed'=>'default'];
$pbadge  = ['low'=>'default','medium'=>'warning','high'=>'danger'];

foreach ($rows as $r) {
    $status   = '<span class="label label-'.($sbadge[$r->status] ?? 'default').'">'.ucfirst(str_replace('_',' ',$r->status)).'</span>';
    $priority = '<span class="label label-'.($pbadge[$r->priority] ?? 'default').'">'.ucfirst($r->priority).'</span>';
    $replies  = $r->reply_count > 0 ? '<span class="badge badge-secondary">'.$r->reply_count.'</span>' : '-';
    $actions  = '<a href="'.admin_url('hr_module/helpdesk/view/'.$r->id).'" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    if (staff_can('delete','hr_helpdesk'))
        $actions .= '<a href="'.admin_url('hr_module/helpdesk/delete/'.$r->id).'" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';

    echo '<tr>';
    echo '<td><a href="'.admin_url('hr_module/helpdesk/view/'.$r->id).'"><strong>#'.$r->id.'</strong> '.htmlspecialchars($r->subject).'</a></td>';
    echo '<td>'.htmlspecialchars($r->first_name.' '.$r->last_name).'<br><small class="text-muted">'.htmlspecialchars($r->department_name ?? '').'</small></td>';
    echo '<td>'.($r->category ? htmlspecialchars($r->category) : '-').'</td>';
    echo '<td>'.$priority.'</td>';
    echo '<td>'.$replies.'</td>';
    echo '<td>'.($r->assigned_name ? htmlspecialchars($r->assigned_name) : '<span class="text-muted">Unassigned</span>').'</td>';
    echo '<td>'.$status.'</td>';
    echo '<td>'.date('d M Y H:i', strtotime($r->created_at)).'</td>';
    echo '<td>'.$actions.'</td>';
    echo '</tr>';
}
