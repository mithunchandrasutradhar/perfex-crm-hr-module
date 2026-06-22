<?php
defined('BASEPATH') or exit('No direct script access allowed');

$filters = [
    'employee_id'   => $this->input->get('employee_id'),
    'department_id' => $this->input->get('department_id'),
    'status'        => $this->input->get('status'),
    'contract_type' => $this->input->get('contract_type'),
    'expiring_soon' => $this->input->get('expiring_soon'),
];
$rows = $this->Hr_contracts_model->get_for_table($filters);

$type_badge = [
    'permanent'   => 'success',
    'fixed'       => 'info',
    'probation'   => 'warning',
    'internship'  => 'default',
    'casual'      => 'primary',
];
$status_badge = [
    'active'      => 'success',
    'expired'     => 'default',
    'terminated'  => 'danger',
    'pending'     => 'warning',
];

foreach ($rows as $r) {
    $expiry_warning = '';
    if ($r->end_date && $r->status === 'active') {
        $days_left = (strtotime($r->end_date) - time()) / 86400;
        if ($days_left >= 0 && $days_left <= 30) {
            $expiry_warning = ' <span class="label label-warning" title="Expiring soon">'.round($days_left).'d</span>';
        }
    }

    echo '<tr>';
    echo '<td><a href="'.admin_url('hr_module/hr_contracts/view/'.$r->id).'">'.htmlspecialchars($r->title).'</a></td>';
    echo '<td>'.htmlspecialchars($r->first_name.' '.$r->last_name).'<br><small class="text-muted">'.$r->employee_code.'</small></td>';
    echo '<td>'.htmlspecialchars($r->department_name ?? '-').'</td>';
    echo '<td><span class="label label-'.($type_badge[$r->contract_type] ?? 'default').'">'.ucfirst($r->contract_type).'</span></td>';
    echo '<td>'.date('d M Y', strtotime($r->start_date)).'</td>';
    echo '<td>'.($r->end_date ? date('d M Y', strtotime($r->end_date)).$expiry_warning : '<span class="text-muted">-</span>').'</td>';
    echo '<td>'.($r->value ? number_format($r->value, 2) : '-').'</td>';
    echo '<td><span class="label label-'.($status_badge[$r->status] ?? 'default').'">'.ucfirst($r->status).'</span></td>';
    echo '<td>'.($r->signed ? '<i class="fa fa-check-circle text-success"></i> '.($r->signed_date ? date('d M Y', strtotime($r->signed_date)) : 'Yes') : '<span class="text-muted">Unsigned</span>').'</td>';
    echo '<td>';
    echo '<a href="'.admin_url('hr_module/hr_contracts/view/'.$r->id).'" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    if (staff_can('edit','hr_contracts')) {
        echo '<a href="'.admin_url('hr_module/hr_contracts/edit/'.$r->id).'" class="btn btn-default btn-xs"><i class="fa fa-edit"></i></a> ';
    }
    if (staff_can('delete','hr_contracts')) {
        echo '<a href="'.admin_url('hr_module/hr_contracts/delete/'.$r->id).'" class="btn btn-default btn-xs _delete"><i class="fa fa-trash"></i></a>';
    }
    echo '</td>';
    echo '</tr>';
}
