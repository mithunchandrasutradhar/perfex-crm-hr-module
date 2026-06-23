<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// 1. Departments
if (!$CI->db->table_exists(db_prefix() . 'hr_departments')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_departments` (
      `id` int(11) NOT NULL,
      `name` varchar(191) NOT NULL,
      `code` varchar(50) DEFAULT NULL,
      `parent_id` int(11) DEFAULT NULL,
      `head_staff_id` int(11) DEFAULT NULL,
      `description` text DEFAULT NULL,
      `status` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_departments`
      ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_departments`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 2. Designations
if (!$CI->db->table_exists(db_prefix() . 'hr_designations')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_designations` (
      `id` int(11) NOT NULL,
      `name` varchar(191) NOT NULL,
      `department_id` int(11) DEFAULT NULL,
      `description` text DEFAULT NULL,
      `status` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_designations`
      ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_designations`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}
// Upgrade: add status column if the table existed before it was added to the schema
if ($CI->db->table_exists(db_prefix() . 'hr_designations')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_designations` LIKE 'status'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_designations` ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT 1");
    }
}

// 3. Employees
if (!$CI->db->table_exists(db_prefix() . 'hr_employees')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_employees` (
      `id` int(11) NOT NULL,
      `employee_code` varchar(50) NOT NULL,
      `staff_id` int(11) DEFAULT NULL,
      `first_name` varchar(100) NOT NULL,
      `last_name` varchar(100) NOT NULL,
      `email` varchar(191) DEFAULT NULL,
      `phone` varchar(50) DEFAULT NULL,
      `gender` varchar(20) DEFAULT NULL,
      `date_of_birth` date DEFAULT NULL,
      `address` text DEFAULT NULL,
      `department_id` int(11) DEFAULT NULL,
      `designation_id` int(11) DEFAULT NULL,
      `joining_date` date DEFAULT NULL,
      `end_date` date DEFAULT NULL,
      `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
      `bank_name` varchar(191) DEFAULT NULL,
      `bank_account` varchar(100) DEFAULT NULL,
      `bank_branch` varchar(191) DEFAULT NULL,
      `tin_number` varchar(50) DEFAULT NULL,
      `nid_number` varchar(50) DEFAULT NULL,
      `passport_number` varchar(50) DEFAULT NULL,
      `blood_group` varchar(10) DEFAULT NULL,
      `religion` varchar(50) DEFAULT NULL,
      `marital_status` varchar(30) DEFAULT NULL,
      `emergency_contact_name` varchar(191) DEFAULT NULL,
      `emergency_contact_phone` varchar(50) DEFAULT NULL,
      `photo` varchar(255) DEFAULT NULL,
      `status` tinyint(1) NOT NULL DEFAULT 1,
      `notes` text DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_employees`
      ADD PRIMARY KEY (`id`),
      ADD KEY `staff_id` (`staff_id`),
      ADD KEY `department_id` (`department_id`),
      ADD KEY `designation_id` (`designation_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_employees`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 4. Leave Types
if (!$CI->db->table_exists(db_prefix() . 'hr_leave_types')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_leave_types` (
      `id` int(11) NOT NULL,
      `name` varchar(191) NOT NULL,
      `days_per_year` int(11) NOT NULL DEFAULT 0,
      `carry_forward` tinyint(1) NOT NULL DEFAULT 0,
      `max_carry_forward_days` int(11) NOT NULL DEFAULT 0,
      `requires_attachment` tinyint(1) NOT NULL DEFAULT 0,
      `allow_half_day` tinyint(1) NOT NULL DEFAULT 1,
      `description` text DEFAULT NULL,
      `status` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_leave_types`
      ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_leave_types`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');

    // Default leave types
    $now = date('Y-m-d H:i:s');
    $CI->db->query("INSERT INTO `" . db_prefix() . "hr_leave_types` (`name`, `days_per_year`, `carry_forward`, `requires_attachment`, `allow_half_day`, `description`, `status`, `created_at`) VALUES
      ('Annual Leave', 15, 1, 0, 1, 'Regular annual leave entitlement', 1, '$now'),
      ('Sick Leave', 14, 0, 1, 1, 'Sick leave with medical certificate', 1, '$now'),
      ('Casual Leave', 10, 0, 0, 1, 'Casual leave for personal reasons', 1, '$now'),
      ('Maternity Leave', 120, 0, 1, 0, 'Maternity leave for female employees', 1, '$now'),
      ('Paternity Leave', 5, 0, 0, 0, 'Paternity leave for male employees', 1, '$now'),
      ('Unpaid Leave', 0, 0, 0, 1, 'Leave without pay', 1, '$now')");
}

// 5. Leave Requests
if (!$CI->db->table_exists(db_prefix() . 'hr_leave_requests')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_leave_requests` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `leave_type_id` int(11) NOT NULL,
      `from_date` date NOT NULL,
      `to_date` date NOT NULL,
      `total_days` decimal(5,1) NOT NULL DEFAULT 0.0,
      `is_half_day` tinyint(1) NOT NULL DEFAULT 0,
      `reason` text DEFAULT NULL,
      `status` varchar(20) NOT NULL DEFAULT \'pending\',
      `approved_by` int(11) DEFAULT NULL,
      `approved_at` datetime DEFAULT NULL,
      `rejection_reason` text DEFAULT NULL,
      `attachment` varchar(255) DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_leave_requests`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_id` (`employee_id`),
      ADD KEY `leave_type_id` (`leave_type_id`),
      ADD KEY `status` (`status`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_leave_requests`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 6. Leave Balances
if (!$CI->db->table_exists(db_prefix() . 'hr_leave_balances')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_leave_balances` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `leave_type_id` int(11) NOT NULL,
      `year` int(4) NOT NULL,
      `allocated_days` decimal(5,1) NOT NULL DEFAULT 0.0,
      `used_days` decimal(5,1) NOT NULL DEFAULT 0.0,
      `carry_forward_days` decimal(5,1) NOT NULL DEFAULT 0.0,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_leave_balances`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `emp_leave_year` (`employee_id`, `leave_type_id`, `year`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_leave_balances`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 7. Attendance Logs
if (!$CI->db->table_exists(db_prefix() . 'hr_attendance')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_attendance` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `attendance_date` date NOT NULL,
      `in_time` time DEFAULT NULL,
      `out_time` time DEFAULT NULL,
      `working_hours` decimal(5,2) DEFAULT NULL,
      `status` varchar(20) NOT NULL DEFAULT \'present\',
      `source` varchar(20) NOT NULL DEFAULT \'manual\',
      `device_id` int(11) DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_attendance`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_id` (`employee_id`),
      ADD KEY `attendance_date` (`attendance_date`),
      ADD KEY `status` (`status`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_attendance`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 8. ZKTeco Devices
if (!$CI->db->table_exists(db_prefix() . 'hr_zkteco_devices')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_zkteco_devices` (
      `id` int(11) NOT NULL,
      `name` varchar(191) NOT NULL,
      `ip_address` varchar(45) NOT NULL,
      `port` int(5) NOT NULL DEFAULT 4370,
      `serial_number` varchar(100) DEFAULT NULL,
      `location` varchar(191) DEFAULT NULL,
      `status` tinyint(1) NOT NULL DEFAULT 1,
      `last_sync_at` datetime DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_zkteco_devices`
      ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_zkteco_devices`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 9. ZKTeco User Mapping
if (!$CI->db->table_exists(db_prefix() . 'hr_zkteco_mapping')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_zkteco_mapping` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `device_id` int(11) NOT NULL,
      `device_user_id` varchar(50) NOT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_zkteco_mapping`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `emp_device` (`employee_id`, `device_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_zkteco_mapping`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 10. ZKTeco Sync Logs
if (!$CI->db->table_exists(db_prefix() . 'hr_zkteco_sync_logs')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_zkteco_sync_logs` (
      `id` int(11) NOT NULL,
      `device_id` int(11) NOT NULL,
      `sync_at` datetime NOT NULL,
      `records_fetched` int(11) NOT NULL DEFAULT 0,
      `records_saved` int(11) NOT NULL DEFAULT 0,
      `status` varchar(20) NOT NULL DEFAULT \'success\',
      `error_message` text DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_zkteco_sync_logs`
      ADD PRIMARY KEY (`id`),
      ADD KEY `device_id` (`device_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_zkteco_sync_logs`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 11. Payroll Items (allowance/deduction definitions)
if (!$CI->db->table_exists(db_prefix() . 'hr_payroll_items')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_payroll_items` (
      `id` int(11) NOT NULL,
      `name` varchar(191) NOT NULL,
      `type` varchar(20) NOT NULL DEFAULT \'allowance\',
      `calculation_type` varchar(20) NOT NULL DEFAULT \'fixed\',
      `value` decimal(10,2) NOT NULL DEFAULT 0.00,
      `taxable` tinyint(1) NOT NULL DEFAULT 0,
      `description` text DEFAULT NULL,
      `status` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_payroll_items`
      ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_payroll_items`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 12. Payroll (monthly payroll records)
if (!$CI->db->table_exists(db_prefix() . 'hr_payroll')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_payroll` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `pay_month` int(2) NOT NULL,
      `pay_year` int(4) NOT NULL,
      `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
      `total_allowances` decimal(15,2) NOT NULL DEFAULT 0.00,
      `total_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
      `overtime_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
      `bonus` decimal(15,2) NOT NULL DEFAULT 0.00,
      `tax` decimal(15,2) NOT NULL DEFAULT 0.00,
      `loan_deduction` decimal(15,2) NOT NULL DEFAULT 0.00,
      `gross_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
      `net_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
      `working_days` int(3) DEFAULT NULL,
      `present_days` int(3) DEFAULT NULL,
      `absent_days` int(3) DEFAULT NULL,
      `payment_method` varchar(30) DEFAULT NULL,
      `payment_date` date DEFAULT NULL,
      `status` varchar(20) NOT NULL DEFAULT \'draft\',
      `approved_by` int(11) DEFAULT NULL,
      `approved_at` datetime DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `generated_by` int(11) DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_payroll`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_id` (`employee_id`),
      ADD KEY `pay_month_year` (`pay_month`, `pay_year`),
      ADD KEY `status` (`status`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_payroll`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 13. Payroll Item Details (per-payroll line items)
if (!$CI->db->table_exists(db_prefix() . 'hr_payroll_details')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_payroll_details` (
      `id` int(11) NOT NULL,
      `payroll_id` int(11) NOT NULL,
      `payroll_item_id` int(11) DEFAULT NULL,
      `item_name` varchar(191) NOT NULL,
      `item_type` varchar(20) NOT NULL DEFAULT \'allowance\',
      `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
      `notes` text DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_payroll_details`
      ADD PRIMARY KEY (`id`),
      ADD KEY `payroll_id` (`payroll_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_payroll_details`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 14. Loans
if (!$CI->db->table_exists(db_prefix() . 'hr_loans')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_loans` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `amount` decimal(15,2) NOT NULL,
      `reason` text DEFAULT NULL,
      `repayment_months` int(3) NOT NULL DEFAULT 1,
      `monthly_installment` decimal(15,2) NOT NULL DEFAULT 0.00,
      `total_repaid` decimal(15,2) NOT NULL DEFAULT 0.00,
      `outstanding` decimal(15,2) NOT NULL DEFAULT 0.00,
      `disbursement_date` date DEFAULT NULL,
      `status` varchar(20) NOT NULL DEFAULT \'pending\',
      `approved_by` int(11) DEFAULT NULL,
      `approved_at` datetime DEFAULT NULL,
      `rejection_reason` text DEFAULT NULL,
      `attachment` varchar(255) DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_loans`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_id` (`employee_id`),
      ADD KEY `status` (`status`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_loans`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 15. Loan Repayments
if (!$CI->db->table_exists(db_prefix() . 'hr_loan_repayments')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_loan_repayments` (
      `id` int(11) NOT NULL,
      `loan_id` int(11) NOT NULL,
      `payroll_id` int(11) DEFAULT NULL,
      `amount` decimal(15,2) NOT NULL,
      `repayment_date` date NOT NULL,
      `notes` text DEFAULT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_loan_repayments`
      ADD PRIMARY KEY (`id`),
      ADD KEY `loan_id` (`loan_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_loan_repayments`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 16. Overtime Requests
if (!$CI->db->table_exists(db_prefix() . 'hr_overtime')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_overtime` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `overtime_date` date NOT NULL,
      `hours` decimal(5,2) NOT NULL,
      `rate_multiplier` decimal(3,1) NOT NULL DEFAULT 1.5,
      `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
      `reason` text DEFAULT NULL,
      `status` varchar(20) NOT NULL DEFAULT \'pending\',
      `approved_by` int(11) DEFAULT NULL,
      `approved_at` datetime DEFAULT NULL,
      `rejection_reason` text DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_overtime`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_id` (`employee_id`),
      ADD KEY `status` (`status`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_overtime`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 17. Performance Reviews
if (!$CI->db->table_exists(db_prefix() . 'hr_performance_reviews')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_performance_reviews` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `reviewer_id` int(11) NOT NULL,
      `review_period_from` date NOT NULL,
      `review_period_to` date NOT NULL,
      `criteria` text DEFAULT NULL,
      `self_assessment` text DEFAULT NULL,
      `manager_review` text DEFAULT NULL,
      `final_score` decimal(5,2) DEFAULT NULL,
      `rating` varchar(30) DEFAULT NULL,
      `status` varchar(20) NOT NULL DEFAULT \'pending\',
      `notes` text DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_reviews`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_id` (`employee_id`),
      ADD KEY `reviewer_id` (`reviewer_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_reviews`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 18. Training Programs
if (!$CI->db->table_exists(db_prefix() . 'hr_training')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_training` (
      `id` int(11) NOT NULL,
      `title` varchar(191) NOT NULL,
      `trainer` varchar(191) DEFAULT NULL,
      `venue` varchar(191) DEFAULT NULL,
      `start_date` date NOT NULL,
      `end_date` date NOT NULL,
      `cost` decimal(15,2) NOT NULL DEFAULT 0.00,
      `capacity` int(5) DEFAULT NULL,
      `description` text DEFAULT NULL,
      `status` varchar(20) NOT NULL DEFAULT \'scheduled\',
      `attachment` varchar(255) DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_training`
      ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_training`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 19. Training Participants
if (!$CI->db->table_exists(db_prefix() . 'hr_training_participants')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_training_participants` (
      `id` int(11) NOT NULL,
      `training_id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `enrolled_at` datetime NOT NULL,
      `completed` tinyint(1) NOT NULL DEFAULT 0,
      `completion_date` date DEFAULT NULL,
      `certificate` varchar(255) DEFAULT NULL,
      `notes` text DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_training_participants`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `training_employee` (`training_id`, `employee_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_training_participants`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 20. HR Helpdesk Tickets
if (!$CI->db->table_exists(db_prefix() . 'hr_helpdesk')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_helpdesk` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `subject` varchar(255) NOT NULL,
      `category` varchar(100) DEFAULT NULL,
      `priority` varchar(20) NOT NULL DEFAULT \'medium\',
      `message` text NOT NULL,
      `status` varchar(20) NOT NULL DEFAULT \'open\',
      `assigned_to` int(11) DEFAULT NULL,
      `attachment` varchar(255) DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_helpdesk`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_id` (`employee_id`),
      ADD KEY `status` (`status`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_helpdesk`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 21. HR Helpdesk Replies
if (!$CI->db->table_exists(db_prefix() . 'hr_helpdesk_replies')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_helpdesk_replies` (
      `id` int(11) NOT NULL,
      `ticket_id` int(11) NOT NULL,
      `staff_id` int(11) NOT NULL,
      `message` text NOT NULL,
      `attachment` varchar(255) DEFAULT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_helpdesk_replies`
      ADD PRIMARY KEY (`id`),
      ADD KEY `ticket_id` (`ticket_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_helpdesk_replies`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 22. HR Contracts
if (!$CI->db->table_exists(db_prefix() . 'hr_contracts')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_contracts` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `title` varchar(255) NOT NULL,
      `contract_type` varchar(30) NOT NULL DEFAULT \'permanent\',
      `start_date` date NOT NULL,
      `end_date` date DEFAULT NULL,
      `value` decimal(15,2) DEFAULT NULL,
      `content` longtext DEFAULT NULL,
      `status` varchar(20) NOT NULL DEFAULT \'active\',
      `signed` tinyint(1) NOT NULL DEFAULT 0,
      `signed_date` date DEFAULT NULL,
      `attachment` varchar(255) DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_contracts`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_id` (`employee_id`),
      ADD KEY `status` (`status`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_contracts`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 23. Audit Trail
if (!$CI->db->table_exists(db_prefix() . 'hr_audit_trail')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_audit_trail` (
      `id` int(11) NOT NULL,
      `module` varchar(50) NOT NULL,
      `action` varchar(50) NOT NULL,
      `record_id` int(11) DEFAULT NULL,
      `old_value` longtext DEFAULT NULL,
      `new_value` longtext DEFAULT NULL,
      `performed_by` int(11) DEFAULT NULL,
      `ip_address` varchar(45) DEFAULT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_audit_trail`
      ADD PRIMARY KEY (`id`),
      ADD KEY `module` (`module`),
      ADD KEY `performed_by` (`performed_by`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_audit_trail`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 24. Settings
if (!$CI->db->table_exists(db_prefix() . 'hr_settings')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_settings` (
      `id` int(11) NOT NULL,
      `setting_key` varchar(100) NOT NULL,
      `setting_value` text DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_settings`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `setting_key` (`setting_key`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_settings`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');

    // Default settings
    $now    = date('Y-m-d H:i:s');
    $CI->db->query("INSERT INTO `" . db_prefix() . "hr_settings` (`setting_key`, `setting_value`, `created_at`) VALUES
      ('working_days_per_week', '5', '$now'),
      ('working_hours_per_day', '8', '$now'),
      ('office_start_time', '09:00', '$now'),
      ('office_end_time', '18:00', '$now'),
      ('late_threshold_minutes', '15', '$now'),
      ('default_overtime_rate', '1.5', '$now'),
      ('employee_id_prefix', 'EMP', '$now'),
      ('fiscal_year_start_month', '1', '$now'),
      ('payroll_generation_day', '25', '$now'),
      ('currency', 'BDT', '$now'),
      ('notify_leave_apply', '1', '$now'),
      ('notify_leave_approve', '1', '$now'),
      ('notify_loan_apply', '1', '$now'),
      ('notify_payroll', '1', '$now'),
      ('zkteco_enabled', '0', '$now'),
      ('zkteco_sync_interval', '30', '$now')");
}
