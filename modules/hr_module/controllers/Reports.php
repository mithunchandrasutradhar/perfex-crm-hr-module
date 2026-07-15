<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Reports extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Reports_model');
        $this->load->model('hr_module/Departments_model');
        $this->load->model('hr_module/Hr_module_model');
    }

    public function index()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $data['title'] = _l('hr_reports');
        $this->load->view('hr_module/reports/index', $data);
    }

    public function attendance()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $f = $this->_get_filters(['employee_id','department_id','status','month','year']);
        if (empty($f['year'])) $f['year'] = date('Y');
        if (empty($f['month'])) $f['month'] = date('m');

        $rows    = $this->Reports_model->attendance($f);
        $summary = $this->Reports_model->attendance_summary($f);

        if ($this->input->get('export') === 'csv') {
            $this->_export_csv($rows, ['employee_code','first_name','last_name','department_name','attendance_date','status','in_time','out_time'], 'attendance_report');
            return;
        }
        $data['title']       = 'Attendance Report';
        $data['rows']        = $rows;
        $data['summary']     = $summary;
        $data['filters']     = $f;
        $data['departments'] = $this->Departments_model->get_active();
        $data['employees']   = $this->Hr_module_model->get_active_employees_dropdown();
        $this->load->view('hr_module/reports/attendance', $data);
    }

    public function leave()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $f = $this->_get_filters(['employee_id','department_id','status','leave_type_id','from_date','to_date']);

        $rows = $this->Reports_model->leave($f);
        $this->load->model('hr_module/Leave_model');
        $leave_types = $this->Leave_model->get_active_types();

        if ($this->input->get('export') === 'csv') {
            $this->_export_csv($rows, ['employee_code','first_name','last_name','department_name','leave_type_name','from_date','to_date','days_requested','status'], 'leave_report');
            return;
        }
        $data['title']       = 'Leave Report';
        $data['rows']        = $rows;
        $data['filters']     = $f;
        $data['departments'] = $this->Departments_model->get_active();
        $data['employees']   = $this->Hr_module_model->get_active_employees_dropdown();
        $data['leave_types'] = $leave_types;
        $this->load->view('hr_module/reports/leave', $data);
    }

    public function payroll()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $f = $this->_get_filters(['department_id','status','month','year']);
        if (empty($f['year'])) $f['year'] = date('Y');

        $rows = $this->Reports_model->payroll($f);

        if ($this->input->get('export') === 'csv') {
            $this->_export_csv($rows, ['employee_code','first_name','last_name','department_name','pay_month','pay_year','basic_salary','gross_earnings','total_deductions','net_salary','status'], 'payroll_report');
            return;
        }
        $totals = ['gross' => 0, 'deductions' => 0, 'net' => 0];
        foreach ($rows as $r) {
            $totals['gross']      += $r->gross_earnings ?? 0;
            $totals['deductions'] += $r->total_deductions ?? 0;
            $totals['net']        += $r->net_salary ?? 0;
        }
        $data['title']       = 'Payroll Report';
        $data['rows']        = $rows;
        $data['filters']     = $f;
        $data['totals']      = $totals;
        $data['departments'] = $this->Departments_model->get_active();
        $this->load->view('hr_module/reports/payroll', $data);
    }

    public function loan()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $f    = $this->_get_filters(['department_id','status']);
        $rows = $this->Reports_model->loans($f);

        if ($this->input->get('export') === 'csv') {
            $this->_export_csv($rows, ['employee_code','first_name','last_name','department_name','loan_amount','monthly_installment','outstanding','total_repaid','status','approved_date'], 'loan_report');
            return;
        }
        $total_amount      = array_sum(array_column((array) $rows, 'loan_amount'));
        $total_outstanding = array_sum(array_column((array) $rows, 'outstanding'));
        $data['title']          = 'Loan Report';
        $data['rows']           = $rows;
        $data['filters']        = $f;
        $data['total_amount']   = $total_amount;
        $data['total_outstanding'] = $total_outstanding;
        $data['departments']    = $this->Departments_model->get_active();
        $this->load->view('hr_module/reports/loan', $data);
    }

    public function overtime()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $f = $this->_get_filters(['department_id','status','from_date','to_date']);

        $rows = $this->Reports_model->overtime($f);

        if ($this->input->get('export') === 'csv') {
            $this->_export_csv($rows, ['employee_code','first_name','last_name','department_name','overtime_date','day_type','holiday_name','rate_multiplier','total_amount','status'], 'overtime_report');
            return;
        }
        $total_amount = array_sum(array_column((array) $rows, 'total_amount'));
        $data['title']        = 'Overtime Report';
        $data['rows']         = $rows;
        $data['filters']      = $f;
        $data['total_amount'] = $total_amount;
        $data['departments']  = $this->Departments_model->get_active();
        $this->load->view('hr_module/reports/overtime', $data);
    }

    public function performance()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $f = $this->_get_filters(['department_id','year','status']);
        if (empty($f['year'])) $f['year'] = date('Y');

        $view = in_array($this->input->get('view'), ['employee', 'department'], true) ? $this->input->get('view') : 'detailed';

        if ($view === 'employee') {
            $rows = $this->Reports_model->performance_by_employee($f);
            $csv_cols = ['employee_code','first_name','last_name','department_name','total_sub_targets','completed_count','pending_count','in_progress_count','partial_count','avg_completion','avg_rating'];
            $csv_name = 'performance_report_by_employee';
        } elseif ($view === 'department') {
            $rows = $this->Reports_model->performance_by_department($f);
            $csv_cols = ['department_name','total_sub_targets','completed_count','pending_count','in_progress_count','partial_count','avg_completion','avg_rating'];
            $csv_name = 'performance_report_by_department';
        } else {
            $rows = $this->Reports_model->performance($f);
            $csv_cols = ['employee_code','first_name','last_name','department_name','target_title','sub_target_title','assigned_by_name','evaluator_names','due_date','completion_percentage','status'];
            $csv_name = 'performance_report';
        }

        if ($this->input->get('export') === 'csv') {
            $this->_export_csv($rows, $csv_cols, $csv_name);
            return;
        }
        $data['title']       = 'Performance Report';
        $data['view']        = $view;
        $data['rows']        = $rows;
        $data['filters']     = $f;
        $data['departments'] = $this->Departments_model->get_active();
        $this->load->view('hr_module/reports/performance', $data);
    }

    public function training()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $f = $this->_get_filters(['status','year']);

        $rows = $this->Reports_model->training($f);

        if ($this->input->get('export') === 'csv') {
            $this->_export_csv($rows, ['title','instructor_name','start_date','end_date','capacity','enrolled','present','status'], 'training_report');
            return;
        }
        $data['title']   = 'Training Report';
        $data['rows']    = $rows;
        $data['filters'] = $f;
        $this->load->view('hr_module/reports/training', $data);
    }

    public function headcount()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $f    = $this->_get_filters(['department_id']);
        $rows = $this->Reports_model->headcount($f);

        $total = 0;
        foreach ($rows as $r) $total += $r->total;

        $data['title']   = 'Headcount Report';
        $data['rows']    = $rows;
        $data['total']   = $total;
        $data['filters'] = $f;
        $this->load->view('hr_module/reports/headcount', $data);
    }

    public function department()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $f = $this->_get_filters(['department_id','year']);
        if (empty($f['year'])) $f['year'] = date('Y');

        $result = $this->Reports_model->department($f);

        $data['title']       = 'Department Report';
        $data['employees']   = $result['employees'];
        $data['leave_map']   = $result['leave_map'];
        $data['payroll_map'] = $result['payroll_map'];
        $data['filters']     = $f;
        $data['departments'] = $this->Departments_model->get_active();
        $this->load->view('hr_module/reports/department', $data);
    }

    public function salary()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $f    = $this->_get_filters(['department_id']);
        $rows = $this->Reports_model->salary($f);
        $dept_summary = $this->Reports_model->salary_summary_by_dept($f);

        if ($this->input->get('export') === 'csv') {
            $this->_export_csv($rows, ['employee_code','first_name','last_name','department_name','designation_name','basic_salary','employment_type'], 'salary_report');
            return;
        }
        $data['title']        = 'Salary Report';
        $data['rows']         = $rows;
        $data['dept_summary'] = $dept_summary;
        $data['filters']      = $f;
        $data['departments']  = $this->Departments_model->get_active();
        $this->load->view('hr_module/reports/salary', $data);
    }

    public function turnover()
    {
        if (staff_cant('view', 'hr_reports')) access_denied('hr_reports');
        $f = $this->_get_filters(['year']);
        if (empty($f['year'])) $f['year'] = date('Y');

        $result = $this->Reports_model->turnover($f);

        $data['title']   = 'Employee Turnover Report';
        $data['result']  = $result;
        $data['filters'] = $f;
        $this->load->view('hr_module/reports/turnover', $data);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function _get_filters($keys)
    {
        $f = [];
        foreach ($keys as $k) {
            $v = $this->input->get($k);
            if ($v !== null && $v !== '') $f[$k] = $v;
        }
        return $f;
    }

    private function _export_csv($rows, $cols, $filename)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $cols);
        foreach ($rows as $row) {
            $line = [];
            foreach ($cols as $col) $line[] = $row->$col ?? '';
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }
}
