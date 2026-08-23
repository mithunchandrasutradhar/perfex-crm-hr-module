<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Zkteco_model');

$device_id = $CI->input->get('device_id');
$logs      = $CI->Zkteco_model->get_logs($device_id ?: null, 200);

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($logs),
    'iTotalDisplayRecords' => count($logs),
    'aaData'               => [],
];

$status_badge = ['success' => 'success', 'failed' => 'danger', 'partial' => 'warning'];

foreach ($logs as $log) {
    $device_col = htmlspecialchars($log->device_name ?? 'Unknown');
    if (!empty($log->ip_address)) {
        $device_col .= '<br><small class="text-muted">' . htmlspecialchars($log->ip_address) . '</small>';
    }

    $status_col = '<span class="label label-' . ($status_badge[$log->status] ?? 'default') . '">' . ucfirst($log->status) . '</span>';

    $error_col = $log->error_message
        ? '<small class="text-danger">' . htmlspecialchars($log->error_message) . '</small>'
        : '<small class="text-muted">-</small>';

    $output['aaData'][] = [
        $device_col,
        date('d M Y H:i:s', strtotime($log->sync_at)),
        '<strong>' . number_format($log->records_fetched) . '</strong>',
        '<strong class="text-success">' . number_format($log->records_saved) . '</strong>',
        $status_col,
        $error_col,
    ];
}
