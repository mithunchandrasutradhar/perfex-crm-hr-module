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

        $data['title']      = 'Official Calendar';
        $data['year']       = $year;
        $data['holidays']   = $this->Holidays_model->get_all($year);
        $data['weekly_off'] = $this->Holidays_model->get_weekly_off_days();
        $data['can_edit']   = is_admin() || staff_can('edit', 'hr_holidays');

        $data = array_merge($data, $this->_build_calendar_data($cal_year, $cal_month));

        // The form now submits this via the same site-display-format datepicker
        // widget used elsewhere in the module (see attendance/table.php) -
        // convert back to ISO before the format check below.
        $roster_date = $this->input->get('roster_date') ? to_sql_date($this->input->get('roster_date')) : date('Y-m-d');
        if (!$roster_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $roster_date)) {
            $roster_date = date('Y-m-d');
        }
        $data['roster_date']   = $roster_date;
        $data['shift_roster']  = $this->Shifts_model->get_shift_roster_for_date($roster_date);

        $this->load->view('hr_module/holidays/index', $data);
    }

    // AJAX: re-renders just the merged "Who's on Leave / Shift Roster" calendar
    // for a given month, so the prev/next month buttons don't reload the page.
    public function calendar()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!is_admin() && staff_cant('view', 'hr_holidays')) {
            show_404();
        }
        $cal_year  = (int) ($this->input->get('cal_year')  ?: date('Y'));
        $cal_month = (int) ($this->input->get('cal_month') ?: date('n'));

        $data = $this->_build_calendar_data($cal_year, $cal_month);
        $data['weekly_off'] = $this->Holidays_model->get_weekly_off_days();

        $html = $this->load->view('hr_module/holidays/calendar', $data, true);
        echo json_encode(['html' => $html]);
    }

    // Builds the derived per-day lookups the merged calendar partial needs -
    // shared by index() (initial page load) and calendar() (AJAX month nav),
    // so the two never drift out of sync.
    private function _build_calendar_data($cal_year, $cal_month)
    {
        if ($cal_month < 1) { $cal_month = 12; $cal_year--; }
        if ($cal_month > 12) { $cal_month = 1; $cal_year++; }
        $cal_from = sprintf('%04d-%02d-01', $cal_year, $cal_month);
        $cal_to   = date('Y-m-t', strtotime($cal_from));

        $cal_holidays   = $this->Holidays_model->get_holiday_names_in_range($cal_from, $cal_to);
        $cal_leave_days = $this->Leave_model->get_approved_leave_days_in_range($cal_from, $cal_to);
        $cal_shifts     = $this->Shifts_model->get_approved_shifts_in_range($cal_from, $cal_to);

        $leave_by_date = [];
        foreach ($cal_leave_days as $ld) {
            $leave_by_date[$ld->leave_date][] = $ld;
        }

        // Each shift assignment is a date RANGE (not per-day rows like leave), so
        // expand it into a lookup by date, clipped to this calendar month.
        $shifts_by_date = [];
        foreach ($cal_shifts as $sh) {
            $clip_from = max($sh->from_date, $cal_from);
            $clip_to   = min($sh->to_date, $cal_to);
            for ($ts = strtotime($clip_from); $ts <= strtotime($clip_to); $ts += 86400) {
                $shifts_by_date[date('Y-m-d', $ts)][] = $sh;
            }
        }

        return [
            'cal_year'       => $cal_year,
            'cal_month'      => $cal_month,
            'cal_holidays'   => $cal_holidays,
            'leave_by_date'  => $leave_by_date,
            'shifts_by_date' => $shifts_by_date,
        ];
    }

    public function add()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!is_admin() && staff_cant('edit', 'hr_holidays')) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }
        $name         = trim($this->input->post('name', true));
        $date         = to_sql_date($this->input->post('holiday_date'));
        $end_date_raw = trim($this->input->post('end_date'));
        $end_date     = $end_date_raw !== '' ? to_sql_date($end_date_raw) : null;
        $type         = $this->input->post('type');

        if (!$name || !$date) {
            echo json_encode(['success' => false, 'message' => 'Name and date are required.']);
            return;
        }
        if ($end_date && $end_date < $date) {
            echo json_encode(['success' => false, 'message' => 'End date cannot be before the start date.']);
            return;
        }

        $id = $this->Holidays_model->add([
            'name'         => $name,
            'holiday_date' => $date,
            'end_date'     => $end_date,
            'type'         => in_array($type, ['government', 'company']) ? $type : 'government',
        ]);

        echo json_encode(['success' => (bool) $id, 'id' => $id]);
    }

    public function edit($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!is_admin() && staff_cant('edit', 'hr_holidays')) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }

        if (!$this->input->post()) {
            $holiday = $this->Holidays_model->get($id);
            if (!$holiday) { echo json_encode(null); return; }
            echo json_encode([
                'id'           => $holiday->id,
                'name'         => $holiday->name,
                'holiday_date' => _d($holiday->holiday_date),
                'end_date'     => $holiday->end_date ? _d($holiday->end_date) : '',
                'type'         => $holiday->type,
            ]);
            return;
        }

        $name         = trim($this->input->post('name', true));
        $date         = to_sql_date($this->input->post('holiday_date'));
        $end_date_raw = trim($this->input->post('end_date'));
        $end_date     = $end_date_raw !== '' ? to_sql_date($end_date_raw) : null;
        $type         = $this->input->post('type');

        if (!$name || !$date) {
            echo json_encode(['success' => false, 'message' => 'Name and date are required.']);
            return;
        }
        if ($end_date && $end_date < $date) {
            echo json_encode(['success' => false, 'message' => 'End date cannot be before the start date.']);
            return;
        }

        $updated = $this->Holidays_model->update($id, [
            'name'         => $name,
            'holiday_date' => $date,
            'end_date'     => $end_date,
            'type'         => in_array($type, ['government', 'company']) ? $type : 'government',
        ]);

        echo json_encode(['success' => (bool) $updated]);
    }

    // Manually (re-)sends the same day-before holiday announcement the cron job
    // sends automatically (see Hr_module_model::send_holiday_reminder()) - lets
    // HR resend for a specific holiday if the automated one failed, using the
    // exact same "holiday_reminder" email template.
    public function send_announcement($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!is_admin() && staff_cant('edit', 'hr_holidays')) {
            echo json_encode(['success' => false, 'message' => _l('hr_error_permission')]);
            return;
        }
        $holiday = $this->Holidays_model->get($id);
        if (!$holiday) {
            echo json_encode(['success' => false, 'message' => 'Holiday not found.']);
            return;
        }

        $this->load->model('hr_module/Email_templates_model');
        $day_name   = $this->Holidays_model->day_name_label($holiday);
        $date_label = $this->Holidays_model->date_label($holiday);

        $placeholders = [
            '{holiday_name}' => $holiday->name,
            '{day_name}'     => $day_name,
            '{date}'         => $date_label,
        ];
        $tpl = $this->Email_templates_model->render('holiday_reminder', $placeholders);

        $sent = $this->Hr_module_model->send_leave_announcement($tpl->subject, $tpl->body);
        $this->Hr_module_model->send_whatsapp_announcement('holiday_reminder', $placeholders);

        $sent_at_label = null;
        if ($sent) {
            $this->Holidays_model->mark_announcement_sent($id);
            $sent_at_label = _d(date('Y-m-d')) . ' ' . date('g:i A');
        }

        echo json_encode([
            'success'  => $sent,
            'message'  => $sent
                ? _l('hr_holiday_announcement_sent')
                : _l('hr_holiday_announcement_failed'),
            'sent_at_label' => $sent_at_label,
        ]);
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
