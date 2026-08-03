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
        // Not $this->input->post() - the only POST field on this form besides
        // the file is the CSRF token, which CI strips from $_POST once it
        // verifies it, so an empty-array truthiness check here always fails.
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $upload_path = FCPATH . 'uploads/hr_module/temp/';
            if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);

            // CI's Upload library rejects any extension not registered in
            // application/config/mimes.php (a core file) regardless of the
            // detect_mime setting, and .dat isn't in there - so this handles
            // the upload with plain PHP instead of the library.
            $orig_name = $_FILES['import_file']['name'] ?? '';
            $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

            if (empty($_FILES['import_file']['tmp_name']) || !in_array($ext, ['csv', 'dat', 'txt', 'xlsx'], true)) {
                set_alert('danger', 'Please select a .csv, .xlsx, .dat, or .txt file.');
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

            try {
                $this->_process_import_file($file, $ext);
            } catch (\Throwable $e) {
                @unlink($file);
                set_alert('danger', 'Import failed: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
                redirect(admin_url('hr_module/attendance/import'));
            }
            redirect(admin_url('hr_module/attendance'));
        }
        $data['title'] = _l('hr_attendance_import');
        $this->load->view('hr_module/attendance/import', $data);
    }

    // A blank 500 page tells the user nothing actionable - this catches
    // anything unexpected during parsing/import and surfaces the real error
    // message instead, without changing what a successful import does.
    private function _process_import_file($file, $ext)
    {
        if ($ext === 'dat' || $ext === 'txt') {
            $parsed = $this->_parse_attlog($file);
            @unlink($file);
            $this->_import_and_report($parsed['records'], $parsed['unmatched'], 'punch(es)');
        } elseif ($ext === 'xlsx') {
            $rows = $this->_read_xlsx_rows($file);
            @unlink($file);
            $parsed = $this->_parse_attendance_report_rows($rows);
            if (isset($parsed['error'])) {
                set_alert('danger', $parsed['error']);
            } else {
                $this->_import_and_report($parsed['records'], $parsed['unmatched'], 'row(s)');
            }
        } else {
            // Same .csv extension covers two different layouts: the simple
            // manual template, and a ZKTeco "Attendance Record Report"
            // re-saved as CSV from Excel/LibreOffice - detected by header.
            $fh     = fopen($file, 'r');
            $header = $fh ? fgetcsv($fh) : false;
            if ($fh) fclose($fh);
            $is_report = strpos(strtolower(implode(',', $header ?: [])), 'clock in') !== false;

            if ($is_report) {
                $rows = $this->_read_csv_rows($file);
                @unlink($file);
                $parsed = $this->_parse_attendance_report_rows($rows);
                if (isset($parsed['error'])) {
                    set_alert('danger', $parsed['error']);
                } else {
                    $this->_import_and_report($parsed['records'], $parsed['unmatched'], 'row(s)');
                }
            } else {
                $records = $this->_parse_csv($file);
                @unlink($file);
                $result = $this->Attendance_model->bulk_import($records);
                set_alert('success', 'Imported: ' . $result['saved'] . ' records. Updated with merged data: ' . $result['merged'] . '. Skipped (no new info): ' . $result['skipped']);
            }
        }
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

    // Every employee has one number (the numeric part of their employee_code,
    // e.g. EMP0004 -> 4) that's set up to match their ID on every physical
    // ZKTeco device/software export - maintained manually when adding/editing
    // an employee. This is what lets a file from ANY device import without
    // picking which device it came from: whatever Emp No./AC-No./device user
    // ID a file uses, it's looked up against this same map.
    private function _build_employee_code_map()
    {
        $map = [];
        $employees = $this->db->select('id, employee_code')->get(db_prefix() . 'hr_employees')->result();
        foreach ($employees as $e) {
            $numeric = preg_replace('/[^0-9]/', '', (string) $e->employee_code);
            if ($numeric !== '') {
                $map[(int) $numeric] = $e->id;
            }
        }
        return $map;
    }

    // Parses the plain tab-separated attlog.dat export from a ZKTeco device's
    // own web portal (Download page): device_user_id, name, timestamp, verify
    // method, work code - one row per raw punch, no in/out flag. Groups punches
    // by employee+date and uses the earliest/latest punch of the day as
    // in_time/out_time, since a door-access device can log many punches a day
    // (official-work trips out and back) with no way to tell which is which.
    private function _parse_attlog($filepath)
    {
        $codeMap   = $this->_build_employee_code_map();
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
                if ($device_user_id === '' || !$timestamp || !ctype_digit($device_user_id)) continue;

                $employee_id = $codeMap[(int) $device_user_id] ?? null;
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

    private function _import_and_report($records, $unmatched, $unmatchedLabel = 'row(s)')
    {
        $result  = $this->Attendance_model->bulk_import($records);
        $message = 'Imported: ' . $result['saved'] . ' day-records. Updated with merged data: ' . $result['merged'] . '. Skipped (no new info): ' . $result['skipped'] . '.';
        if ($unmatched > 0) {
            $message .= ' ' . $unmatched . ' ' . $unmatchedLabel . ' skipped - no employee has a matching Employee Code for that ID.';
        }
        set_alert('success', $message);
    }

    private function _read_csv_rows($filepath)
    {
        $rows = [];
        if (($fh = fopen($filepath, 'r')) !== false) {
            while (($row = fgetcsv($fh)) !== false) {
                $rows[] = $row;
            }
            fclose($fh);
        }
        return $rows;
    }

    private function _read_xlsx_rows($filepath)
    {
        require_once FCPATH . 'modules/surveys/vendor/autoload.php';
        $rows   = [];
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $sheet  = $reader->load($filepath)->getActiveSheet();
        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = $cell->getFormattedValue();
            }
            $rows[] = $cells;
        }
        return $rows;
    }

    // strtotime() silently returns false on a malformed time string (e.g. a
    // spreadsheet-mangled "18:39PM" mixing 24-hour and AM/PM notation) - and
    // date() then coerces that false to 0, i.e. midnight, rather than raising
    // any error. That midnight would silently look like a real punch to the
    // merge logic. Returning null instead means it's correctly treated as no
    // usable value at all.
    private function _parse_time($value)
    {
        $ts = strtotime($value);
        return $ts !== false ? date('H:i:s', $ts) : null;
    }

    // Parses a ZKTeco "Attendance Record Report" - re-saved as a clean .csv or
    // .xlsx from Excel/LibreOffice, since the device software's own .xls export
    // uses a non-standard binary layout no library can parse reliably. Looks
    // columns up by header name (not position), since blank cells on
    // absent/incomplete-punch days shift raw column positions around. Unlike
    // attlog.dat, this report has one row per employee per day for the whole
    // month, including explicitly-flagged absent days.
    private function _parse_attendance_report_rows($rows)
    {
        if (empty($rows)) {
            return ['records' => [], 'unmatched' => 0];
        }

        $codeMap = $this->_build_employee_code_map();

        $header = array_map(function ($h) { return strtolower(trim((string) $h)); }, $rows[0]);
        $col    = array_flip($header);

        $empCol     = $col['emp no.'] ?? $col['ac-no.'] ?? null;
        $dateCol    = $col['date'] ?? null;
        $inCol      = $col['clock in'] ?? null;
        $outCol     = $col['clock out'] ?? null;
        $absentCol  = $col['absent'] ?? null;
        $weekendCol = $col['weekend'] ?? null;
        $holidayCol = $col['holiday'] ?? null;

        if ($empCol === null || $dateCol === null) {
            return ['records' => [], 'unmatched' => 0, 'error' => 'Unrecognised report format - could not find the Emp No./Date columns.'];
        }

        $records   = [];
        $unmatched = 0;

        for ($i = 1, $n = count($rows); $i < $n; $i++) {
            $row            = $rows[$i];
            $device_user_id = trim((string) ($row[$empCol] ?? ''));
            $date_raw       = trim((string) ($row[$dateCol] ?? ''));
            if ($device_user_id === '' || $date_raw === '') continue;

            $ts = strtotime($date_raw);
            if ($ts === false) continue;
            $date = date('Y-m-d', $ts);

            $employee_id = ctype_digit($device_user_id) ? ($codeMap[(int) $device_user_id] ?? null) : null;
            if (!$employee_id) {
                $unmatched++;
                continue;
            }

            $clock_in  = $inCol  !== null ? trim((string) ($row[$inCol]  ?? '')) : '';
            $clock_out = $outCol !== null ? trim((string) ($row[$outCol] ?? '')) : '';
            $absent    = $absentCol !== null ? strtolower(trim((string) ($row[$absentCol] ?? ''))) : '';
            $is_absent = in_array($absent, ['true', '1', 'yes'], true) || ($clock_in === '' && $clock_out === '');

            // A weekly-off/holiday day with no punches isn't an absence - no
            // one was expected to clock in, so no attendance record is
            // fabricated for it at all (matches how a real device export has
            // no punch rows for a day nobody was scheduled to work).
            if ($clock_in === '' && $clock_out === '' && !in_array($absent, ['true', '1', 'yes'], true)) {
                $weekend = $weekendCol !== null ? strtolower(trim((string) ($row[$weekendCol] ?? ''))) : '';
                $holiday = $holidayCol !== null ? strtolower(trim((string) ($row[$holidayCol] ?? ''))) : '';
                if (in_array($weekend, ['true', '1', 'yes'], true) || in_array($holiday, ['true', '1', 'yes'], true)) {
                    continue;
                }
            }

            // Leaving status unset lets the shared _determine_status() derive
            // present/late from in_time - but it treats a blank in_time as
            // absent, which is wrong here: this report often has a real
            // Clock Out with no Clock In (common in this device's export),
            // which is still evidence of attendance, just not absence.
            $status = null;
            if ($is_absent) {
                $status = 'absent';
            } elseif ($clock_in === '' && $clock_out !== '') {
                $status = 'present';
            }

            $records[] = [
                'employee_id'     => $employee_id,
                'attendance_date' => $date,
                'in_time'         => (!$is_absent && $clock_in !== '')  ? $this->_parse_time($clock_in)  : null,
                'out_time'        => (!$is_absent && $clock_out !== '') ? $this->_parse_time($clock_out) : null,
                'status'          => $status,
                'source'          => 'zkteco',
            ];
        }

        return ['records' => $records, 'unmatched' => $unmatched];
    }
}
