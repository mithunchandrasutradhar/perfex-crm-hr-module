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
    }

    public function index()
    {
        if (staff_cant('view', 'hr_performance')) access_denied('hr_performance');
        if ($this->input->is_ajax_request() && !$this->input->post()) {
            $this->app->get_table_data(module_views_path('hr_module', 'performance/table'));
            return;
        }
        $data['title']       = _l('hr_performance_list');
        $data['departments'] = $this->Departments_model->get_active();
        $data['employees']   = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/performance/index', $data);
    }

    public function add()
    {
        if (staff_cant('create', 'hr_performance')) access_denied('hr_performance');
        if ($this->input->post()) {
            $result = $this->Performance_model->add($this->_post_data());
            if ($result['success']) {
                set_alert('success', $result['message']);
                redirect(admin_url('hr_module/performance/view/' . $result['id']));
            }
            set_alert('danger', $result['message']);
            redirect(admin_url('hr_module/performance/add'));
        }
        $data['title']    = _l('hr_performance_add');
        $data['review']   = null;
        $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        $data['reviewers'] = $this->_get_staff_dropdown();
        $this->load->view('hr_module/performance/form', $data);
    }

    public function edit($id)
    {
        if (staff_cant('edit', 'hr_performance')) access_denied('hr_performance');
        $review = $this->Performance_model->get($id);
        if (!$review) show_404();

        if ($this->input->post()) {
            $result = $this->Performance_model->update($this->_post_data(true), $id);
            set_alert($result['success'] ? 'success' : 'danger', $result['message']);
            redirect(admin_url('hr_module/performance/view/' . $id));
        }
        $data['title']     = _l('hr_performance_edit');
        $data['review']    = $review;
        $data['employees'] = $this->Hr_module_model->get_active_employees_dropdown();
        $data['reviewers'] = $this->_get_staff_dropdown();
        $this->load->view('hr_module/performance/form', $data);
    }

    public function view($id)
    {
        if (staff_cant('view', 'hr_performance')) access_denied('hr_performance');
        $review = $this->Performance_model->get($id);
        if (!$review) show_404();
        $data['title']  = _l('hr_performance_view');
        $data['review'] = $review;
        $this->load->view('hr_module/performance/view', $data);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'hr_performance')) access_denied('hr_performance');
        $result = $this->Performance_model->delete($id);
        set_alert($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(admin_url('hr_module/performance'));
    }

    private function _post_data($is_edit = false)
    {
        $data = [
            'employee_id'        => $this->input->post('employee_id'),
            'reviewer_id'        => $this->input->post('reviewer_id'),
            'review_period_from' => $this->input->post('review_period_from'),
            'review_period_to'   => $this->input->post('review_period_to'),
            'criteria'           => $this->input->post('criteria', true),
            'notes'              => $this->input->post('notes', true),
        ];
        if ($is_edit) {
            $data['self_assessment'] = $this->input->post('self_assessment', true);
            $data['manager_review']  = $this->input->post('manager_review', true);
            $data['final_score']     = $this->input->post('final_score');
        }
        return $data;
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
