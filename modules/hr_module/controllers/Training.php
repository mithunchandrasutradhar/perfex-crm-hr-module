<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Training extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Training_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Email_templates_model');
    }

    public function index()
    {
        $has_own = $this->Training_model->has_own_or_instructor(hr_get_own_employee_id(), get_staff_user_id());
        if (staff_cant('view', 'hr_training') && staff_cant('view_own', 'hr_training') && !$has_own) {
            access_denied('hr_training');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'training/table'));
            return;
        }
        $data['title'] = _l('hr_training_list');
        $this->load->view('hr_module/training/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_training')) access_denied('hr_training');
        if ($this->input->post()) {
            $data = $this->_post_data();
            $this->_handle_attachment($data);
            $result = $this->Training_model->add($data);
            if ($result['success']) {
                if ($this->Hr_module_model->notifications_enabled('notify_training')) {
                    $this->_notify_instructor_assigned($result['id']);
                }
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/training/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/training/add'));
        }
        $data['title']    = _l('hr_training_add');
        $data['training'] = null;
        $data['staff']    = $this->_get_staff_dropdown();
        $data['sessions'] = [];
        $this->load->view('hr_module/training/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_training')) access_denied('hr_training');
        $training = $this->Training_model->get($id);
        if (!$training) show_404();
        if ($this->input->post()) {
            $data = $this->_post_data();
            $this->_handle_attachment($data);
            $old_instructor_id = (int) $training->instructor_id;
            $result = $this->Training_model->update($data, $id);
            $new_instructor_id = !empty($data['instructor_id']) ? (int) $data['instructor_id'] : 0;
            // Only notify when the instructor is actually being newly set/changed -
            // otherwise saving an unrelated field on every edit would re-notify them.
            if ($result['success'] && $new_instructor_id && $new_instructor_id !== $old_instructor_id
                && $this->Hr_module_model->notifications_enabled('notify_training')) {
                $this->_notify_instructor_assigned($id);
            }
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
            redirect(admin_url('hr_module/training/view/' . $id));
        }
        $data['title']    = _l('hr_training_edit');
        $data['training'] = $training;
        $data['staff']    = $this->_get_staff_dropdown();
        $data['sessions'] = $this->Training_model->get_sessions($id);
        $this->load->view('hr_module/training/form', $data);
    }

    public function view($id)
    {
        $training = $this->Training_model->get($id);
        if (!$training) show_404();

        $own_emp_id   = hr_get_own_employee_id();
        $is_instructor = $this->Training_model->is_instructor($id, get_staff_user_id());

        if (staff_cant('view', 'hr_training') && staff_cant('view_own', 'hr_training') && !$is_instructor) {
            access_denied('hr_training');
        }

        $data['title']         = _l('hr_training_view');
        $data['training']      = $training;
        $data['participants']  = $this->Training_model->get_participants($id);
        $data['employees']     = $this->Hr_module_model->get_active_employees_dropdown();
        $data['is_instructor'] = $is_instructor;
        $data['own_emp_id']    = $own_emp_id;
        $data['can_mark_attendance'] = staff_can('edit', 'hr_training') || $is_instructor;
        $sessions = $this->Training_model->get_sessions($id);
        $data['sessions']        = $sessions;
        $data['days']            = array_column($sessions, 'session_date');
        $data['attendance_grid'] = $this->Training_model->get_attendance_grid($id);
        $this->load->view('hr_module/training/view', $data);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_training')) access_denied('hr_training');
        $result = $this->Training_model->delete($id);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/training'));
    }

    public function enroll($id)
    {
        if (staff_cant('edit', 'hr_training')) access_denied('hr_training');
        if ($this->input->is_ajax_request()) {
            $emp_ids = $this->input->post('employee_ids') ?: [];
            $result  = $this->Training_model->enroll($id, $emp_ids);
            if ($result['success'] && !empty($result['enrolled_ids'])
                && $this->Hr_module_model->notifications_enabled('notify_training')) {
                $this->_notify_enrolled($id, $result['enrolled_ids']);
            }
            echo json_encode($result);
            return;
        }
        redirect(admin_url('hr_module/training/view/' . $id));
    }

    public function remove_participant($training_id, $employee_id)
    {
        if (staff_cant('edit', 'hr_training')) access_denied('hr_training');
        $this->Training_model->remove_participant($training_id, $employee_id);
        set_alert('success', 'Participant removed.');
        redirect(admin_url('hr_module/training/view/' . $training_id));
    }

    // Instructor (or HR) leaves a private note about how this employee did.
    public function save_employee_note($training_id, $employee_id)
    {
        $is_instructor = $this->Training_model->is_instructor($training_id, get_staff_user_id());
        if (staff_cant('edit', 'hr_training') && !$is_instructor) {
            access_denied('hr_training');
        }
        $note   = $this->input->post('note', true);
        $result = $this->Training_model->save_employee_note($training_id, $employee_id, $note);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/training/view/' . $training_id));
    }

    // The enrolled employee leaves their own feedback about the instructor/training.
    public function save_employee_feedback($training_id, $employee_id)
    {
        if ((int) $employee_id !== hr_get_own_employee_id()) {
            access_denied('hr_training');
        }
        $feedback = $this->input->post('feedback', true);
        $result   = $this->Training_model->save_employee_feedback($training_id, $employee_id, $feedback);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/training/view/' . $training_id));
    }

    public function mark_complete($id)
    {
        $is_instructor = $this->Training_model->is_instructor($id, get_staff_user_id());
        if (staff_cant('edit', 'hr_training') && !$is_instructor) {
            access_denied('hr_training');
        }
        $note   = $this->input->post('completion_note', true);
        $result = $this->Training_model->mark_complete($id, $note);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/training/view/' . $id));
    }

    // A printable report for one training - the instructor, or the role-based
    // person allowed to assign trainings, can generate it.
    public function report($id)
    {
        $training = $this->Training_model->get($id);
        if (!$training) show_404();

        $is_instructor = $this->Training_model->is_instructor($id, get_staff_user_id());
        if (staff_cant('create', 'hr_training') && staff_cant('edit', 'hr_training') && !$is_instructor) {
            access_denied('hr_training');
        }

        $sessions = $this->Training_model->get_sessions($id);
        $data['title']           = _l('hr_training_report');
        $data['training']        = $training;
        $data['participants']    = $this->Training_model->get_participants($id);
        $data['sessions']        = $sessions;
        $data['days']            = array_column($sessions, 'session_date');
        $data['attendance_grid'] = $this->Training_model->get_attendance_grid($id);
        $this->load->view('hr_module/training/report', $data);
    }

    // Emails a summary of the training report to the admin inbox already
    // configured in Settings (hr_notification_email) - the same address every
    // other HR-module admin notification uses, not a new setting.
    public function email_report($id)
    {
        $training = $this->Training_model->get($id);
        if (!$training) show_404();

        $is_instructor = $this->Training_model->is_instructor($id, get_staff_user_id());
        if (staff_cant('create', 'hr_training') && staff_cant('edit', 'hr_training') && !$is_instructor) {
            access_denied('hr_training');
        }

        // Same premium look as the printable report - rendered from a dedicated
        // view (report_email.php) built with table/inline-style markup only, since
        // email clients strip <style> blocks and don't support flexbox/grid.
        $sessions = $this->Training_model->get_sessions($id);
        $data['training']        = $training;
        $data['participants']    = $this->Training_model->get_participants($id);
        $data['sessions']        = $sessions;
        $data['days']            = array_column($sessions, 'session_date');
        $data['attendance_grid'] = $this->Training_model->get_attendance_grid($id);
        $message = $this->load->view('hr_module/training/report_email', $data, true);

        $sent = $this->Hr_module_model->send_notification_email(
            'Training Report: ' . $training->title,
            $message,
            admin_url('hr_module/training/report/' . $id)
        );

        set_alert($sent ? 'success' : 'danger', $sent ? _l('hr_training_report_emailed') : _l('hr_training_report_email_failed'));
        redirect(admin_url('hr_module/training/view/' . $id));
    }

    public function mark_attendance($training_id, $employee_id)
    {
        $is_instructor = $this->Training_model->is_instructor($training_id, get_staff_user_id());
        if (staff_cant('edit', 'hr_training') && !$is_instructor) {
            access_denied('hr_training');
        }
        $status = $this->input->post('status');
        $date   = $this->input->post('attendance_date') ?: date('Y-m-d');
        $result = $this->Training_model->mark_attendance($training_id, $employee_id, $status, $date);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/training/view/' . $training_id));
    }

    // Confirms one employee's attendance for a single day of a multi-day training.
    public function mark_daily_attendance($training_id, $employee_id)
    {
        $is_instructor = $this->Training_model->is_instructor($training_id, get_staff_user_id());
        if (staff_cant('edit', 'hr_training') && !$is_instructor) {
            access_denied('hr_training');
        }
        $date   = $this->input->post('date');
        $status = $this->input->post('status');
        $result = $this->Training_model->mark_daily_attendance($training_id, $employee_id, $date, $status);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/training/view/' . $training_id));
    }

    private function _post_data()
    {
        return [
            'title'         => $this->input->post('title', true),
            'instructor_id' => $this->input->post('instructor_id'),
            'venue'         => $this->input->post('venue', true),
            'cost'          => $this->input->post('cost'),
            'capacity'      => $this->input->post('capacity'),
            'description'   => $this->input->post('description', true),
            'status'        => $this->input->post('status'),
            'sessions'      => $this->_post_sessions(),
        ];
    }

    // Zips the day-by-day repeater's parallel arrays into one list of session
    // rows, dropping any row left without a date.
    private function _post_sessions()
    {
        $dates  = $this->input->post('session_date') ?: [];
        $starts = $this->input->post('session_start_time') ?: [];
        $ends   = $this->input->post('session_end_time') ?: [];

        $sessions = [];
        foreach ($dates as $i => $date) {
            if (empty($date)) continue;
            $sessions[] = [
                'session_date' => to_sql_date($date),
                'start_time'   => !empty($starts[$i]) ? date('H:i:s', strtotime($starts[$i])) : null,
                'end_time'     => !empty($ends[$i]) ? date('H:i:s', strtotime($ends[$i])) : null,
            ];
        }
        return $sessions;
    }

    private function _handle_attachment(&$data)
    {
        if (empty($_FILES['attachment']['name'])) return;
        $path = FCPATH . 'uploads/hr_module/training/';
        if (!is_dir($path)) mkdir($path, 0755, true);
        $this->load->library('upload', [
            'upload_path'   => $path,
            'allowed_types' => 'pdf|doc|docx|ppt|pptx|jpg|png',
            'max_size'      => 5120,
            'encrypt_name'  => true,
        ]);
        if ($this->upload->do_upload('attachment')) {
            $data['attachment'] = $this->upload->data('file_name');
        }
    }

    private function _get_staff_dropdown()
    {
        $staff = $this->db->select('staffid, CONCAT(firstname," ",lastname) as name')
            ->where('active', 1)->get(db_prefix() . 'staff')->result();
        $out = [];
        foreach ($staff as $s) $out[$s->staffid] = $s->name;
        return $out;
    }

    private function _training_dates_label($training)
    {
        if (!$training->start_date) return '-';
        if ($training->start_date === $training->end_date) return _d($training->start_date);
        return _d($training->start_date) . ' - ' . _d($training->end_date);
    }

    // Day-by-day schedule with actual session times (e.g. "01 Jul 2026, 9:00 AM -
    // 5:00 PM<br>02 Jul 2026, 9:00 AM - 5:00 PM") for the notification emails -
    // falls back to the plain date range when no sessions are set up yet.
    private function _training_schedule_label($training_id, $training)
    {
        $sessions = $this->Training_model->get_sessions($training_id);
        if (empty($sessions)) {
            return $this->_training_dates_label($training);
        }
        $lines = [];
        foreach ($sessions as $s) {
            $line = _d($s->session_date);
            if ($s->start_time || $s->end_time) {
                $line .= ', ' . ($s->start_time ? date('g:i A', strtotime($s->start_time)) : '?')
                       . ' - ' . ($s->end_time ? date('g:i A', strtotime($s->end_time)) : '?');
            }
            $lines[] = $line;
        }
        return implode('<br>', $lines);
    }

    // Emails the assigned instructor (a staff account) the training's details -
    // fired on create (if an instructor is picked) and on edit only when the
    // instructor actually changes, so re-saving other fields doesn't re-notify them.
    // Wrapped in try/catch - a notification hiccup must never break the
    // save-and-redirect flow that triggered it (same rule Hr_module_model's own
    // senders follow).
    private function _notify_instructor_assigned($training_id)
    {
        try {
            $training = $this->Training_model->get($training_id);
            if (!$training || !$training->instructor_id) return;

            $staff = $this->db->select('email, CONCAT(firstname," ",lastname) as name')
                ->where('staffid', $training->instructor_id)
                ->get(db_prefix() . 'staff')->row();
            if (!$staff || empty($staff->email)) return;

            $tpl = $this->Email_templates_model->render('training_instructor_assigned', [
                '{instructor_name}' => $staff->name,
                '{training_title}'  => $training->title,
                '{venue}'           => $training->venue ?: '-',
                '{schedule}'        => $this->_training_schedule_label($training_id, $training),
                '{status}'          => ucfirst($training->status),
                '{description}'     => $training->description ?: '-',
            ]);

            $this->Hr_module_model->send_employee_email(
                $staff->email,
                $tpl->subject,
                $tpl->body,
                admin_url('hr_module/training/view/' . $training_id)
            );
        } catch (Exception $e) {
            log_activity('HR Training instructor-assigned email failed: ' . $e->getMessage());
        }
    }

    // Emails each newly-enrolled employee the training's details. Wrapped in
    // try/catch for the same reason as _notify_instructor_assigned() above.
    private function _notify_enrolled($training_id, $employee_ids)
    {
        try {
            $training = $this->Training_model->get($training_id);
            if (!$training || empty($employee_ids)) return;

            $employees = $this->db->select('email, CONCAT(first_name," ",last_name) as name')
                ->where_in('id', $employee_ids)
                ->where('email !=', '')
                ->get(db_prefix() . 'hr_employees')->result();

            foreach ($employees as $emp) {
                $tpl = $this->Email_templates_model->render('training_enrolled', [
                    '{employee_name}'   => $emp->name,
                    '{training_title}'  => $training->title,
                    '{instructor_name}' => $training->instructor_name ?: $training->trainer ?: '-',
                    '{venue}'           => $training->venue ?: '-',
                    '{schedule}'        => $this->_training_schedule_label($training_id, $training),
                    '{description}'     => $training->description ?: '-',
                ]);

                $this->Hr_module_model->send_employee_email(
                    $emp->email,
                    $tpl->subject,
                    $tpl->body,
                    admin_url('hr_module/training/view/' . $training_id)
                );
            }
        } catch (Exception $e) {
            log_activity('HR Training enrollment email failed: ' . $e->getMessage());
        }
    }
}
