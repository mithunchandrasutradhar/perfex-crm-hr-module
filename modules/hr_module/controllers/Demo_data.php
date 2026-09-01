<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Demo_data extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (!is_admin()) access_denied('demo_data');
    }

    // admin/hr_module/demo_data
    public function index()
    {
        $data['title'] = 'HR Demo Data Seeder';
        $this->load->view('hr_module/demo_data/index', $data);
    }

    // admin/hr_module/demo_data/status
    public function status()
    {
        $tables = [
            'hr_employees', 'hr_departments', 'hr_designations',
            'hr_leave_types', 'hr_leave_requests', 'hr_leave_balances',
            'hr_attendance', 'hr_payroll', 'hr_payroll_details',
            'hr_payroll_items', 'hr_loans', 'hr_loan_repayments',
            'hr_overtime', 'hr_performance_reviews',
            'hr_training', 'hr_training_participants',
            'hr_helpdesk', 'hr_helpdesk_replies', 'hr_contracts',
        ];
        $counts = [];
        foreach ($tables as $tbl) {
            $full = db_prefix() . $tbl;
            $counts[$tbl] = $this->db->table_exists($full)
                ? $this->db->count_all($full)
                : 'TABLE MISSING';
        }
        $demo_emp = $this->db->like('employee_code', 'DEMO-', 'after')
            ->count_all_results(db_prefix() . 'hr_employees');
        $talha_emp = $this->db->where('employee_code', 'ALPHA-EMP-001')
            ->count_all_results(db_prefix() . 'hr_employees');

        $data['title']     = 'HR Demo Data Status';
        $data['counts']    = $counts;
        $data['demo_emp']  = $demo_emp;
        $data['talha_emp'] = $talha_emp;
        $this->load->view('hr_module/demo_data/status', $data);
    }

    // admin/hr_module/demo_data/run
    public function run()
    {
        if (!is_admin()) access_denied('demo_data');

        $log    = [];
        $errors = [];
        $now    = date('Y-m-d H:i:s');
        $year   = (int) date('Y');

        // ---- Bail if already seeded ----------------------------------------
        $existing = $this->db->like('employee_code', 'DEMO-', 'after')
            ->count_all_results(db_prefix() . 'hr_employees');
        $talha_existing = $this->db->where('employee_code', 'ALPHA-EMP-001')
            ->count_all_results(db_prefix() . 'hr_employees');
        if ($existing > 0 || $talha_existing > 0) {
            $data['log']     = ['[WARN] Demo data already seeded. Use Reset to wipe and re-seed.'];
            $data['success'] = false;
            $data['title']   = 'HR Demo Data Seeder';
            $this->load->view('hr_module/demo_data/result', $data);
            return;
        }

        // ---- Fetch or create departments -----------------------------------
        $dept_rows = $this->db->select('id, name')->get(db_prefix() . 'hr_departments')->result();
        if (empty($dept_rows)) {
            $dept_names = ['Engineering', 'Human Resources', 'Finance', 'Design', 'Sales'];
            foreach ($dept_names as $dn) {
                $this->db->insert(db_prefix() . 'hr_departments', [
                    'name'       => $dn,
                    'status'     => 1,
                    'created_at' => $now,
                ]);
            }
            $dept_rows = $this->db->select('id, name')->get(db_prefix() . 'hr_departments')->result();
            $log[] = '[OK] Created ' . count($dept_rows) . ' departments';
        } else {
            $log[] = '[OK] Using ' . count($dept_rows) . ' existing departments';
        }
        $dept_ids = array_column($dept_rows, 'id');

        // ---- Designations (independent of department) ------------------------
        $desig_names = ['HR Manager', 'Software Engineer', 'Senior Accountant', 'UI/UX Designer', 'Sales Executive'];
        $desig_ids = [];
        foreach ($desig_names as $dn) {
            $row = $this->db->where('name', $dn)->get(db_prefix() . 'hr_designations')->row();
            if ($row) {
                $desig_ids[$dn] = $row->id;
            } else {
                $this->db->insert(db_prefix() . 'hr_designations', [
                    'name'       => $dn,
                    'status'     => 1,
                    'created_at' => $now,
                ]);
                $err = $this->db->error();
                if (!empty($err['code'])) {
                    $errors[] = 'Designation insert failed: ' . $dn . ' -- ' . $err['message'];
                    $desig_ids[$dn] = null;
                } else {
                    $desig_ids[$dn] = $this->db->insert_id();
                    $log[] = '[OK] Created designation: ' . $dn;
                }
            }
        }

        // ====================================================================
        // SECTION 1: TALHA (talha@alpha.net.bd) -- real staff linked employee
        // ====================================================================
        $talha_sid = 0;
        $talha_eid = 0;
        $talha_staff = $this->db->where('email', 'talha@alpha.net.bd')->get(db_prefix() . 'staff')->row();
        if (!$talha_staff) {
            $errors[] = '[WARN] Staff account talha@alpha.net.bd not found. Will create a placeholder staff account.';
            $this->db->insert(db_prefix() . 'staff', [
                'firstname'    => 'Talha',
                'lastname'     => 'Ahmed',
                'email'        => 'talha@alpha.net.bd',
                'password'     => app_hash_password('Alpha@1234'),
                'active'       => 1,
                'admin'        => 0,
                'datecreated'  => $now,
                'is_not_staff' => 0,
            ]);
            $talha_sid = $this->db->insert_id();
            $log[] = '[OK] Created staff account for talha@alpha.net.bd (password: Alpha@1234)';
        } else {
            $talha_sid = $talha_staff->staffid;
            $log[] = '[OK] Found existing staff: talha@alpha.net.bd (staff ID ' . $talha_sid . ')';
        }

        // Create employee record for Talha
        $this->db->insert(db_prefix() . 'hr_employees', [
            'employee_code'          => 'ALPHA-EMP-001',
            'staff_id'               => $talha_sid,
            'first_name'             => 'Talha',
            'last_name'              => 'Ahmed',
            'email'                  => 'talha@alpha.net.bd',
            'phone'                  => '+880 1711-000001',
            'gender'                 => 'Male',
            'date_of_birth'          => '1992-03-15',
            'department_id'          => !empty($dept_ids[0]) ? $dept_ids[0] : null,
            'designation_id'         => $desig_ids['HR Manager'] ?? null,
            'joining_date'           => date('Y-m-d', strtotime('-18 months')),
            'basic_salary'           => 85000,
            'blood_group'            => 'B+',
            'marital_status'         => 'married',
            'bank_name'              => 'Dutch-Bangla Bank Ltd.',
            'bank_account'           => '2100' . str_pad(9999, 8, '0', STR_PAD_LEFT),
            'tin_number'             => 'TIN000099999',
            'nid_number'             => '1988000000001',
            'emergency_contact_name'  => 'Fatima Ahmed',
            'emergency_contact_phone' => '+880 1811-000001',
            'status'                 => 1,
            'created_at'             => $now,
        ]);
        $talha_err = $this->db->error();
        if (!empty($talha_err['code'])) {
            $errors[] = 'Employee insert failed for Talha: ' . $talha_err['message'];
        } else {
            $talha_eid = $this->db->insert_id();
            $log[] = '[OK] Created employee: Talha Ahmed (ALPHA-EMP-001, employee ID ' . $talha_eid . ')';
        }

        // ====================================================================
        // SECTION 2: 4 DEMO staff + employees
        // ====================================================================
        $demo_people = [
            ['firstname' => 'Alice',  'lastname' => 'Johnson',  'email' => 'alice.johnson@demo.hr',  'salary' => 75000, 'gender' => 'Female', 'blood' => 'A+',  'marital' => 'married',  'desig' => 'Software Engineer', 'dept_idx' => 0, 'dob' => '1990-07-22', 'join_months' => 24],
            ['firstname' => 'Bob',    'lastname' => 'Smith',    'email' => 'bob.smith@demo.hr',      'salary' => 65000, 'gender' => 'Male',   'blood' => 'O+',  'marital' => 'single',   'desig' => 'Senior Accountant', 'dept_idx' => 2, 'dob' => '1988-11-05', 'join_months' => 36],
            ['firstname' => 'Carol',  'lastname' => 'Williams', 'email' => 'carol.williams@demo.hr', 'salary' => 60000, 'gender' => 'Female', 'blood' => 'AB+', 'marital' => 'married',  'desig' => 'UI/UX Designer',   'dept_idx' => 3, 'dob' => '1995-02-18', 'join_months' => 12],
            ['firstname' => 'David',  'lastname' => 'Brown',    'email' => 'david.brown@demo.hr',    'salary' => 55000, 'gender' => 'Male',   'blood' => 'A-',  'marital' => 'single',   'desig' => 'Sales Executive',   'dept_idx' => 4, 'dob' => '1993-09-30', 'join_months' => 8],
        ];

        $demo_staff_ids = [];
        $demo_emp_ids   = [];
        foreach ($demo_people as $i => $p) {
            // Staff account
            $row = $this->db->where('email', $p['email'])->get(db_prefix() . 'staff')->row();
            if ($row) {
                $demo_staff_ids[] = $row->staffid;
                $log[] = '[OK] Staff already exists: ' . $p['email'];
            } else {
                $this->db->insert(db_prefix() . 'staff', [
                    'firstname'    => $p['firstname'],
                    'lastname'     => $p['lastname'],
                    'email'        => $p['email'],
                    'password'     => app_hash_password('Demo@1234'),
                    'active'       => 1,
                    'admin'        => 0,
                    'datecreated'  => $now,
                    'is_not_staff' => 0,
                ]);
                $err = $this->db->error();
                if (!empty($err['code'])) {
                    $errors[] = 'Staff insert failed for ' . $p['email'] . ': ' . $err['message'];
                    $demo_staff_ids[] = 0;
                    continue;
                }
                $demo_staff_ids[] = $this->db->insert_id();
                $log[] = '[OK] Created staff: ' . $p['firstname'] . ' ' . $p['lastname'] . ' (Demo@1234)';
            }

            // Employee record
            $code = 'DEMO-EMP-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            $sid  = end($demo_staff_ids);
            $this->db->insert(db_prefix() . 'hr_employees', [
                'employee_code'          => $code,
                'staff_id'               => $sid,
                'first_name'             => $p['firstname'],
                'last_name'              => $p['lastname'],
                'email'                  => $p['email'],
                'phone'                  => '+880 1700-' . str_pad(100001 + $i, 6, '0', STR_PAD_LEFT),
                'gender'                 => $p['gender'],
                'date_of_birth'          => $p['dob'],
                'department_id'          => !empty($dept_ids[$p['dept_idx']]) ? $dept_ids[$p['dept_idx']] : null,
                'designation_id'         => $desig_ids[$p['desig']] ?? null,
                'joining_date'           => date('Y-m-d', strtotime('-' . $p['join_months'] . ' months')),
                'basic_salary'           => $p['salary'],
                'blood_group'            => $p['blood'],
                'marital_status'         => $p['marital'],
                'bank_name'              => 'Dutch-Bangla Bank Ltd.',
                'bank_account'           => '2100' . str_pad($i + 1, 8, '0', STR_PAD_LEFT),
                'tin_number'             => 'TIN' . str_pad(1000 + $i, 8, '0', STR_PAD_LEFT),
                'nid_number'             => '19' . str_pad(8800000000 + $i * 111111, 10, '0', STR_PAD_LEFT),
                'emergency_contact_name'  => 'Contact of ' . $p['firstname'],
                'emergency_contact_phone' => '+880 1800-' . str_pad(200001 + $i, 6, '0', STR_PAD_LEFT),
                'status'                 => 1,
                'created_at'             => $now,
            ]);
            $err = $this->db->error();
            if (!empty($err['code'])) {
                $errors[] = 'Employee insert failed for ' . $code . ': ' . $err['message'];
                $demo_emp_ids[] = 0;
            } else {
                $eid = $this->db->insert_id();
                $demo_emp_ids[] = $eid;
                $log[] = '[OK] Created employee: ' . $p['firstname'] . ' ' . $p['lastname'] . ' (' . $code . ')';
            }
        }

        // Full employee list: Talha first, then demos
        $all_emp = array_merge(
            $talha_eid ? [$talha_eid] : [],
            array_filter($demo_emp_ids)
        );
        // Map index → person data (Talha at 0, demos at 1-4)
        $all_people = array_merge(
            [['firstname' => 'Talha', 'lastname' => 'Ahmed', 'salary' => 85000]],
            $demo_people
        );

        if (empty($all_emp)) {
            $errors[] = 'FATAL: No employees were created. Aborting.';
            $data['log']     = array_merge($log, $errors);
            $data['success'] = false;
            $data['title']   = 'HR Demo Data Seeder';
            $this->load->view('hr_module/demo_data/result', $data);
            return;
        }

        // ====================================================================
        // LEAVE TYPES
        // ====================================================================
        $lt_rows = $this->db->select('id, name, days_per_year')
            ->get(db_prefix() . 'hr_leave_types')->result();
        if (empty($lt_rows)) {
            $lt_defs = [
                ['Annual Leave', 15, 1, 0, 1],
                ['Sick Leave',   14, 0, 1, 1],
                ['Casual Leave', 10, 0, 0, 1],
                ['Maternity Leave', 120, 0, 1, 0],
                ['Paternity Leave',  5,  0, 0, 0],
                ['Unpaid Leave',     0,  0, 0, 1],
            ];
            foreach ($lt_defs as $lt) {
                $this->db->insert(db_prefix() . 'hr_leave_types', [
                    'name'                => $lt[0],
                    'days_per_year'       => $lt[1],
                    'carry_forward'       => $lt[2],
                    'requires_attachment' => $lt[3],
                    'allow_half_day'      => $lt[4],
                    'status'              => 1,
                    'created_at'          => $now,
                ]);
            }
            $lt_rows = $this->db->select('id, name, days_per_year')
                ->get(db_prefix() . 'hr_leave_types')->result();
            $log[] = '[OK] Created ' . count($lt_rows) . ' leave types';
        } else {
            $log[] = '[OK] Using ' . count($lt_rows) . ' existing leave types';
        }

        // ====================================================================
        // LEAVE BALANCES  (current year, with realistic used_days for some)
        // ====================================================================
        $bal_count = 0;
        $used_matrix = [
            // emp_index => [lt_index => used_days]
            0 => [0 => 3, 1 => 2, 2 => 1],
            1 => [0 => 5, 1 => 4, 2 => 2],
            2 => [0 => 2, 1 => 0, 2 => 3],
            3 => [0 => 7, 1 => 6, 2 => 0],
            4 => [0 => 1, 1 => 0, 2 => 1],
        ];
        foreach ($all_emp as $ei => $eid) {
            foreach ($lt_rows as $li => $lt) {
                $exists = $this->db->where('employee_id', $eid)
                    ->where('leave_type_id', $lt->id)
                    ->where('year', $year)
                    ->count_all_results(db_prefix() . 'hr_leave_balances');
                if ($exists) continue;
                $used = $used_matrix[$ei][$li] ?? 0;
                $this->db->insert(db_prefix() . 'hr_leave_balances', [
                    'employee_id'        => $eid,
                    'leave_type_id'      => $lt->id,
                    'year'               => $year,
                    'allocated_days'     => $lt->days_per_year,
                    'used_days'          => $used,
                    'carry_forward_days' => ($li === 0 && $ei < 3) ? 2 : 0,
                    'created_at'         => $now,
                ]);
                $bal_count++;
            }
        }
        $log[] = '[OK] Created ' . $bal_count . ' leave balance records';

        // ====================================================================
        // LEAVE REQUESTS  (varied statuses, realistic dates)
        // ====================================================================
        $leave_data = [
            // [leave_type_idx, days, offset_from, status, reason]
            [0, 3, -45, 'approved', 'Annual vacation trip'],
            [1, 2, -30, 'approved', 'Fever and flu'],
            [2, 1, -15, 'approved', 'Personal errands'],
            [0, 5, -5,  'pending',  'Upcoming family event'],
            [1, 1, 10,  'pending',  'Scheduled medical checkup'],
            [0, 2, -90, 'rejected', 'Extended leave request'],
            [2, 1, -60, 'approved', 'Half-day for banking work'],
        ];
        $leave_count = 0;
        foreach ($all_emp as $ei => $eid) {
            foreach ($leave_data as $li => $ld) {
                $lt_idx = $ld[0] % count($lt_rows);
                $ltid   = $lt_rows[$lt_idx]->id;
                $from   = date('Y-m-d', strtotime($ld[2] . ' days'));
                $to     = date('Y-m-d', strtotime($from . ' +' . ($ld[1] - 1) . ' days'));
                // Stagger employees so dates don't clash
                $from = date('Y-m-d', strtotime($from . ' +' . ($ei * 2) . ' days'));
                $to   = date('Y-m-d', strtotime($from . ' +' . ($ld[1] - 1) . ' days'));
                $this->db->insert(db_prefix() . 'hr_leave_requests', [
                    'employee_id'   => $eid,
                    'leave_type_id' => $ltid,
                    'from_date'     => $from,
                    'to_date'       => $to,
                    'total_days'    => $ld[1],
                    'is_half_day'   => 0,
                    'reason'        => $ld[4],
                    'status'        => $ld[3],
                    'approved_by'   => ($ld[3] === 'approved') ? get_staff_user_id() : null,
                    'approved_at'   => ($ld[3] === 'approved') ? date('Y-m-d H:i:s', strtotime($from . ' -1 day')) : null,
                    'rejection_reason' => ($ld[3] === 'rejected') ? 'Insufficient leave balance for this period' : null,
                    'created_at'    => $now,
                ]);
                $leave_count++;
            }
        }
        $log[] = '[OK] Created ' . $leave_count . ' leave requests (' . count($all_emp) . ' employees x 7 requests each)';

        // ====================================================================
        // ATTENDANCE  (last 35 calendar days, skip weekends)
        // ====================================================================
        $att_patterns = [
            // Talha: mostly present, occasional late
            [0 => ['present','present','present','present','late','present','present']],
            // Alice: excellent attendance
            [0 => ['present','present','present','present','present','present','present']],
            // Bob: some absences
            [0 => ['present','present','absent','present','present','late','present']],
            // Carol: half days on Fridays
            [0 => ['present','present','present','present','present','half_day','present']],
            // David: irregular
            [0 => ['present','absent','present','present','late','present','absent']],
        ];
        $att_cycle = [
            ['present','present','present','present','late','present','present'],
            ['present','present','present','present','present','present','present'],
            ['present','present','absent','present','present','late','present'],
            ['present','present','present','present','present','half_day','present'],
            ['present','absent','present','present','late','present','absent'],
        ];
        $att_count = 0;
        for ($d = 0; $d < 35; $d++) {
            $att_date = date('Y-m-d', strtotime('-' . $d . ' days'));
            $dow = (int) date('N', strtotime($att_date)); // 1=Mon..7=Sun
            if ($dow >= 6) continue; // skip Sat/Sun
            foreach ($all_emp as $ei => $eid) {
                $cycle   = $att_cycle[$ei % count($att_cycle)];
                $status  = $cycle[$d % count($cycle)];
                $in_map  = ['present' => '09:02:00', 'late' => '09:42:00', 'half_day' => '13:00:00', 'absent' => null];
                $out_map = ['present' => '18:00:00', 'late' => '18:00:00', 'half_day' => '18:00:00', 'absent' => null];
                $hrs_map = ['present' => 8.97, 'late' => 8.30, 'half_day' => 5.00, 'absent' => null];
                $this->db->insert(db_prefix() . 'hr_attendance', [
                    'employee_id'     => $eid,
                    'attendance_date' => $att_date,
                    'in_time'         => $in_map[$status],
                    'out_time'        => $out_map[$status],
                    'working_hours'   => $hrs_map[$status],
                    'status'          => $status,
                    'source'          => 'manual',
                    'created_at'      => $now,
                ]);
                $att_count++;
            }
        }
        $log[] = '[OK] Created ' . $att_count . ' attendance records (last 35 days, weekdays only)';

        // ====================================================================
        // PAYROLL ITEMS
        // ====================================================================
        $p_items = [
            ['House Rent Allowance', 'allowance', 'percentage', 40,   0],
            ['Medical Allowance',    'allowance', 'fixed',      1500, 0],
            ['Transport Allowance',  'allowance', 'fixed',      1000, 0],
            ['Festival Bonus',       'allowance', 'fixed',      5000, 0],
            ['Mobile Allowance',     'allowance', 'fixed',       500, 0],
            ['Provident Fund',       'deduction', 'percentage', 10,   0],
            ['Income Tax',           'deduction', 'percentage',  5,   1],
            ['Late Deduction',       'deduction', 'fixed',       100, 0],
        ];
        foreach ($p_items as $pi) {
            if (!$this->db->where('name', $pi[0])->count_all_results(db_prefix() . 'hr_payroll_items')) {
                $this->db->insert(db_prefix() . 'hr_payroll_items', [
                    'name'             => $pi[0],
                    'type'             => $pi[1],
                    'calculation_type' => $pi[2],
                    'value'            => $pi[3],
                    'taxable'          => $pi[4],
                    'status'           => 1,
                    'created_at'       => $now,
                ]);
            }
        }
        $log[] = '[OK] Ensured ' . count($p_items) . ' payroll items exist';

        // ====================================================================
        // PAYROLL  (last 4 months)
        // ====================================================================
        $payroll_count = 0;
        for ($m = 1; $m <= 4; $m++) {
            $pay_month  = (int) date('n', strtotime('-' . $m . ' months'));
            $pay_year   = (int) date('Y', strtotime('-' . $m . ' months'));
            $pay_status = ($m === 1) ? 'approved' : 'paid';
            $pay_date   = ($m > 1)  ? date('Y-m-05', strtotime('-' . $m . ' months')) : null;

            foreach ($all_emp as $ei => $eid) {
                $basic  = (float) $all_people[$ei]['salary'];
                $hra    = $basic * 0.40;
                $med    = 1500.00;
                $trans  = 1000.00;
                $mobile = 500.00;
                $gross  = $basic + $hra + $med + $trans + $mobile;
                $pf     = $basic * 0.10;
                $tax    = $gross * 0.05;
                $late   = ($ei === 2 || $ei === 4) ? 200.00 : 0.00; // Bob and David have late deductions
                $net    = $gross - $pf - $tax - $late;

                $this->db->insert(db_prefix() . 'hr_payroll', [
                    'employee_id'      => $eid,
                    'pay_month'        => $pay_month,
                    'pay_year'         => $pay_year,
                    'basic_salary'     => $basic,
                    'total_allowances' => $hra + $med + $trans + $mobile,
                    'total_deductions' => $pf + $tax + $late,
                    'gross_salary'     => $gross,
                    'net_salary'       => $net,
                    'tax'              => $tax,
                    'working_days'     => 22,
                    'present_days'     => 22 - (($ei === 2 || $ei === 4) ? 2 : 0),
                    'absent_days'      => ($ei === 2 || $ei === 4) ? 2 : 0,
                    'status'           => $pay_status,
                    'payment_method'   => 'bank_transfer',
                    'payment_date'     => $pay_date,
                    'approved_by'      => get_staff_user_id(),
                    'approved_at'      => date('Y-m-d H:i:s', strtotime('-' . $m . ' months')),
                    'created_at'       => $now,
                ]);
                $pid = $this->db->insert_id();
                if ($pid) {
                    $details = [
                        ['House Rent Allowance', 'allowance', $hra],
                        ['Medical Allowance',    'allowance', $med],
                        ['Transport Allowance',  'allowance', $trans],
                        ['Mobile Allowance',     'allowance', $mobile],
                        ['Provident Fund',       'deduction', $pf],
                        ['Income Tax',           'deduction', $tax],
                    ];
                    if ($late > 0) {
                        $details[] = ['Late Deduction', 'deduction', $late];
                    }
                    foreach ($details as $det) {
                        $this->db->insert(db_prefix() . 'hr_payroll_details', [
                            'payroll_id' => $pid,
                            'item_name'  => $det[0],
                            'item_type'  => $det[1],
                            'amount'     => $det[2],
                        ]);
                    }
                    $payroll_count++;
                }
            }
        }
        $log[] = '[OK] Created ' . $payroll_count . ' payroll records (4 months x ' . count($all_emp) . ' employees)';

        // ====================================================================
        // LOANS  (varied statuses, Talha has active loan with repayments)
        // ====================================================================
        $loan_scenarios = [
            // Talha: active loan, 3 repayments made
            ['amount' => 120000, 'months' => 12, 'reason' => 'Home renovation and furnishing', 'status' => 'active', 'repayments' => 3],
            // Alice: approved, disbursed, no repayments yet
            ['amount' =>  60000, 'months' => 6,  'reason' => 'Higher education fees',         'status' => 'active', 'repayments' => 1],
            // Bob: pending approval
            ['amount' =>  80000, 'months' => 8,  'reason' => 'Medical emergency expenses',    'status' => 'pending', 'repayments' => 0],
            // Carol: closed loan
            ['amount' =>  40000, 'months' => 4,  'reason' => 'Vehicle purchase down payment', 'status' => 'closed', 'repayments' => 4],
            // David: rejected
            ['amount' =>  50000, 'months' => 6,  'reason' => 'Personal investment',           'status' => 'rejected', 'repayments' => 0],
        ];
        $loan_count = 0;
        $loan_ids   = [];
        foreach ($all_emp as $ei => $eid) {
            $ls          = $loan_scenarios[$ei % count($loan_scenarios)];
            $installment = round($ls['amount'] / $ls['months'], 2);
            $repaid      = ($ls['status'] === 'closed') ? $ls['amount'] : round($installment * $ls['repayments'], 2);
            $outstanding = max(0, $ls['amount'] - $repaid);
            $this->db->insert(db_prefix() . 'hr_loans', [
                'employee_id'         => $eid,
                'amount'              => $ls['amount'],
                'reason'              => $ls['reason'],
                'repayment_months'    => $ls['months'],
                'monthly_installment' => $installment,
                'total_repaid'        => $repaid,
                'outstanding'         => $outstanding,
                'disbursement_date'   => in_array($ls['status'], ['active', 'closed']) ? date('Y-m-d', strtotime('-' . ($ls['repayments'] + 1) . ' months')) : null,
                'status'              => $ls['status'],
                'approved_by'         => in_array($ls['status'], ['active', 'closed']) ? get_staff_user_id() : null,
                'approved_at'         => in_array($ls['status'], ['active', 'closed']) ? date('Y-m-d H:i:s', strtotime('-' . ($ls['repayments'] + 1) . ' months')) : null,
                'rejection_reason'    => ($ls['status'] === 'rejected') ? 'Loan policy limit exceeded. Please reapply with a lower amount.' : null,
                'created_at'          => $now,
            ]);
            $err = $this->db->error();
            if (!empty($err['code'])) {
                $errors[] = 'Loan insert failed: ' . $err['message'];
                $loan_ids[] = 0;
            } else {
                $lid = $this->db->insert_id();
                $loan_ids[] = $lid;
                // Add repayment records
                for ($r = 0; $r < $ls['repayments']; $r++) {
                    $rep_date = date('Y-m-d', strtotime('-' . ($ls['repayments'] - $r) . ' months'));
                    $this->db->insert(db_prefix() . 'hr_loan_repayments', [
                        'loan_id'        => $lid,
                        'amount'         => $installment,
                        'repayment_date' => $rep_date,
                        'notes'          => 'Monthly installment ' . ($r + 1) . ' of ' . $ls['months'],
                        'created_at'     => $now,
                    ]);
                }
                $loan_count++;
            }
        }
        $log[] = '[OK] Created ' . $loan_count . ' loans with repayment records';

        // ====================================================================
        // OVERTIME  (day-based: weekend entries over the last ~6 weeks per employee)
        // ====================================================================
        $ot_reasons    = ['Project deadline delivery', 'Client presentation preparation', 'Emergency server maintenance', 'Month-end report preparation', 'Product launch support'];
        $ot_multiplier = 1.5;
        $ot_count      = 0;
        foreach ($all_emp as $ei => $eid) {
            $daily_rate = $all_people[$ei]['salary'] / 26;
            $ot_entries = [
                ['weeks_ago' => 1, 'status' => 'approved'],
                ['weeks_ago' => 2, 'status' => 'approved'],
                ['weeks_ago' => 3, 'status' => 'approved'],
                ['weeks_ago' => 4, 'status' => 'approved'],
                ['weeks_ago' => 5, 'status' => 'pending'],
                ['weeks_ago' => 6, 'status' => 'rejected'],
            ];
            foreach ($ot_entries as $oi => $ot) {
                // Land on the nearest weekly-off day (Friday, dow=5) in that week, matching
                // the module's default "weekly_off_days" setting, so it's a valid overtime date.
                $anchor_ts = strtotime('-' . (($ot['weeks_ago'] * 7) + $ei) . ' days');
                $dow       = (int) date('w', $anchor_ts);
                $back_days = ($dow - 5 + 7) % 7;
                $ot_date   = date('Y-m-d', strtotime('-' . $back_days . ' days', $anchor_ts));

                $this->db->insert(db_prefix() . 'hr_overtime', [
                    'employee_id'     => $eid,
                    'overtime_date'   => $ot_date,
                    'day_type'        => 'weekend',
                    'rate_multiplier' => $ot_multiplier,
                    'total_amount'    => round($daily_rate * $ot_multiplier, 2),
                    'reason'          => $ot_reasons[($ei + $oi) % count($ot_reasons)],
                    'status'          => $ot['status'],
                    'approved_by'     => ($ot['status'] === 'approved') ? get_staff_user_id() : null,
                    'approved_at'     => ($ot['status'] === 'approved') ? date('Y-m-d H:i:s', strtotime($ot_date)) : null,
                    'rejection_reason'=> ($ot['status'] === 'rejected') ? 'Overduty not pre-approved by department head.' : null,
                    'created_at'      => $now,
                ]);
                $ot_count++;
            }
        }
        $log[] = '[OK] Created ' . $ot_count . ' overduty records (' . count($all_emp) . ' employees x 6 entries)';

        // ====================================================================
        // PERFORMANCE REVIEWS  (previous year: completed; current year: in_progress)
        // ====================================================================
        $admin_sid   = get_staff_user_id();
        $ratings_map = ['Poor', 'Average', 'Good', 'Very Good', 'Excellent'];
        $perf_count  = 0;
        foreach ($all_emp as $ei => $eid) {
            // Previous year — completed review
            $score_prev = 65 + ($ei * 6);
            $rating_prev = $ratings_map[min(4, (int) floor(($score_prev - 40) / 12))];
            $this->db->insert(db_prefix() . 'hr_performance_reviews', [
                'employee_id'        => $eid,
                'reviewer_id'        => $admin_sid,
                'review_period_from' => ($year - 1) . '-01-01',
                'review_period_to'   => ($year - 1) . '-12-31',
                'criteria'           => json_encode([
                    'Communication'   => 7 + ($ei % 3),
                    'Technical Skills'=> 8 + ($ei % 2),
                    'Teamwork'        => 7 + ($ei % 3),
                    'Punctuality'     => 6 + ($ei % 4),
                    'Productivity'    => 8,
                ]),
                'self_assessment' => 'I have consistently met my KPIs, supported team objectives, and contributed to cross-functional projects throughout the year.',
                'manager_review'  => 'Strong performer. Demonstrates initiative and good communication. Recommended for salary revision.',
                'final_score'     => $score_prev,
                'rating'          => $rating_prev,
                'status'          => 'completed',
                'created_at'      => $now,
            ]);
            $perf_count++;

            // Current year — in_progress review
            $this->db->insert(db_prefix() . 'hr_performance_reviews', [
                'employee_id'        => $eid,
                'reviewer_id'        => $admin_sid,
                'review_period_from' => $year . '-01-01',
                'review_period_to'   => $year . '-06-30',
                'criteria'           => json_encode([
                    'Communication'   => 8,
                    'Technical Skills'=> 9,
                    'Teamwork'        => 8,
                    'Punctuality'     => 7 + ($ei % 2),
                    'Productivity'    => 8 + ($ei % 2),
                ]),
                'self_assessment' => 'Working on several key projects this quarter. Completed training on new tools. Aiming to exceed last year\'s performance.',
                'manager_review'  => null,
                'final_score'     => null,
                'rating'          => null,
                'status'          => ($ei === 0) ? 'in_progress' : 'pending',
                'created_at'      => $now,
            ]);
            $perf_count++;
        }
        $log[] = '[OK] Created ' . $perf_count . ' performance reviews (2 per employee: 1 completed + 1 current)';

        // ====================================================================
        // TRAINING SESSIONS + ENROLLMENTS
        // ====================================================================
        $trainings = [
            [
                'title'    => 'Laravel & Modern PHP Development',
                'trainer'  => 'Md. Rakibul Islam',
                'venue'    => 'Conference Room A, Floor 3',
                'offset_s' => '+14 days',
                'offset_e' => '+16 days',
                'cost'     => 8000,
                'cap'      => 15,
                'status'   => 'scheduled',
                'desc'     => 'Advanced PHP framework training covering Laravel 10, REST APIs, and best practices.',
            ],
            [
                'title'    => 'HR Compliance & Labour Law ' . $year,
                'trainer'  => 'Sara Hossain (Legal Advisor)',
                'venue'    => 'Training Hall, Floor 2',
                'offset_s' => '-20 days',
                'offset_e' => '-18 days',
                'cost'     => 5000,
                'cap'      => 20,
                'status'   => 'completed',
                'desc'     => 'Annual HR compliance training on Bangladesh Labour Law, employee rights and payroll regulations.',
            ],
            [
                'title'    => 'Leadership & Team Management',
                'trainer'  => 'Michael Thompson (Management Consultant)',
                'venue'    => 'Board Room, Executive Floor',
                'offset_s' => '-5 days',
                'offset_e' => '+2 days',
                'cost'     => 12000,
                'cap'      => 10,
                'status'   => 'in_progress',
                'desc'     => 'Intensive leadership programme covering team management, conflict resolution, and strategic thinking.',
            ],
            [
                'title'    => 'Data Security & Cybersecurity Awareness',
                'trainer'  => 'Farhan Kabir (IT Security Specialist)',
                'venue'    => 'IT Lab, Floor 1',
                'offset_s' => '+30 days',
                'offset_e' => '+30 days',
                'cost'     => 3000,
                'cap'      => 30,
                'status'   => 'scheduled',
                'desc'     => 'Essential cybersecurity awareness session covering phishing, data protection and secure practices.',
            ],
        ];
        $tr_ids = [];
        foreach ($trainings as $t) {
            $this->db->insert(db_prefix() . 'hr_training', [
                'title'       => $t['title'],
                'trainer'     => $t['trainer'],
                'venue'       => $t['venue'],
                'start_date'  => date('Y-m-d', strtotime($t['offset_s'])),
                'end_date'    => date('Y-m-d', strtotime($t['offset_e'])),
                'cost'        => $t['cost'],
                'capacity'    => $t['cap'],
                'description' => $t['desc'],
                'status'      => $t['status'],
                'created_at'  => $now,
            ]);
            $tr_ids[] = $this->db->insert_id();
        }
        $enroll_count = 0;
        foreach ($tr_ids as $ti => $tid) {
            if (!$tid) continue;
            $completed_tid = ($ti === 1); // only the completed training has completion records
            foreach ($all_emp as $eid) {
                $this->db->insert(db_prefix() . 'hr_training_participants', [
                    'training_id'     => $tid,
                    'employee_id'     => $eid,
                    'enrolled_at'     => $now,
                    'completed'       => $completed_tid ? 1 : 0,
                    'completion_date' => $completed_tid ? date('Y-m-d', strtotime('-18 days')) : null,
                ]);
                $enroll_count++;
            }
        }
        $log[] = '[OK] Created ' . count(array_filter($tr_ids)) . ' training sessions, ' . $enroll_count . ' enrollments';

        // ====================================================================
        // HELPDESK TICKETS  (multiple per employee, with replies)
        // ====================================================================
        $hd_templates = [
            ['subject' => 'Salary slip for last month not received',    'cat' => 'Payroll',    'priority' => 'high',   'status' => 'open',        'reply' => null],
            ['subject' => 'Leave balance not updated after approved leave','cat' => 'Leave',    'priority' => 'medium', 'status' => 'in_progress', 'reply' => 'We are reviewing your leave records. Will update within 24 hours.'],
            ['subject' => 'Attendance marked absent on public holiday',  'cat' => 'Attendance', 'priority' => 'medium', 'status' => 'closed',      'reply' => 'The attendance record has been corrected. Please check your dashboard.'],
            ['subject' => 'Request for experience letter',               'cat' => 'Policy',     'priority' => 'low',    'status' => 'in_progress', 'reply' => 'Experience letter will be issued within 3 working days. Please visit HR.'],
            ['subject' => 'Loan EMI amount mismatch in payslip',         'cat' => 'Payroll',    'priority' => 'high',   'status' => 'open',        'reply' => null],
            ['subject' => 'Query about provident fund statement',        'cat' => 'Benefits',   'priority' => 'low',    'status' => 'closed',      'reply' => 'PF statement has been emailed to your registered email address.'],
        ];
        $hd_count = 0;
        foreach ($all_emp as $ei => $eid) {
            // Each employee gets 3 tickets (rotated through templates)
            for ($ti = 0; $ti < 3; $ti++) {
                $tpl = $hd_templates[($ei * 3 + $ti) % count($hd_templates)];
                $this->db->insert(db_prefix() . 'hr_helpdesk', [
                    'employee_id' => $eid,
                    'subject'     => $tpl['subject'],
                    'category'    => $tpl['cat'],
                    'priority'    => $tpl['priority'],
                    'message'     => 'I am writing to request assistance regarding: ' . $tpl['subject'] . '. Kindly look into this matter at your earliest convenience and provide an update. Thank you.',
                    'status'      => $tpl['status'],
                    'assigned_to' => $admin_sid,
                    'created_at'  => date('Y-m-d H:i:s', strtotime('-' . (($ei * 3 + $ti) * 3 + 1) . ' days')),
                ]);
                $ticket_id = $this->db->insert_id();
                $hd_count++;
                if ($ticket_id && $tpl['reply']) {
                    $this->db->insert(db_prefix() . 'hr_helpdesk_replies', [
                        'ticket_id'  => $ticket_id,
                        'staff_id'   => $admin_sid,
                        'message'    => $tpl['reply'],
                        'created_at' => date('Y-m-d H:i:s', strtotime('-' . (($ei * 3 + $ti) * 3) . ' days')),
                    ]);
                }
            }
        }
        $log[] = '[OK] Created ' . $hd_count . ' helpdesk tickets (3 per employee)';

        // ====================================================================
        // CONTRACTS
        // ====================================================================
        $contract_types = ['permanent', 'fixed', 'permanent', 'fixed', 'probation'];
        $ct_count = 0;
        foreach ($all_emp as $ei => $eid) {
            $ct    = $contract_types[$ei % count($contract_types)];
            $join  = date('Y-m-d', strtotime('-' . (18 - $ei * 2) . ' months'));
            $end   = ($ct !== 'permanent') ? date('Y-m-d', strtotime($join . ' +12 months')) : null;
            $this->db->insert(db_prefix() . 'hr_contracts', [
                'employee_id'   => $eid,
                'title'         => $all_people[$ei]['firstname'] . ' ' . $all_people[$ei]['lastname'] . ' — ' . ucfirst(str_replace('_', ' ', $ct)) . ' Employment Contract',
                'contract_type' => $ct,
                'start_date'    => $join,
                'end_date'      => $end,
                'value'         => (float) $all_people[$ei]['salary'] * 12,
                'content'       => "EMPLOYMENT CONTRACT\n\nThis employment contract is entered into between Alpha Net Bangladesh Ltd. (the 'Company') and " . $all_people[$ei]['firstname'] . ' ' . $all_people[$ei]['lastname'] . " (the 'Employee').\n\n1. POSITION\nThe Employee is employed as per the designation stated in the HR records.\n\n2. REMUNERATION\nThe basic salary shall be as agreed and detailed in the payroll system.\n\n3. WORKING HOURS\nThe Employee is required to work standard office hours (9:00 AM to 6:00 PM), Monday through Friday.\n\n4. CONFIDENTIALITY\nThe Employee agrees to maintain strict confidentiality of all company information, client data, and business processes.\n\n5. TERMINATION\nEither party may terminate this contract with 30 days written notice. Immediate termination may occur for gross misconduct.\n\n6. GOVERNING LAW\nThis contract is governed by Bangladesh Labour Law 2006.",
                'status'        => 'active',
                'signed'        => ($ei % 3 !== 2) ? 1 : 0,
                'signed_date'   => ($ei % 3 !== 2) ? date('Y-m-d', strtotime($join . ' +7 days')) : null,
                'created_at'    => $now,
            ]);
            $ct_count++;
        }
        $log[] = '[OK] Created ' . $ct_count . ' employment contracts';

        // ====================================================================
        // DONE
        // ====================================================================
        $log[] = '----------------------------------------';
        if (!empty($errors)) {
            foreach ($errors as $e) {
                $log[] = '[ERROR] ' . $e;
            }
            $log[] = '----------------------------------------';
            $log[] = '[WARN] Completed with ' . count($errors) . ' warning(s).';
        } else {
            $log[] = '[DONE] All demo data seeded successfully!';
        }
        $log[] = '';
        $log[] = 'Demo accounts created:';
        $log[] = '  talha@alpha.net.bd  => ALPHA-EMP-001 (Talha Ahmed, HR Manager)';
        $log[] = '  alice.johnson@demo.hr => DEMO-EMP-001 (Alice Johnson, Software Engineer) -- password: Demo@1234';
        $log[] = '  bob.smith@demo.hr     => DEMO-EMP-002 (Bob Smith, Senior Accountant)     -- password: Demo@1234';
        $log[] = '  carol.williams@demo.hr=> DEMO-EMP-003 (Carol Williams, UI/UX Designer)   -- password: Demo@1234';
        $log[] = '  david.brown@demo.hr   => DEMO-EMP-004 (David Brown, Sales Executive)     -- password: Demo@1234';

        $data['log']     = $log;
        $data['success'] = empty(array_filter($errors, function($e) { return strpos($e, 'FATAL') !== false || (strpos($e, 'failed') !== false && strpos($e, '[WARN]') === false); }));
        $data['title']   = 'HR Demo Data Seeder';
        $this->load->view('hr_module/demo_data/result', $data);
    }

    // admin/hr_module/demo_data/reset
    public function reset()
    {
        if (!is_admin()) access_denied('demo_data');

        // Find all demo employees (DEMO- prefix) AND Talha's employee record
        $employees = $this->db->group_start()
            ->like('employee_code', 'DEMO-', 'after')
            ->or_where('employee_code', 'ALPHA-EMP-001')
            ->group_end()
            ->get(db_prefix() . 'hr_employees')->result();

        if (empty($employees)) {
            set_alert('warning', 'No demo employee data found to reset.');
            redirect(admin_url('hr_module/demo_data'));
            return;
        }

        $emp_ids    = array_column($employees, 'id');
        $emp_id_str = implode(',', array_map('intval', $emp_ids));

        // Delete linked HR data
        foreach ([
            'hr_leave_requests', 'hr_leave_balances', 'hr_attendance',
            'hr_overtime', 'hr_performance_reviews', 'hr_loans',
            'hr_contracts',
        ] as $tbl) {
            $this->db->query('DELETE FROM `' . db_prefix() . $tbl . '` WHERE employee_id IN (' . $emp_id_str . ')');
        }

        // Loan repayments (via loan IDs)
        $loan_rows = $this->db->select('id')->where_in('employee_id', $emp_ids)->get(db_prefix() . 'hr_loans')->result_array();
        if (!empty($loan_rows)) {
            $l_ids = implode(',', array_map('intval', array_column($loan_rows, 'id')));
            $this->db->query('DELETE FROM `' . db_prefix() . 'hr_loan_repayments` WHERE loan_id IN (' . $l_ids . ')');
        }

        // Payroll details (via payroll IDs)
        $pay_rows = $this->db->select('id')->where_in('employee_id', $emp_ids)->get(db_prefix() . 'hr_payroll')->result_array();
        if (!empty($pay_rows)) {
            $p_ids = implode(',', array_map('intval', array_column($pay_rows, 'id')));
            $this->db->query('DELETE FROM `' . db_prefix() . 'hr_payroll_details` WHERE payroll_id IN (' . $p_ids . ')');
            $this->db->query('DELETE FROM `' . db_prefix() . 'hr_payroll` WHERE employee_id IN (' . $emp_id_str . ')');
        }

        // Training participants
        $this->db->query('DELETE FROM `' . db_prefix() . 'hr_training_participants` WHERE employee_id IN (' . $emp_id_str . ')');

        // Helpdesk replies (via ticket IDs)
        $hd_rows = $this->db->select('id')->where_in('employee_id', $emp_ids)->get(db_prefix() . 'hr_helpdesk')->result_array();
        if (!empty($hd_rows)) {
            $hd_ids = implode(',', array_map('intval', array_column($hd_rows, 'id')));
            $this->db->query('DELETE FROM `' . db_prefix() . 'hr_helpdesk_replies` WHERE ticket_id IN (' . $hd_ids . ')');
        }
        $this->db->query('DELETE FROM `' . db_prefix() . 'hr_helpdesk` WHERE employee_id IN (' . $emp_id_str . ')');

        // Employees
        $this->db->where_in('id', $emp_ids)->delete(db_prefix() . 'hr_employees');

        // Delete demo staff accounts (but NOT talha@alpha.net.bd -- it's a real account)
        $demo_emails = [
            'alice.johnson@demo.hr',
            'bob.smith@demo.hr',
            'carol.williams@demo.hr',
            'david.brown@demo.hr',
        ];
        $this->db->where_in('email', $demo_emails)->delete(db_prefix() . 'staff');

        set_alert('success', 'Demo data has been reset. Talha\'s staff account was preserved; only the HR employee profile was removed.');
        redirect(admin_url('hr_module/demo_data'));
    }
}
