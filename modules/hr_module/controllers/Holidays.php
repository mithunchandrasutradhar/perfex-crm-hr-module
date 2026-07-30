<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Holidays extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Holidays_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Leave_model');
        $this->load->model('hr_module/Shifts_model');
    }

    public function index()
    {
        if (!is_admin() && staff_cant('view', 'hr_holidays')) {
            access_denied('hr_holidays');
        }

        $year = (int) ($this->input->get('year') ?: date('Y'));

        $cal_year  = (int) ($this->input->get('cal_year')  ?: date('Y'));
        $cal_month = (int) ($this->input->get('cal_month') ?: date('n'));
        if ($cal_month < 1) { $cal_month = 12; $cal_year--; }
        if ($cal_month > 12) { $cal_month = 1; $cal_year++; }
        $cal_from = sprintf('%04d-%02d-01', $cal_year, $cal_month);
        $cal_to   = date('Y-m-t', strtotime($cal_from));

        $data['title']      = 'Official Calendar';
        $data['year']       = $year;
        $data['holidays']   = $this->Holidays_model->get_all($year);
        $data['weekly_off'] = $this->Holidays_model->get_weekly_off_days();
        $data['can_edit']   = is_admin() || staff_can('edit', 'hr_holidays');

        $data['cal_year']       = $cal_year;
        $data['cal_month']      = $cal_month;
        $data['cal_holidays']   = $this->Holidays_model->get_holiday_names_in_range($cal_from, $cal_to);
        $data['cal_leave_days'] = $this->Leave_model->get_approved_leave_days_in_range($cal_from, $cal_to);
        $data['cal_shifts']     = $this->Shifts_model->get_approved_shifts_in_range($cal_from, $cal_to);

        $roster_date = $this->input->get('roster_date') ?: date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $roster_date)) {
            $roster_date = date('Y-m-d');
        }
        $data['roster_date']   = $roster_date;
        $data['shift_roster']  = $this->Shifts_model->get_shift_roster_for_date($roster_date);

        $this->load->view('hr_module/holidays/index', $data);
    }

    public function add()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!is_admin() && staff_cant('edit', 'hr_holidays')) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }
        $name = trim($this->input->post('name', true));
        $date = $this->input->post('holiday_date');
        $type = $this->input->post('type');

        if (!$name || !$date) {
            echo json_encode(['success' => false, 'message' => 'Name and date are required.']);
            return;
        }

        $id = $this->Holidays_model->add([
            'name'         => $name,
            'holiday_date' => $date,
            'type'         => in_array($type, ['government', 'company']) ? $type : 'government',
        ]);

        echo json_encode(['success' => (bool) $id, 'id' => $id]);
    }

    public function delete($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!is_admin() && staff_cant('edit', 'hr_holidays')) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }
        echo json_encode(['success' => $this->Holidays_model->delete((int) $id)]);
    }

    public function save_weekly_off()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!is_admin() && staff_cant('edit', 'hr_holidays')) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }
        $days = $this->input->post('weekly_off_days');
        $val  = '';
        if (is_array($days)) {
            $clean = array_map('intval', $days);
            $clean = array_filter($clean, function ($d) { return $d >= 0 && $d <= 6; });
            $val   = implode(',', array_values($clean));
        }
        $this->Hr_module_model->save_settings(['weekly_off_days' => $val]);
        echo json_encode(['success' => true]);
    }

    /**
     * AJAX endpoint used by the leave apply form to get holidays + weekly-off config for a year.
     * Public (no special permission — any logged-in staff can call this).
     */
    public function get_for_year()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $year = (int) ($this->input->get('year') ?: date('Y'));
        echo json_encode([
            'holidays'   => $this->Holidays_model->get_as_json($year),
            'weekly_off' => $this->Holidays_model->get_weekly_off_days(),
        ]);
    }
}
