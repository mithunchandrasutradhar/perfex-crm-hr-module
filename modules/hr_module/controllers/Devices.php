<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Attendance device management (ZKTeco, AiFace/AI07F, and any future brand) -
// renamed from the original Zkteco-only controller once a second device
// brand was added, so the navigation, URL, and page titles aren't specific
// to one brand. The underlying permission capability ('hr_zkteco') and
// model (Zkteco_model) are kept as-is on purpose - renaming those would
// silently drop already-granted staff permissions and touch the live ADMS
// push path for no user-facing benefit.
class Devices extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Zkteco_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_zkteco')) access_denied('hr_zkteco');
        $data['title']   = _l('hr_zkteco_devices');
        $data['devices'] = $this->Zkteco_model->get_devices();
        $this->load->view('hr_module/devices/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_zkteco')) access_denied('hr_zkteco');
        if ($this->input->post()) {
            $result = $this->Zkteco_model->add_device($this->_post_data());
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
            redirect(admin_url('hr_module/devices'));
        }
        $data['title']  = _l('hr_zkteco_add_device');
        $data['device'] = null;
        $this->load->view('hr_module/devices/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_zkteco')) access_denied('hr_zkteco');
        $device = $this->Zkteco_model->get_device($id);
        if (!$device) show_404();
        if ($this->input->post()) {
            $result = $this->Zkteco_model->update_device($this->_post_data(), $id);
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
            redirect(admin_url('hr_module/devices'));
        }
        $data['title']  = _l('hr_zkteco_edit_device');
        $data['device'] = $device;
        $this->load->view('hr_module/devices/form', $data);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_zkteco')) access_denied('hr_zkteco');
        $result = $this->Zkteco_model->delete_device($id);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/devices'));
    }

    public function sync_logs()
    {
        if (staff_cant('view', 'hr_zkteco')) access_denied('hr_zkteco');
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'devices/sync_logs_table'));
            return;
        }
        $data['title']   = _l('hr_zkteco_sync_logs');
        $data['devices'] = $this->Zkteco_model->get_devices();
        $this->load->view('hr_module/devices/sync_logs', $data);
    }

    private function _post_data()
    {
        return [
            'name'          => $this->input->post('name', true),
            'device_type'   => $this->input->post('device_type', true) ?: 'zkteco',
            'ip_address'    => $this->input->post('ip_address'),
            'port'          => $this->input->post('port'),
            'serial_number' => $this->input->post('serial_number', true),
            'location'      => $this->input->post('location', true),
            'notes'         => $this->input->post('notes', true),
            'status'        => $this->input->post('status'),
        ];
    }
}
