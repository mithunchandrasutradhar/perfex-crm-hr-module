<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Training extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Training_model');
        $this->load->model('hr_module/Hr_module_model');
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
            $result = $this->Training_model->update($data, $id);
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
                'start_time'   => $starts[$i] ?? null,
                'end_time'     => $ends[$i] ?? null,
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
}
