<?php
defined('BASEPATH') or exit('No direct script access allowed');
$this->load->model('hr_module/Loans_model');
$filters = array_filter([
    'employee_id'   => $this->input->get('employee_id'),
    'department_id' => $this->input->get('department_id'),
    'status'        => $this->input->get('status'),
], function($v){ return $v !== null && $v !== ''; });

$rows = $this->Loans_model->get_for_table($filters);
$badge = ['pending'=>'default','approved'=>'warning','active'=>'info','rejected'=>'danger','closed'=>'success'];

foreach ($rows as $r) {
    $status = '<span class="label label-'.($badge[$r->status] ?? 'default').'">'.ucfirst($r->status).'</span>';
    $progress = '';
    if ($r->amount > 0) {
        $pct = min(100, round(($r->total_repaid / $r->amount) * 100));
        $progress = '<div class="progress tw-mb-0" style="height:6px;min-width:80px">
                       <div class="progress-bar progress-bar-success" style="width:'.$pct.'%"></div>
                     </div><small class="text-muted">'.$pct.'%</small>';
    }
    $actions = '<a href="'.admin_url('hr_module/loans/view/'.$r->id).'" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    if (staff_can('delete','hr_loans') && !in_array($r->status, ['active','closed']))
        $actions .= '<a href="'.admin_url('hr_module/loans/delete/'.$r->id).'" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';

    echo '<tr>';
    echo '<td><a href="'.admin_url('hr_module/loans/view/'.$r->id).'">'.htmlspecialchars($r->first_name.' '.$r->last_name).'</a><br><small class="text-muted">'.$r->employee_code.'</small></td>';
    echo '<td>'.($r->department_name ? htmlspecialchars($r->department_name) : '-').'</td>';
    echo '<td>'.number_format($r->amount, 2).'</td>';
    echo '<td>'.number_format($r->monthly_installment, 2).'</td>';
    echo '<td>'.number_format($r->outstanding, 2).'</td>';
    echo '<td>'.$progress.'</td>';
    echo '<td>'.$status.'</td>';
    echo '<td>'.($r->disbursement_date ? date('d M Y', strtotime($r->disbursement_date)) : '-').'</td>';
    echo '<td>'.$actions.'</td>';
    echo '</tr>';
}
