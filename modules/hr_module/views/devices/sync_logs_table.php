<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Zkteco_model');

$device_id = $CI->input->get('device_id');
// No practical row cap here (unlike this method's 100-row default elsewhere)
// so iTotalRecords below reflects the true total - pagination is applied
// after fetching, the same way views/branches/table.php does it.
$logs = $CI->Zkteco_model->get_logs($device_id ?: null, 1000000);

// The DataTable's own column-header sort (the client sends order[column,dir]
// on every AJAX request) - server-side since rows here are built manually,
// not through the generic data_tables_init() helper.
hr_module_apply_datatable_order($logs, [
    0 => 'device_name',
    1 => 'sync_at',
    2 => 'records_fetched',
    3 => 'records_saved',
    4 => 'status',
    5 => 'error_message',
]);

$total_records = count($logs);
$dt_start      = (int) $CI->input->post('start');
$dt_length     = (int) $CI->input->post('length');
$paged_logs    = $dt_length > 0 ? array_slice($logs, $dt_start, $dt_length) : $logs;

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => $total_records,
    'iTotalDisplayRecords' => $total_records,
    'aaData'               => [],
];

$status_badge = ['success' => 'success', 'failed' => 'danger', 'partial' => 'warning'];

foreach ($paged_logs as $log) {
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
