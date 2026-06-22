<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Zkteco extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Zkteco_model');
        $this->load->model('hr_module/Hr_module_model');
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

    public function test_connection($id)
    {
        if (staff_cant('view', 'hr_zkteco')) access_denied('hr_zkteco');
        $device = $this->Zkteco_model->get_device($id);
        if (!$device) {
            echo json_encode(['success' => false, 'message' => 'Device not found.']);
            return;
        }
        $result = $this->Zkteco_model->test_connection($device->ip_address, $device->port);
        echo json_encode($result);
    }

    public function sync($id)
    {
        if (staff_cant('edit', 'hr_zkteco')) access_denied('hr_zkteco');
        $result = $this->Zkteco_model->sync($id);
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
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

    public function mapping()
    {
        if (staff_cant('edit', 'hr_zkteco')) access_denied('hr_zkteco');
        if ($this->input->post()) {
            $employee_id    = $this->input->post('employee_id');
            $device_id      = $this->input->post('device_id');
            $device_user_id = $this->input->post('device_user_id');
            if ($employee_id && $device_id && $device_user_id !== '') {
                $result = $this->Zkteco_model->save_mapping($employee_id, $device_id, $device_user_id);
                if ($this->input->is_ajax_request()) {
                    echo json_encode($result);
                    return;
                }
                set_alert('success', $result['message']);
            }
            redirect(admin_url('hr_module/zkteco/mapping'));
        }
        $data['title']     = _l('hr_zkteco_mapping');
        $data['mappings']  = $this->Zkteco_model->get_mappings();
        $data['devices']   = $this->Zkteco_model->get_devices();
        $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/zkteco/mapping', $data);
    }

    public function delete_mapping($id)
    {
        if (staff_cant('edit', 'hr_zkteco')) access_denied('hr_zkteco');
        $this->Zkteco_model->delete_mapping($id);
        set_alert('success', 'Mapping removed.');
        redirect(admin_url('hr_module/zkteco/mapping'));
    }

    private function _post_data()
    {
        return [
            'name'       => $this->input->post('name', true),
            'ip_address' => $this->input->post('ip_address'),
            'port'       => $this->input->post('port'),
            'location'   => $this->input->post('location', true),
            'notes'      => $this->input->post('notes', true),
            'status'     => $this->input->post('status'),
        ];
    }
}
