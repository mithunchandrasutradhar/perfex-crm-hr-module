<?php
defined('BASEPATH') or exit('No direct script access allowed');
$this->load->model('hr_module/Leave_model');
$filters = [
    'status'        => $this->input->get('status'),
    'leave_type_id' => $this->input->get('leave_type_id'),
    'employee_id'   => $this->input->get('employee_id'),
];
$rows = $this->Leave_model->get_request(null, array_filter($filters, function($v){ return $v !== null && $v !== ''; }));

$badge_map = [
    'pending'   => 'label-warning',
    'approved'  => 'label-success',
    'rejected'  => 'label-danger',
    'cancelled' => 'label-default',
];

foreach ($rows as $r) {
    $badge = '<span class="label ' . ($badge_map[$r->status] ?? 'label-default') . '">' . ucfirst($r->status) . '</span>';
    $actions = '<a href="' . admin_url('hr_module/leave/view/' . $r->id) . '" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a>';
    if (staff_can('delete', 'hr_leave') && in_array($r->status, ['rejected','cancelled'])) {
        $actions .= ' <a href="' . admin_url('hr_module/leave/delete/' . $r->id) . '" class="btn btn-danger btn-xs _delete"><i class="fa fa-times"></i></a>';
    }
    echo '<tr>';
    echo '<td>' . $r->id . '</td>';
    echo '<td><a href="' . admin_url('hr_module/employees/view/' . $r->employee_id) . '">' . htmlspecialchars($r->employee_name) . '</a><br><small class="text-muted">' . $r->employee_code . '</small></td>';
    echo '<td>' . htmlspecialchars($r->leave_type_name) . '</td>';
    echo '<td>' . _d($r->from_date) . '</td>';
    echo '<td>' . _d($r->to_date) . '</td>';
    echo '<td>' . $r->total_days . ($r->is_half_day ? ' <span class="label label-info">Half</span>' : '') . '</td>';
    echo '<td>' . $badge . '</td>';
    echo '<td>' . _d($r->created_at) . '</td>';
    echo '<td>' . $actions . '</td>';
    echo '</tr>';
}
