<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('hr_module/Departments_model');

$rows = $CI->Departments_model->get();

$output = [
    'draw'                 => intval($CI->input->post('draw')),
    'iTotalRecords'        => count($rows),
    'iTotalDisplayRecords' => count($rows),
    'aaData'               => [],
];

foreach ($rows as $dept) {
    $total = $CI->Departments_model->total_employees($dept->id);
    $output['aaData'][] = [
        htmlspecialchars($dept->name),
        $total,
    ];
}
