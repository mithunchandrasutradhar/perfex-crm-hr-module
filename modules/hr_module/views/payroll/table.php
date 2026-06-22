<?php
defined('BASEPATH') or exit('No direct script access allowed');
$this->load->model('hr_module/Payroll_model');
$filters = array_filter([
    'employee_id'   => $this->input->get('employee_id'),
    'department_id' => $this->input->get('department_id'),
    'pay_month'     => $this->input->get('pay_month'),
    'pay_year'      => $this->input->get('pay_year'),
    'status'        => $this->input->get('status'),
], function($v){ return $v !== null && $v !== ''; });

$rows  = $this->Payroll_model->get_for_table($filters);
$badge = ['draft'=>'default','approved'=>'warning','paid'=>'success'];

foreach ($rows as $r) {
    $status = '<span class="label label-'.($badge[$r->status] ?? 'default').'">'.ucfirst($r->status).'</span>';
    $period = date('F', mktime(0,0,0,$r->pay_month,1)) . ' ' . $r->pay_year;
    $actions  = '<a href="'.admin_url('hr_module/payroll/view/'.$r->id).'" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a> ';
    $actions .= '<a href="'.admin_url('hr_module/payroll/slip/'.$r->id).'" class="btn btn-info btn-xs" target="_blank"><i class="fa fa-print"></i></a> ';
    if (staff_can('delete','hr_payroll') && $r->status !== 'paid')
        $actions .= '<a href="'.admin_url('hr_module/payroll/delete/'.$r->id).'" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';

    echo '<tr>';
    echo '<td>'.htmlspecialchars($r->first_name.' '.$r->last_name).'<br><small class="text-muted">'.$r->employee_code.'</small></td>';
    echo '<td>'.($r->department_name ? htmlspecialchars($r->department_name) : '-').'</td>';
    echo '<td>'.$period.'</td>';
    echo '<td>'.number_format($r->basic_salary,2).'</td>';
    echo '<td>'.number_format($r->gross_salary,2).'</td>';
    echo '<td>'.number_format($r->net_salary,2).'</td>';
    echo '<td>'.$status.'</td>';
    echo '<td>'.($r->payment_date ? date('d M Y', strtotime($r->payment_date)) : '-').'</td>';
    echo '<td>'.$actions.'</td>';
    echo '</tr>';
}
