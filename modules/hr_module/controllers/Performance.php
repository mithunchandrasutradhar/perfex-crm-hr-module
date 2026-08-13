<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Performance extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Performance_model');
        $this->load->model('hr_module/Hr_module_model');
        $this->load->model('hr_module/Departments_model');
        $this->load->model('hr_module/Employees_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_performance') && staff_cant('view_own', 'hr_performance')) access_denied('hr_performance');
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('hr_module', 'performance/table'));
            return;
        }
        // The department filter only makes sense for someone who can see more
        // than their own/evaluated targets - table.php already forces the list
        // back to own-or-evaluator otherwise.
        $can_view_all = is_admin() || staff_can('view', 'hr_performance');
        $data['title']            = _l('hr_performance_list');
        $data['show_dept_filter'] = $can_view_all;
        $data['departments']      = $can_view_all ? $this->Departments_model->get_active() : [];
        $data['employees']        = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/performance/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_performance')) access_denied('hr_performance');
        // Someone who can only create (not view everyone) may only assign a
        // target to themselves - mirrors the same self-service restriction
        // already used for Loans/Overtime/Shifts/Helpdesk.
        $own_only   = !is_admin() && !staff_can('view', 'hr_performance');
        $own_emp_id = $own_only ? hr_get_own_employee_id() : 0;

        if ($this->input->post()) {
            $data = $this->_target_post_data($own_only, $own_emp_id);
            $data['sub_targets'] = $this->_post_sub_targets();
            $result = $this->Performance_model->assign($data);
            if ($result['success']) {
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/performance/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/performance/add'));
        }
        $data['title']      = _l('hr_performance_assign');
        $data['target']     = null;
        $data['own_only']   = $own_only;
        $data['own_emp_id'] = $own_emp_id;
        if ($own_only) {
            $emp = $this->Employees_model->get($own_emp_id);
            $data['employees'] = $own_emp_id && $emp
                ? [$own_emp_id => $emp->first_name . ' ' . $emp->last_name]
                : [];
        } else {
            $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        }
        $data['staff']     = $this->_get_staff_dropdown();
        $this->load->view('hr_module/performance/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_performance')) access_denied('hr_performance');
        $target = $this->Performance_model->get_target($id);
        if (!$target) show_404();

        if ($this->input->post()) {
            $result = $this->Performance_model->update_target($this->_target_post_data(), $id);
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
            redirect(admin_url('hr_module/performance/view/' . $id));
        }
        $data['title']     = _l('hr_performance_edit');
        $data['target']    = $target;
        $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/performance/target_edit_form', $data);
    }

    public function view($id)
    {
        $target = $this->Performance_model->get_target($id);
        if (!$target) show_404();

        $own_emp_id = hr_get_own_employee_id();
        $is_owner   = (int) $target->employee_id === $own_emp_id;
        $my_staff_id = get_staff_user_id();

        $sub_targets = $this->Performance_model->get_sub_targets($id);
        $is_evaluator_on_target = false;
        foreach ($sub_targets as $st) {
            $st->evaluators        = $this->Performance_model->get_evaluators($st->id);
            $st->feedback          = $this->Performance_model->get_feedback($st->id);
            $st->is_evaluator      = $this->Performance_model->is_evaluator($st->id, $my_staff_id);
            $st->can_change_status = staff_can('edit', 'hr_performance') || $is_owner || $st->is_evaluator;
            if ($st->is_evaluator) $is_evaluator_on_target = true;
        }

        if (staff_cant('view', 'hr_performance')
            && !(staff_can('view_own', 'hr_performance') && $is_owner)
            && !$is_evaluator_on_target) {
            access_denied('hr_performance');
        }

        $data['title']            = _l('hr_performance_view');
        $data['target']           = $target;
        $data['sub_targets']      = $sub_targets;
        $data['is_owner']         = $is_owner;
        $data['can_edit_details'] = staff_can('edit', 'hr_performance');
        $data['can_delete']       = staff_can('delete', 'hr_performance');
        $this->load->view('hr_module/performance/view', $data);
    }

    public function add_sub_target($target_id)
    {
        if (staff_cant('edit', 'hr_performance') && staff_cant('create', 'hr_performance')) access_denied('hr_performance');
        $target = $this->Performance_model->get_target($target_id);
        if (!$target) show_404();

        if ($this->input->post()) {
            $result = $this->Performance_model->add_sub_target($target_id, $this->_sub_target_post_data());
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
            redirect(admin_url('hr_module/performance/view/' . $target_id));
        }
        $data['title']      = _l('hr_performance_add_sub_target');
        $data['target']     = $target;
        $data['sub_target'] = null;
        $data['evaluators'] = [];
        $data['staff']      = $this->_get_staff_dropdown();
        $this->load->view('hr_module/performance/sub_target_form', $data);
    }

    public function edit_sub_target($id)
    {
        if (staff_cant('edit', 'hr_performance')) access_denied('hr_performance');
        $sub_target = $this->Performance_model->get_sub_target($id);
        if (!$sub_target) show_404();
        $target = $this->Performance_model->get_target($sub_target->target_id);

        if ($this->input->post()) {
            $result = $this->Performance_model->update_sub_target($id, $this->_sub_target_post_data());
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
            redirect(admin_url('hr_module/performance/view/' . $sub_target->target_id));
        }
        $data['title']      = _l('hr_performance_edit_sub_target');
        $data['target']     = $target;
        $data['sub_target'] = $sub_target;
        $data['evaluators'] = array_column($this->Performance_model->get_evaluators($id), 'staff_id');
        $data['staff']      = $this->_get_staff_dropdown();
        $this->load->view('hr_module/performance/sub_target_form', $data);
    }

    public function delete_sub_target($id)
    {
        if (staff_cant('edit', 'hr_performance') && staff_cant('delete', 'hr_performance')) access_denied('hr_performance');
        $sub_target = $this->Performance_model->get_sub_target($id);
        if (!$sub_target) show_404();
        $target_id = $sub_target->target_id;

        $result = $this->Performance_model->delete_sub_target($id);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/performance/view/' . $target_id));
    }

    public function update_status($id)
    {
        $sub_target = $this->Performance_model->get_sub_target($id);
        if (!$sub_target) show_404();
        $target = $this->Performance_model->get_target($sub_target->target_id);

        $own_emp_id   = hr_get_own_employee_id();
        $is_owner     = (int) $target->employee_id === $own_emp_id;
        $is_evaluator = $this->Performance_model->is_evaluator($id, get_staff_user_id());
        if (!staff_can('edit', 'hr_performance') && !$is_owner && !$is_evaluator) {
            access_denied('hr_performance');
        }

        $status     = $this->input->post('status');
        $percentage = $this->input->post('completion_percentage');
        $note       = $this->input->post('employee_note', true);
        $result     = $this->Performance_model->update_status($id, $status, $percentage, $note);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/performance/view/' . $sub_target->target_id));
    }

    public function add_feedback($id)
    {
        $sub_target = $this->Performance_model->get_sub_target($id);
        if (!$sub_target) show_404();

        $is_evaluator = $this->Performance_model->is_evaluator($id, get_staff_user_id());
        if (!staff_can('edit', 'hr_performance') && !$is_evaluator) {
            access_denied('hr_performance');
        }

        $feedback = $this->input->post('feedback', true);
        $rating   = $this->input->post('rating');
        $result   = $this->Performance_model->add_feedback($id, get_staff_user_id(), $feedback, $rating);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/performance/view/' . $sub_target->target_id));
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_performance')) access_denied('hr_performance');
        $result = $this->Performance_model->delete($id);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/performance'));
    }

    // A printable performance report for one employee, compiled from every Target (and
    // its Sub-Targets) they've been assigned - only the person who's allowed to assign
    // targets (the "Default Role assigned person") can generate it.
    public function employee_report($employee_id)
    {
        if (staff_cant('create', 'hr_performance') && staff_cant('edit', 'hr_performance')) {
            access_denied('hr_performance');
        }
        // A pure create-only self-service employee (no view/edit) can assign a
        // target for themselves, but must not be able to pull up anyone else's
        // report just by changing the URL - force it back to their own record,
        // the same way add() forces employee_id for that same tier of caller.
        $own_only = !is_admin() && !staff_can('view', 'hr_performance') && !staff_can('edit', 'hr_performance');
        if ($own_only) {
            $employee_id = hr_get_own_employee_id();
        }
        $employee = $this->Employees_model->get($employee_id);
        if (!$employee) show_404();

        $data['title']    = _l('hr_performance_employee_report');
        $data['employee'] = $employee;
        $data['targets']  = $this->Performance_model->get_employee_targets($employee_id);
        $this->load->view('hr_module/performance/employee_report', $data);
    }

    // $own_only/$own_emp_id are only ever passed by add() - edit()'s own call
    // (with no arguments) keeps trusting the posted employee_id exactly as
    // before, since editing an existing target already requires 'edit'.
    private function _target_post_data($own_only = false, $own_emp_id = 0)
    {
        $posted_emp_id = (int) $this->input->post('employee_id');
        return [
            'employee_id' => $own_only ? $own_emp_id : $posted_emp_id,
            'title'       => $this->input->post('title', true),
            'description' => $this->input->post('description', true),
            'due_date'    => to_sql_date($this->input->post('due_date')),
        ];
    }

    private function _sub_target_post_data()
    {
        return [
            'title'         => $this->input->post('title', true),
            'description'   => $this->input->post('description', true),
            'due_date'      => $this->input->post('due_date'),
            'evaluator_ids' => $this->input->post('evaluator_ids') ?: [],
        ];
    }

    // Assign Target form submits parallel arrays (sub_title[], sub_description[],
    // sub_due_date[], sub_evaluator_ids[i][]) - one dynamic row per sub-target.
    private function _post_sub_targets()
    {
        $titles       = $this->input->post('sub_title') ?: [];
        $descriptions = $this->input->post('sub_description') ?: [];
        $due_dates    = $this->input->post('sub_due_date') ?: [];
        $evaluators   = $this->input->post('sub_evaluator_ids') ?: [];

        $rows = [];
        foreach ($titles as $i => $title) {
            $rows[] = [
                'title'         => trim($title),
                'description'   => $descriptions[$i] ?? null,
                'due_date'      => to_sql_date($due_dates[$i] ?? null),
                'evaluator_ids' => $evaluators[$i] ?? [],
            ];
        }
        return $rows;
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
