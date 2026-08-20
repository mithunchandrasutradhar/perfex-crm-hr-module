<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Zkteco extends AdminController
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
        $this->load->view('hr_module/zkteco/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_zkteco')) access_denied('hr_zkteco');
        if ($this->input->post()) {
            $result = $this->Zkteco_model->add_device($this->_post_data());
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
            redirect(admin_url('hr_module/zkteco'));
        }
        $data['title']  = _l('hr_zkteco_add_device');
        $data['device'] = null;
        $this->load->view('hr_module/zkteco/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_zkteco')) access_denied('hr_zkteco');
        $device = $this->Zkteco_model->get_device($id);
        if (!$device) show_404();
        if ($this->input->post()) {
            $result = $this->Zkteco_model->update_device($this->_post_data(), $id);
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
            redirect(admin_url('hr_module/zkteco'));
        }
        $data['title']  = _l('hr_zkteco_edit_device');
        $data['device'] = $device;
        $this->load->view('hr_module/zkteco/form', $data);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_zkteco')) access_denied('hr_zkteco');
        $result = $this->Zkteco_model->delete_device($id);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/zkteco'));
    }

    public function sync_logs()
    {
        if (staff_cant('view', 'hr_zkteco')) access_denied('hr_zkteco');
        $device_id = $this->input->get('device_id');
        $data['title']   = _l('hr_zkteco_sync_logs');
        $data['logs']    = $this->Zkteco_model->get_logs($device_id, 200);
        $data['devices'] = $this->Zkteco_model->get_devices();
        $data['current_device'] = $device_id;
        $this->load->view('hr_module/zkteco/sync_logs', $data);
    }

    private function _post_data()
    {
        return [
            'name'          => $this->input->post('name', true),
            'ip_address'    => $this->input->post('ip_address'),
            'port'          => $this->input->post('port'),
            'serial_number' => $this->input->post('serial_number', true),
            'location'      => $this->input->post('location', true),
            'notes'         => $this->input->post('notes', true),
            'status'        => $this->input->post('status'),
        ];
    }
}
