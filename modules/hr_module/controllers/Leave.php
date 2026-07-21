<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leave extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Leave_model');
        $this->load->model('hr_module/Employees_model');
        $this->load->model('hr_module/Hr_module_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_leave') && staff_cant('view_own', 'hr_leave')) {
            access_denied('hr_leave');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'leave/table'));
        }
        $data['title']        = _l('hr_leave_list');
        $data['leave_types']  = $this->Leave_model->get_active_types();
        $this->load->model('hr_module/Departments_model');
        $data['departments']  = $this->Departments_model->get_active();
        $this->load->view('hr_module/leave/index', $data);
    }

    public function apply()
    {
        if (staff_cant('create', 'hr_leave')) {
            access_denied('hr_leave');
        }
        // Any non-admin, non-global-viewer applies only for themselves
        $own_only   = !is_admin() && !staff_can('view', 'hr_leave');
        $own_emp_id = $own_only ? hr_get_own_employee_id() : 0;

        if ($this->input->post()) {
            // view_own users can only apply for themselves — ignore any spoofed employee_id
            $posted_emp_id = (int) $this->input->post('employee_id');
            $resolved_emp_id = $own_only ? $own_emp_id : $posted_emp_id;

            $leave_type = $this->Leave_model->get_type((int) $this->input->post('leave_type_id'));

            $days = ($leave_type && $leave_type->is_date_range)
                ? $this->_build_range_days()
                : $this->_parse_posted_days();
            if ($days === null) {
                set_alert('danger', _l('hr_val_no_leave_days'));
                redirect(admin_url('hr_module/leave/apply'));
            }

            $data = [
                'employee_id'   => $resolved_emp_id,
                'leave_type_id' => (int) $this->input->post('leave_type_id'),
                'reason'        => $this->input->post('reason', true),
            ];

            // Handle attachment
            if (!empty($_FILES['attachment']['name'])) {
                $upload_path = FCPATH . 'uploads/hr_module/leaves/';
                if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);
                $this->load->library('upload', [
                    'upload_path'   => $upload_path,
                    'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx',
                    'max_size'      => 4096,
                    'encrypt_name'  => true,
                ]);
                if ($this->upload->do_upload('attachment')) {
                    $data['attachment'] = $this->upload->data('file_name');
                }
            }

            $result = $this->Leave_model->apply($data, $days);
            if ($result['success']) {
                $req      = $this->Leave_model->get_request($result['id']);
                $req_days = $this->Leave_model->get_request_days($result['id']);
                $time_fmt = (get_option('time_format') == 24) ? 'H:i' : 'g:i A';
                $dates_html = implode('<br>', array_map(function ($d) use ($time_fmt) {
                    $line = _d($d->leave_date) . ' &mdash; ' . htmlspecialchars(hr_leave_day_type_label($d->day_type));
                    if ($d->day_type === 'hourly' && $d->hour_start && $d->hour_end) {
                        $line .= ' (' . date($time_fmt, strtotime($d->hour_start)) . ' - ' . date($time_fmt, strtotime($d->hour_end)) . ')';
                    }
                    return $line;
                }, $req_days));

                $message = '<p>A new leave request has been submitted and is awaiting review.</p>'
                    . $this->Hr_module_model->format_notification_details([
                        'Employee'    => htmlspecialchars($req->employee_name . ' (' . $req->employee_code . ')'),
                        'Department'  => htmlspecialchars($req->department_name ?: '-'),
                        'Designation' => htmlspecialchars($req->designation_name ?: '-'),
                        'Leave Type'  => htmlspecialchars($req->leave_type_name ?? ''),
                        'Leave Dates' => $dates_html,
                        'Total Days'  => $req->total_days,
                        'Reason'      => nl2br(htmlspecialchars($req->reason ?: '-')),
                    ]);
                $this->Hr_module_model->send_notification_email(
                    'New Leave Request Submitted',
                    $message,
                    admin_url('hr_module/leave/view/' . $result['id'])
                );
                set_alert('success', _l('hr_leave_applied_msg'));
                redirect(admin_url('hr_module/leave/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/leave/apply'));
        }

        $this->load->model('hr_module/Holidays_model');
        $year = (int) date('Y');

        $data['title']          = _l('hr_leave_add');
        $data['leave_types']    = $this->Leave_model->get_active_types();
        $data['own_only']       = $own_only;
        $data['own_emp_id']     = $own_emp_id;
        $data['holidays_json']  = json_encode($this->Holidays_model->get_as_json($year));
        $data['weekly_off_json']= json_encode($this->Holidays_model->get_weekly_off_days());

        if ($own_only) {
            $emp = $this->Employees_model->get($own_emp_id);
            $data['employees'] = $own_emp_id && $emp
                ? [$own_emp_id => $emp->first_name . ' ' . $emp->last_name]
                : [];
        } else {
            $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        }
        $this->load->view('hr_module/leave/apply', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_leave') && staff_cant('view_own', 'hr_leave')) {
            access_denied('hr_leave');
        }
        $request = $this->Leave_model->get_request($id);
        if (!$request) show_404();
        if (!staff_can('view', 'hr_leave') && staff_can('view_own', 'hr_leave')) {
            if ((int) $request->employee_id !== hr_get_own_employee_id()) {
                access_denied('hr_leave');
            }
        }

        $data['title']   = _l('hr_leave_view') . ' #' . $id;
        $data['request'] = $request;
        $data['days']    = $this->Leave_model->get_request_days($id);
        $data['balance'] = $this->Leave_model->get_balance(
            $request->employee_id, $request->leave_type_id, date('Y', strtotime($request->from_date))
        );
        $this->load->view('hr_module/leave/view', $data);
    }

    public function approve($id)
    {
        if (staff_cant('approve', 'hr_leave') && !is_admin()) {
            access_denied('hr_leave');
        }
        $notes  = $this->input->post('notes', true);
        $result = $this->Leave_model->approve($id, $notes);
        if ($result['success']) {
            $this->_send_status_email($id, 'approved', $notes);
            $this->_broadcast_leave_announcement($id);
        }
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_leave_approved') : $result['message']);
        redirect(admin_url('hr_module/leave/view/' . $id));
    }

    // Emails the requesting employee at their own registered address once their
    // leave request has been approved, with the actual leave date(s) and a link
    // back to their request. No-op if the employee has no email on file.
    private function _send_status_email($id, $status, $notes = null)
    {
        $req = $this->Leave_model->get_request($id);
        if (!$req || empty($req->employee_email)) {
            return;
        }

        $req_days = $this->Leave_model->get_request_days($id);
        $time_fmt = (get_option('time_format') == 24) ? 'H:i' : 'g:i A';
        $dates_html = implode('<br>', array_map(function ($d) use ($time_fmt) {
            $line = _d($d->leave_date) . ' &mdash; ' . htmlspecialchars(hr_leave_day_type_label($d->day_type));
            if ($d->day_type === 'hourly' && $d->hour_start && $d->hour_end) {
                $line .= ' (' . date($time_fmt, strtotime($d->hour_start)) . ' - ' . date($time_fmt, strtotime($d->hour_end)) . ')';
            }
            return $line;
        }, $req_days));

        $details = [
            'Department'  => htmlspecialchars($req->department_name ?: '-'),
            'Designation' => htmlspecialchars($req->designation_name ?: '-'),
            'Leave Type'  => htmlspecialchars($req->leave_type_name ?? ''),
            'Leave Dates' => $dates_html,
            'Total Days'  => $req->total_days,
        ];
        if ($notes) {
            $details['Notes'] = nl2br(htmlspecialchars($notes));
        }

        $this->Hr_module_model->send_employee_email(
            $req->employee_email,
            'Your Leave Request Has Been ' . ucfirst($status),
            '<p>Hi ' . htmlspecialchars($req->employee_name) . ',</p>'
                . '<p>Your leave request has been <strong style="color:#059669">' . htmlspecialchars($status) . '</strong>.</p>'
                . $this->Hr_module_model->format_notification_details($details),
            admin_url('hr_module/leave/view/' . $id)
        );
    }

    // Broadcasts a formal announcement to all active staff once a leave request
    // is approved, so colleagues know the employee will be out on those dates.
    private function _broadcast_leave_announcement($id)
    {
        $req = $this->Leave_model->get_request($id);
        if (!$req) {
            return;
        }

        $req_days = $this->Leave_model->get_request_days($id);
        $time_fmt = (get_option('time_format') == 24) ? 'H:i' : 'g:i A';
        $dates_html = implode('<br>', array_map(function ($d) use ($time_fmt) {
            $line = _d($d->leave_date) . ' &mdash; ' . htmlspecialchars(hr_leave_day_type_label($d->day_type));
            if ($d->day_type === 'hourly' && $d->hour_start && $d->hour_end) {
                $line .= ' (' . date($time_fmt, strtotime($d->hour_start)) . ' - ' . date($time_fmt, strtotime($d->hour_end)) . ')';
            }
            return $line;
        }, $req_days));

        $message = '<p>Dear Team,</p>'
            . '<p>This is to formally inform you that <strong>' . htmlspecialchars($req->employee_name) . '</strong>'
            . ' (' . htmlspecialchars($req->employee_code) . ') will be on leave as per the schedule below. '
            . 'Please plan your work accordingly during this period.</p>'
            . $this->Hr_module_model->format_notification_details([
                'Employee'    => htmlspecialchars($req->employee_name . ' (' . $req->employee_code . ')'),
                'Department'  => htmlspecialchars($req->department_name ?: '-'),
                'Designation' => htmlspecialchars($req->designation_name ?: '-'),
                'Leave Type'  => htmlspecialchars($req->leave_type_name ?? ''),
                'Leave Dates' => $dates_html,
                'Total Days'  => $req->total_days,
            ])
            . '<p>Regards,<br>HR Department</p>';

        $this->Hr_module_model->send_leave_announcement(
            'Leave Announcement: ' . $req->employee_name . ' will be on leave',
            $message
        );
    }

    public function reject($id)
    {
        if (staff_cant('approve', 'hr_leave') && !is_admin()) {
            access_denied('hr_leave');
        }
        $reason = $this->input->post('reason', true);
        $result = $this->Leave_model->reject($id, $reason);
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
            return;
        }
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_leave_rejected_msg') : $result['message']);
        redirect(admin_url('hr_module/leave/view/' . $id));
    }

    public function cancel($id)
    {
        $reason = $this->input->post('reason', true);
        $result = $this->Leave_model->cancel($id, $reason ?: '');
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_leave_status_cancelled') : $result['message']);
        redirect(admin_url('hr_module/leave'));
    }

    // Employee requests cancellation of their own already-approved leave - HR must
    // review it via approve_cancellation()/reject_cancellation() below, since the
    // leave was already approved (balance deducted, colleagues possibly notified).
    public function request_cancellation($id)
    {
        if (staff_cant('view', 'hr_leave') && staff_cant('view_own', 'hr_leave')) {
            access_denied('hr_leave');
        }
        $request = $this->Leave_model->get_request($id);
        if (!$request) show_404();
        if (!staff_can('view', 'hr_leave') && staff_can('view_own', 'hr_leave')) {
            if ((int) $request->employee_id !== hr_get_own_employee_id()) {
                access_denied('hr_leave');
            }
        }
        $reason = $this->input->post('reason', true);
        $result = $this->Leave_model->request_cancellation($id, $reason);
        if ($result['success']) {
            $this->_notify_cancellation_requested($id, $reason);
        }
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_leave_cancellation_requested_msg') : $result['message']);
        redirect(admin_url('hr_module/leave/view/' . $id));
    }

    public function approve_cancellation($id)
    {
        if (staff_cant('approve', 'hr_leave') && !is_admin()) {
            access_denied('hr_leave');
        }
        $result = $this->Leave_model->approve_cancellation($id);
        if ($result['success']) {
            $this->_send_cancellation_status_email($id, 'approved');
            $this->_broadcast_leave_cancellation($id);
        }
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_leave_cancellation_approved_msg') : $result['message']);
        redirect(admin_url('hr_module/leave/view/' . $id));
    }

    public function reject_cancellation($id)
    {
        if (staff_cant('approve', 'hr_leave') && !is_admin()) {
            access_denied('hr_leave');
        }
        $result = $this->Leave_model->reject_cancellation($id);
        if ($result['success']) {
            $this->_send_cancellation_status_email($id, 'rejected');
        }
        set_alert($result['success'] ? 'success' : 'danger',
            $result['success'] ? _l('hr_leave_cancellation_rejected_msg') : $result['message']);
        redirect(admin_url('hr_module/leave/view/' . $id));
    }

    // Notifies the configured HR inbox when an employee requests to cancel an
    // already-approved leave request, with a link to review/accept it.
    private function _notify_cancellation_requested($id, $reason)
    {
        $req = $this->Leave_model->get_request($id);
        if (!$req) {
            return;
        }

        $req_days = $this->Leave_model->get_request_days($id);
        $time_fmt = (get_option('time_format') == 24) ? 'H:i' : 'g:i A';
        $dates_html = implode('<br>', array_map(function ($d) use ($time_fmt) {
            $line = _d($d->leave_date) . ' &mdash; ' . htmlspecialchars(hr_leave_day_type_label($d->day_type));
            if ($d->day_type === 'hourly' && $d->hour_start && $d->hour_end) {
                $line .= ' (' . date($time_fmt, strtotime($d->hour_start)) . ' - ' . date($time_fmt, strtotime($d->hour_end)) . ')';
            }
            return $line;
        }, $req_days));

        $message = '<p>An employee has requested to cancel an already-approved leave request.</p>'
            . $this->Hr_module_model->format_notification_details([
                'Employee'    => htmlspecialchars($req->employee_name . ' (' . $req->employee_code . ')'),
                'Department'  => htmlspecialchars($req->department_name ?: '-'),
                'Designation' => htmlspecialchars($req->designation_name ?: '-'),
                'Leave Type'  => htmlspecialchars($req->leave_type_name ?? ''),
                'Leave Dates' => $dates_html,
                'Total Days'  => $req->total_days,
                'Reason'      => nl2br(htmlspecialchars($reason ?: '-')),
            ]);
        $this->Hr_module_model->send_notification_email(
            'Leave Cancellation Request Submitted',
            $message,
            admin_url('hr_module/leave/view/' . $id)
        );
    }

    // Emails the requesting employee once their leave cancellation request has been
    // approved (leave is now cancelled) or rejected (leave stays approved as-is).
    private function _send_cancellation_status_email($id, $status)
    {
        $req = $this->Leave_model->get_request($id);
        if (!$req || empty($req->employee_email)) {
            return;
        }

        $req_days = $this->Leave_model->get_request_days($id);
        $time_fmt = (get_option('time_format') == 24) ? 'H:i' : 'g:i A';
        $dates_html = implode('<br>', array_map(function ($d) use ($time_fmt) {
            $line = _d($d->leave_date) . ' &mdash; ' . htmlspecialchars(hr_leave_day_type_label($d->day_type));
            if ($d->day_type === 'hourly' && $d->hour_start && $d->hour_end) {
                $line .= ' (' . date($time_fmt, strtotime($d->hour_start)) . ' - ' . date($time_fmt, strtotime($d->hour_end)) . ')';
            }
            return $line;
        }, $req_days));

        $details = [
            'Department'  => htmlspecialchars($req->department_name ?: '-'),
            'Designation' => htmlspecialchars($req->designation_name ?: '-'),
            'Leave Type'  => htmlspecialchars($req->leave_type_name ?? ''),
            'Leave Dates' => $dates_html,
            'Total Days'  => $req->total_days,
        ];

        $color = $status === 'approved' ? '#059669' : '#dc2626';
        $intro = $status === 'approved'
            ? 'Your leave cancellation request has been <strong style="color:' . $color . '">approved</strong> - this leave is now cancelled.'
            : 'Your leave cancellation request has been <strong style="color:' . $color . '">rejected</strong> - this leave remains approved.';

        $this->Hr_module_model->send_employee_email(
            $req->employee_email,
            'Your Leave Cancellation Request Has Been ' . ucfirst($status),
            '<p>Hi ' . htmlspecialchars($req->employee_name) . ',</p>'
                . '<p>' . $intro . '</p>'
                . $this->Hr_module_model->format_notification_details($details),
            admin_url('hr_module/leave/view/' . $id)
        );
    }

    // Broadcasts a follow-up announcement once an approved leave is actually cancelled,
    // so colleagues who saw the original "will be on leave" announcement know it no
    // longer applies.
    private function _broadcast_leave_cancellation($id)
    {
        $req = $this->Leave_model->get_request($id);
        if (!$req) {
            return;
        }

        $req_days = $this->Leave_model->get_request_days($id);
        $time_fmt = (get_option('time_format') == 24) ? 'H:i' : 'g:i A';
        $dates_html = implode('<br>', array_map(function ($d) use ($time_fmt) {
            $line = _d($d->leave_date) . ' &mdash; ' . htmlspecialchars(hr_leave_day_type_label($d->day_type));
            if ($d->day_type === 'hourly' && $d->hour_start && $d->hour_end) {
                $line .= ' (' . date($time_fmt, strtotime($d->hour_start)) . ' - ' . date($time_fmt, strtotime($d->hour_end)) . ')';
            }
            return $line;
        }, $req_days));

        $message = '<p>Dear Team,</p>'
            . '<p>Please note that the previously announced leave for <strong>' . htmlspecialchars($req->employee_name) . '</strong>'
            . ' (' . htmlspecialchars($req->employee_code) . ') has been <strong style="color:#dc2626">cancelled</strong>. '
            . 'Please disregard the earlier announcement.</p>'
            . $this->Hr_module_model->format_notification_details([
                'Employee'    => htmlspecialchars($req->employee_name . ' (' . $req->employee_code . ')'),
                'Department'  => htmlspecialchars($req->department_name ?: '-'),
                'Designation' => htmlspecialchars($req->designation_name ?: '-'),
                'Leave Type'  => htmlspecialchars($req->leave_type_name ?? ''),
                'Leave Dates' => $dates_html,
            ])
            . '<p>Regards,<br>HR Department</p>';

        $this->Hr_module_model->send_leave_announcement(
            'Leave Cancellation: ' . $req->employee_name . '\'s leave has been cancelled',
            $message
        );
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_leave')) {
            access_denied('hr_leave');
        }
        $this->Leave_model->delete_request($id);
        set_alert('success', _l('deleted_successfully', 'Leave Request'));
        redirect(admin_url('hr_module/leave'));
    }

    public function get_balance_ajax()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $emp_id  = $this->input->get('employee_id');
        $type_id = $this->input->get('leave_type_id');
        $year    = $this->input->get('year') ?: date('Y');
        $balance = $this->Leave_model->get_balance($emp_id, $type_id, $year);
        $remaining = 0;
        if ($balance) {
            $remaining = $balance->allocated_days + $balance->carry_forward_days - $balance->used_days;
        }
        echo json_encode(['balance' => $balance, 'remaining' => $remaining]);
    }

    // Parses the day-by-day rows posted from the apply form (days[i][date/type/half_period/hour_start/hour_end])
    // into the ['date', 'type', 'hour_start', 'hour_end'] shape Leave_model::apply() expects.
    // Returns null if no valid day rows were submitted.
    private function _parse_posted_days()
    {
        $posted = $this->input->post('days');
        if (empty($posted) || !is_array($posted)) return null;

        $days = [];
        foreach ($posted as $row) {
            if (empty($row['date']) || empty($row['type'])) continue;

            $type = $row['type'];
            if ($type === 'half') {
                $period   = ($row['half_period'] ?? '') === 'after_lunch' ? 'after_lunch' : 'before_lunch';
                $day_type = 'half_' . $period;
            } else {
                $day_type = $type;
            }

            $hour_start = $row['hour_start'] ?? null;
            $hour_end   = $row['hour_end'] ?? null;

            $days[] = [
                'date'       => to_sql_date($row['date']),
                'type'       => $day_type,
                'hour_start' => $hour_start ? date('H:i:s', strtotime($hour_start)) : null,
                'hour_end'   => $hour_end   ? date('H:i:s', strtotime($hour_end))   : null,
            ];
        }

        return $days ?: null;
    }

    // For date-range leave types (e.g. Maternity Leave): builds one 'full' day entry
    // per calendar day between range_from_date and range_to_date, inclusive, in the
    // same shape Leave_model::apply() expects. Returns null if the range is missing
    // or invalid.
    private function _build_range_days()
    {
        $from = to_sql_date($this->input->post('range_from_date'));
        $to   = to_sql_date($this->input->post('range_to_date'));
        if (!$from || !$to || $to < $from) return null;

        $days = [];
        for ($ts = strtotime($from); $ts <= strtotime($to); $ts += 86400) {
            $days[] = [
                'date'       => date('Y-m-d', $ts),
                'type'       => 'full',
                'hour_start' => null,
                'hour_end'   => null,
            ];
        }
        return $days ?: null;
    }
}
