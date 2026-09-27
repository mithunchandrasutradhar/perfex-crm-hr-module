<?php
defined('BASEPATH') or exit('No direct script access allowed');
// TEMPORARY test helper (Phase 6 test) - deleted by the test script.
class Zz_test_hook extends AdminController
{
    public function run($what)
    {
        if (! is_admin()) { die('no'); }
        if ($what === 'schedules') {
            $this->load->model(AMS_MODULE_NAME . '/ams_maintenance_model');
            echo json_encode(['n' => $this->ams_maintenance_model->process_due_schedules()]);
        } elseif ($what === 'licenses') {
            $this->load->model(AMS_MODULE_NAME . '/ams_license_model');
            echo json_encode(['n' => $this->ams_license_model->send_expiry_reminders()]);
        }
    }
}
