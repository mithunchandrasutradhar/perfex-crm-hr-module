<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Hr_module extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Hr_module_model');
    }

    public function index()
    {
        $data['title']  = _l('hr_dashboard_title');
        $data['stats']  = $this->Hr_module_model->get_dashboard_stats();
        $data['bodyclass'] = 'hr-module-dashboard';

        $this->load->view('hr_module/dashboard/index', $data);
    }
}
