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
        $this->load->model('hr_module/Holidays_model');
        $this->load->model('hr_module/Zkteco_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_attendance') && staff_cant('view_own', 'hr_attendance')) {
            access_denied('hr_attendance');
        }
        if ($this->input->is_ajax_request()) {
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
            $row = $this->Attendance_model->get($id);
            if (!$row) { echo json_encode(null); return; }
            // Native <input type="time"> always requires its value in 24-hour
            // "HH:MM" regardless of the app's display time format - the browser
            // renders it in the user's own locale/format automatically.
            echo json_encode([
                'id'              => $row->id,
                'employee_id'     => $row->employee_id,
                'attendance_date' => _d($row->attendance_date),
                'in_time'         => $row->in_time  ? date('H:i', strtotime($row->in_time))  : '',
                'out_time'        => $row->out_time ? date('H:i', strtotime($row->out_time)) : '',
                'status'          => $row->status,
                'notes'           => $row->notes,
            ]);
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
        $data['days_in_month'] = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $data['settings']   = $this->Hr_module_model->get_all_settings();
        $data['weekly_off']  = $this->Holidays_model->get_weekly_off_days();
        $data['holiday_map'] = $this->Holidays_model->get_holiday_names_in_range(
            sprintf('%04d-%02d-01', $year, $month),
            date('Y-m-t', mktime(0, 0, 0, $month, 1, $year))
        );
        $this->load->view('hr_module/attendance/monthly', $data);
    }

    public function report()
    {
        if (staff_cant('view', 'hr_attendance')) {
            access_denied('hr_attendance');
        }
        $filters = [
            'from_date'     => $this->input->get('from_date') ? to_sql_date($this->input->get('from_date')) : date('Y-m-01'),
            'to_date'       => $this->input->get('to_date')   ? to_sql_date($this->input->get('to_date'))   : date('Y-m-d'),
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

            // CI's Upload library rejects any extension not registered in
            // application/config/mimes.php (a core file) regardless of the
            // detect_mime setting, and .dat isn't in there - so this handles
            // the upload with plain PHP instead of the library.
            $orig_name = $_FILES['import_file']['name'] ?? '';
            $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

            if (empty($_FILES['import_file']['tmp_name']) || !in_array($ext, ['csv', 'dat', 'txt'], true)) {
                set_alert('danger', 'Please select a .csv, .dat, or .txt file.');
                redirect(admin_url('hr_module/attendance/import'));
            }
            if ($_FILES['import_file']['size'] > 2 * 1024 * 1024) {
                set_alert('danger', 'File exceeds the 2 MB size limit.');
                redirect(admin_url('hr_module/attendance/import'));
            }
            $file = $upload_path . uniqid('att_', true) . '.' . $ext;
            if (!is_uploaded_file($_FILES['import_file']['tmp_name']) || !move_uploaded_file($_FILES['import_file']['tmp_name'], $file)) {
                set_alert('danger', 'Upload failed.');
                redirect(admin_url('hr_module/attendance/import'));
            }

            if ($ext === 'dat' || $ext === 'txt') {
                $device_id = (int) $this->input->post('device_id');
                $parsed    = $this->_parse_attlog($file, $device_id);
                @unlink($file);
                $result = $this->Attendance_model->bulk_import($parsed['records']);
                $message = 'Imported: ' . $result['saved'] . ' day-records. Skipped (already existed): ' . $result['skipped'] . '.';
                if ($parsed['unmatched'] > 0) {
                    $message .= ' ' . $parsed['unmatched'] . ' punch(es) skipped - no employee mapping for that device user ID (set one up under ZKTeco > Employee Mapping).';
                }
                set_alert('success', $message);
            } else {
                $records = $this->_parse_csv($file);
                @unlink($file);
                $result = $this->Attendance_model->bulk_import($records);
                set_alert('success', 'Imported: ' . $result['saved'] . ' records. Skipped (duplicates): ' . $result['skipped']);
            }
            redirect(admin_url('hr_module/attendance'));
        }
        $data['title']   = _l('hr_attendance_import');
        $data['devices'] = $this->Zkteco_model->get_devices();
        $this->load->view('hr_module/attendance/import', $data);
    }

    private function _post_data()
    {
        $in  = $this->input->post('in_time');
        $out = $this->input->post('out_time');
        return [
            'employee_id'     => (int) $this->input->post('employee_id'),
            'attendance_date' => to_sql_date($this->input->post('attendance_date')),
            'in_time'         => $in  ? date('H:i:s', strtotime($in))  : null,
            'out_time'        => $out ? date('H:i:s', strtotime($out)) : null,
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

    // Parses the plain tab-separated attlog.dat export from a ZKTeco device's
    // own web portal (Download page): device_user_id, name, timestamp, verify
    // method, work code - one row per raw punch, no in/out flag. Groups punches
    // by employee+date and uses the earliest/latest punch of the day as
    // in_time/out_time, since a door-access device can log many punches a day
    // (official-work trips out and back) with no way to tell which is which.
    private function _parse_attlog($filepath, $device_id)
    {
        $punches   = [];
        $unmatched = 0;

        if (($fh = fopen($filepath, 'r')) !== false) {
            while (($line = fgets($fh)) !== false) {
                $line = rtrim($line, "\r\n");
                if ($line === '') continue;
                $cols = explode("\t", $line);
                if (count($cols) < 3) continue;

                $device_user_id = trim($cols[0]);
                $timestamp      = trim($cols[2]);
                if ($device_user_id === '' || !$timestamp) continue;

                $employee_id = $this->Zkteco_model->resolve_employee($device_id, $device_user_id);
                if (!$employee_id) {
                    $unmatched++;
                    continue;
                }

                $date = substr($timestamp, 0, 10);
                $punches[$employee_id][$date][] = $timestamp;
            }
            fclose($fh);
        }

        $records = [];
        foreach ($punches as $employee_id => $dates) {
            foreach ($dates as $date => $times) {
                sort($times);
                $records[] = [
                    'employee_id'     => $employee_id,
                    'attendance_date' => $date,
                    'in_time'         => date('H:i:s', strtotime($times[0])),
                    'out_time'        => count($times) > 1 ? date('H:i:s', strtotime(end($times))) : null,
                    'source'          => 'zkteco',
                ];
            }
        }

        return ['records' => $records, 'unmatched' => $unmatched];
    }
}
