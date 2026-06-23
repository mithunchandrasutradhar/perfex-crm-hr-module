<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Attendance extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Attendance_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Employees_model');
        $this->load->model('hr_module/Departments_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_attendance') && staff_cant('view_own', 'hr_attendance')) {
            access_denied('hr_attendance');
        }
        if ($this->input->is_ajax_request() && !$this->input->post()) {
            $this->app->get_table_data(module_views_path('hr_module', 'attendance/table'));
            return;
        }
        $data['title']       = _l('hr_attendance_list');
        $data['departments'] = $this->Departments_model->get_active();
        $is_global = is_admin() || staff_can('view', 'hr_attendance');
        if ($is_global) {
            $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        } else {
            $emp_id = hr_get_own_employee_id();
            $emp    = $emp_id ? $this->Employees_model->get($emp_id) : null;
            $data['employees'] = $emp ? [$emp->id => $emp->employee_code . ' - ' . $emp->first_name . ' ' . $emp->last_name] : [];
        }
        $data['is_global'] = $is_global;
        $this->load->view('hr_module/attendance/index', $data);
    }

    public function add()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (staff_cant('create', 'hr_attendance')) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }
        $data = $this->_post_data();
        $result = $this->Attendance_model->add($data);
        echo json_encode($result);
    }

    public function edit($id)
    {
        if ($this->input->is_ajax_request() && !$this->input->post()) {
            echo json_encode($this->Attendance_model->get($id));
            return;
        }
        if (!$this->input->is_ajax_request()) show_404();
        if (staff_cant('edit', 'hr_attendance')) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }
        $data   = $this->_post_data();
        $result = $this->Attendance_model->update($data, $id);
        echo json_encode($result);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_attendance')) {
            access_denied('hr_attendance');
        }
        $this->Attendance_model->delete($id);
        set_alert('success', _l('hr_attendance_deleted'));
        redirect(admin_url('hr_module/attendance'));
    }

    public function monthly()
    {
        if (staff_cant('view', 'hr_attendance')) {
            access_denied('hr_attendance');
        }
        $month = $this->input->get('month') ?: date('n');
        $year  = $this->input->get('year')  ?: date('Y');
        $emp_id = $this->input->get('employee_id');

        $data['title']      = _l('hr_attendance_monthly');
        $data['month']      = (int) $month;
        $data['year']       = (int) $year;
        $data['employees']  = $this->Hr_module_model->get_active_employees_dropdown();
        $data['employee_id'] = $emp_id;
        $data['records']    = $emp_id ? $this->Attendance_model->get_monthly($emp_id, $month, $year) : [];
        $data['summary']    = $emp_id ? $this->Attendance_model->get_summary($emp_id, $month, $year) : [];
        $data['days_in_month'] = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $data['settings']   = $this->Hr_module_model->get_all_settings();
        $this->load->view('hr_module/attendance/monthly', $data);
    }

    public function report()
    {
        if (staff_cant('view', 'hr_attendance')) {
            access_denied('hr_attendance');
        }
        $filters = [
            'from_date'     => $this->input->get('from_date') ?: date('Y-m-01'),
            'to_date'       => $this->input->get('to_date')   ?: date('Y-m-d'),
            'department_id' => $this->input->get('department_id'),
            'employee_id'   => $this->input->get('employee_id'),
            'status'        => $this->input->get('status'),
        ];
        $data['title']       = _l('hr_attendance_report');
        $data['filters']     = $filters;
        $data['records']     = $this->Attendance_model->get_for_table(array_filter($filters));
        $data['departments'] = $this->Departments_model->get_active();
        $data['employees']   = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/attendance/report', $data);
    }

    public function import()
    {
        if (staff_cant('create', 'hr_attendance')) {
            access_denied('hr_attendance');
        }
        if ($this->input->get('download_template')) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="attendance_import_template.csv"');
            echo "employee_code,date,in_time,out_time,status\n";
            echo "EMP0001,2026-06-01,09:00,18:00,present\n";
            echo "EMP0002,2026-06-01,09:45,18:00,late\n";
            echo "EMP0003,2026-06-01,,,absent\n";
            echo "EMP0004,2026-06-01,09:00,13:00,half_day\n";
            exit;
        }
        if ($this->input->post()) {
            $upload_path = FCPATH . 'uploads/hr_module/temp/';
            if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);
            $this->load->library('upload', [
                'upload_path'   => $upload_path,
                'allowed_types' => 'csv',
                'max_size'      => 2048,
                'encrypt_name'  => true,
            ]);
            if (!$this->upload->do_upload('csv_file')) {
                set_alert('danger', $this->upload->display_errors('', ''));
                redirect(admin_url('hr_module/attendance/import'));
            }
            $file    = $this->upload->data('full_path');
            $records = $this->_parse_csv($file);
            @unlink($file);
            $result = $this->Attendance_model->bulk_import($records);
            set_alert('success', 'Imported: ' . $result['saved'] . ' records. Skipped (duplicates): ' . $result['skipped']);
            redirect(admin_url('hr_module/attendance'));
        }
        $data['title'] = _l('hr_attendance_import');
        $this->load->view('hr_module/attendance/import', $data);
    }

    private function _post_data()
    {
        return [
            'employee_id'     => (int) $this->input->post('employee_id'),
            'attendance_date' => $this->input->post('attendance_date'),
            'in_time'         => $this->input->post('in_time') ?: null,
            'out_time'        => $this->input->post('out_time') ?: null,
            'status'          => $this->input->post('status'),
            'source'          => 'manual',
            'notes'           => $this->input->post('notes', true),
        ];
    }

    private function _parse_csv($filepath)
    {
        $records = [];
        if (($fh = fopen($filepath, 'r')) === false) return $records;
        $header = fgetcsv($fh); // skip header row
        while (($row = fgetcsv($fh)) !== false) {
            if (count($row) < 3) continue;
            // Expected: employee_code, date (Y-m-d), in_time, out_time, status
            $emp = $this->db->where('employee_code', trim($row[0]))->get(db_prefix() . 'hr_employees')->row();
            if (!$emp) continue;
            $records[] = [
                'employee_id'     => $emp->id,
                'attendance_date' => trim($row[1]),
                'in_time'         => !empty($row[2]) ? trim($row[2]) : null,
                'out_time'        => !empty($row[3]) ? trim($row[3]) : null,
                'status'          => !empty($row[4]) ? trim($row[4]) : 'present',
                'source'          => 'manual',
            ];
        }
        fclose($fh);
        return $records;
    }
}
