<?php
defined('BASEPATH') or exit('No direct script access allowed');

// This file re-runs automatically on every already-activated install whenever
// HR_MODULE_SCHEMA_VERSION (in hr_module.php) is bumped - see
// hr_module_ensure_schema() there. That means every statement in here MUST
// stay guarded ("only create/alter if not already there"), the same as it
// already is. If you add a new table or column below, also bump
// HR_MODULE_SCHEMA_VERSION, or existing sites will never pick it up.

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
// Upgrade: designations are independent of department - drop the old column if present
if ($CI->db->table_exists(db_prefix() . 'hr_designations')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_designations` LIKE 'department_id'")->num_rows();
    if ($col > 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_designations` DROP COLUMN `department_id`");
    }
}

// 2b. Branches - a predefined list (Settings > Company Structure), same shape
// and management pattern as Designations
if (!$CI->db->table_exists(db_prefix() . 'hr_branches')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_branches` (
      `id` int(11) NOT NULL,
      `name` varchar(191) NOT NULL,
      `description` text DEFAULT NULL,
      `status` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_branches`
      ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_branches`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
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
      `personal_email` varchar(191) DEFAULT NULL,
      `phone` varchar(50) DEFAULT NULL,
      `gender` varchar(20) DEFAULT NULL,
      `date_of_birth` date DEFAULT NULL,
      `address` text DEFAULT NULL,
      `department_id` int(11) DEFAULT NULL,
      `branch_id` int(11) DEFAULT NULL,
      `designation_id` int(11) DEFAULT NULL,
      `joining_date` date DEFAULT NULL,
      `end_date` date DEFAULT NULL,
      `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
      `max_loan_amount` decimal(15,2) DEFAULT NULL,
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
      ADD KEY `branch_id` (`branch_id`),
      ADD KEY `designation_id` (`designation_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_employees`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}
// Upgrade: add max_loan_amount column (per-employee loan ceiling override - NULL
// means "use the site-wide default from Settings > General") if the table
// existed before it was added to the schema
if ($CI->db->table_exists(db_prefix() . 'hr_employees')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_employees` LIKE 'max_loan_amount'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_employees` ADD COLUMN `max_loan_amount` decimal(15,2) DEFAULT NULL AFTER `basic_salary`");
    }
}
// Upgrade: add personal_email column (a second, employee-owned address kept
// separate from `email`, which stays synced from the linked staff account) if
// the table existed before it was added to the schema
if ($CI->db->table_exists(db_prefix() . 'hr_employees')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_employees` LIKE 'personal_email'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_employees` ADD COLUMN `personal_email` varchar(191) DEFAULT NULL AFTER `email`");
    }
}
// Upgrade: add branch_id column (FK to the new hr_branches list, same pattern
// as designation_id) if the table existed before it was added to the schema
if ($CI->db->table_exists(db_prefix() . 'hr_employees')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_employees` LIKE 'branch_id'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_employees` ADD COLUMN `branch_id` int(11) DEFAULT NULL AFTER `department_id`");
    }
}

// 4. Leave Types
if (!$CI->db->table_exists(db_prefix() . 'hr_leave_types')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_leave_types` (
      `id` int(11) NOT NULL,
      `name` varchar(191) NOT NULL,
      `days_per_year` int(11) NOT NULL DEFAULT 0,
      `hours_per_day` decimal(4,1) NOT NULL DEFAULT 8.0,
      `carry_forward` tinyint(1) NOT NULL DEFAULT 0,
      `max_carry_forward_days` int(11) NOT NULL DEFAULT 0,
      `requires_attachment` tinyint(1) NOT NULL DEFAULT 0,
      `allow_half_day` tinyint(1) NOT NULL DEFAULT 1,
      `is_date_range` tinyint(1) NOT NULL DEFAULT 0,
      `gender` varchar(10) DEFAULT NULL,
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
    $CI->db->query("INSERT INTO `" . db_prefix() . "hr_leave_types` (`name`, `days_per_year`, `hours_per_day`, `carry_forward`, `requires_attachment`, `allow_half_day`, `is_date_range`, `gender`, `description`, `status`, `created_at`) VALUES
      ('Annual Leave', 15, 8.0, 1, 0, 1, 0, NULL, 'Regular annual leave entitlement', 1, '$now'),
      ('Sick Leave', 14, 8.0, 0, 1, 1, 0, NULL, 'Sick leave with medical certificate', 1, '$now'),
      ('Casual Leave', 10, 8.0, 0, 0, 1, 0, NULL, 'Casual leave for personal reasons', 1, '$now'),
      ('Maternity Leave', 120, 8.0, 0, 1, 0, 1, 'female', 'Maternity leave for female employees', 1, '$now'),
      ('Paternity Leave', 5, 8.0, 0, 0, 0, 0, 'male', 'Paternity leave for male employees', 1, '$now'),
      ('Unpaid Leave', 0, 8.0, 0, 0, 1, 0, NULL, 'Leave without pay', 1, '$now')");
}
// Upgrade: add hours_per_day column if the table existed before it was added to the schema
if ($CI->db->table_exists(db_prefix() . 'hr_leave_types')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_leave_types` LIKE 'hours_per_day'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_leave_types` ADD COLUMN `hours_per_day` decimal(4,1) NOT NULL DEFAULT 8.0 AFTER `days_per_year`");
    }
}
// Upgrade: add is_date_range column (e.g. Maternity Leave applied as a From/To range
// instead of day-by-day) if the table existed before it was added to the schema
if ($CI->db->table_exists(db_prefix() . 'hr_leave_types')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_leave_types` LIKE 'is_date_range'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_leave_types` ADD COLUMN `is_date_range` tinyint(1) NOT NULL DEFAULT 0 AFTER `allow_half_day`");
        // Best-effort: flag any existing "Maternity"/"Paternity"-named type as range-based by default
        $CI->db->query("UPDATE `" . db_prefix() . "hr_leave_types` SET `is_date_range` = 1 WHERE `name` LIKE '%maternity%' OR `name` LIKE '%paternity%'");
    }
}
// Upgrade: add gender column (restricts a leave type to 'male'/'female' employees;
// NULL/'' means open to any gender) if the table existed before it was added to the schema
if ($CI->db->table_exists(db_prefix() . 'hr_leave_types')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_leave_types` LIKE 'gender'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_leave_types` ADD COLUMN `gender` varchar(10) DEFAULT NULL AFTER `is_date_range`");
        // Best-effort: restrict any existing "Maternity"/"Paternity"-named type by gender
        $CI->db->query("UPDATE `" . db_prefix() . "hr_leave_types` SET `gender` = 'female' WHERE `name` LIKE '%maternity%'");
        $CI->db->query("UPDATE `" . db_prefix() . "hr_leave_types` SET `gender` = 'male' WHERE `name` LIKE '%paternity%'");
    }
}

// 5. Leave Requests
if (!$CI->db->table_exists(db_prefix() . 'hr_leave_requests')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_leave_requests` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `leave_type_id` int(11) NOT NULL,
      `from_date` date NOT NULL,
      `to_date` date NOT NULL,
      `total_days` decimal(5,2) NOT NULL DEFAULT 0.00,
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

// 5b. Leave Request Days - per-day breakdown (full / half before-lunch / half after-lunch / hourly)
if (!$CI->db->table_exists(db_prefix() . 'hr_leave_request_days')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_leave_request_days` (
      `id` int(11) NOT NULL,
      `leave_request_id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `leave_date` date NOT NULL,
      `day_type` varchar(20) NOT NULL DEFAULT \'full\',
      `hour_start` time DEFAULT NULL,
      `hour_end` time DEFAULT NULL,
      `day_value` decimal(4,2) NOT NULL DEFAULT 1.00,
      `note` varchar(191) DEFAULT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_leave_request_days`
      ADD PRIMARY KEY (`id`),
      ADD KEY `leave_request_id` (`leave_request_id`),
      ADD KEY `employee_date` (`employee_id`, `leave_date`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_leave_request_days`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}
// Upgrade: add note column (used for bridge-day context, e.g. which holiday it was) if missing
if ($CI->db->table_exists(db_prefix() . 'hr_leave_request_days')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_leave_request_days` LIKE 'note'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_leave_request_days` ADD COLUMN `note` varchar(191) DEFAULT NULL");
    }
}

// 6. Leave Balances
if (!$CI->db->table_exists(db_prefix() . 'hr_leave_balances')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_leave_balances` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `leave_type_id` int(11) NOT NULL,
      `year` int(4) NOT NULL,
      `allocated_days` decimal(5,2) NOT NULL DEFAULT 0.00,
      `used_days` decimal(5,2) NOT NULL DEFAULT 0.00,
      `carry_forward_days` decimal(5,2) NOT NULL DEFAULT 0.00,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_leave_balances`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `emp_leave_year` (`employee_id`, `leave_type_id`, `year`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_leave_balances`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}
// Upgrade: widen day-count columns to 2 decimal places for hourly-leave fractions
// (e.g. 3 hours of an 8-hour day = 0.38, which decimal(5,1) would round to 0.4)
if ($CI->db->table_exists(db_prefix() . 'hr_leave_requests')) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_leave_requests` MODIFY `total_days` decimal(5,2) NOT NULL DEFAULT 0.00");
}
if ($CI->db->table_exists(db_prefix() . 'hr_leave_balances')) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_leave_balances` MODIFY `allocated_days` decimal(5,2) NOT NULL DEFAULT 0.00");
    $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_leave_balances` MODIFY `used_days` decimal(5,2) NOT NULL DEFAULT 0.00");
    $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_leave_balances` MODIFY `carry_forward_days` decimal(5,2) NOT NULL DEFAULT 0.00");
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
// Upgrade: records the verify method (Fingerprint/ID Card/Face/Password) of
// the latest punch behind this row's out_time, so the Attendance list's
// Source column can show it directly instead of a generic device icon.
if ($CI->db->table_exists(db_prefix() . 'hr_attendance')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_attendance` LIKE 'verify_mode'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_attendance` ADD COLUMN `verify_mode` varchar(30) DEFAULT NULL AFTER `source`");
    }
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
// Upgrade: ADMS push protocol identifies devices by serial_number, not
// ip_address/port anymore - add a unique index (NULLs still allowed, so
// existing devices without one aren't broken; the field is only required
// going forward at the application layer).
if ($CI->db->table_exists(db_prefix() . 'hr_zkteco_devices')) {
    $idx = $CI->db->query("SHOW INDEX FROM `" . db_prefix() . "hr_zkteco_devices` WHERE Key_name = 'serial_number'")->num_rows();
    if ($idx === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_zkteco_devices` ADD UNIQUE KEY `serial_number` (`serial_number`)");
    }
}
// Upgrade: a second device brand (non-ZKTeco face-recognition terminals) can
// now be registered too - existing rows default to 'zkteco' so nothing already
// installed changes behavior.
if ($CI->db->table_exists(db_prefix() . 'hr_zkteco_devices')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_zkteco_devices` LIKE 'device_type'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_zkteco_devices` ADD `device_type` varchar(30) NOT NULL DEFAULT 'zkteco' AFTER `name`");
    }
}
// Upgrade: 'ai07f' was renamed to 'aiface' - it was only ever meant to mean
// "TIMY AiFace-protocol device" in general (the AI07F was just the first
// model registered), not one specific model. The AI-series has multiple
// models (AI07F, AI03FC, etc.) all speaking the same protocol, and the
// Device Type dropdown/label should reflect the protocol family, not a
// single model name - the actual model goes in the free-text Device Name
// field instead.
if ($CI->db->table_exists(db_prefix() . 'hr_zkteco_devices')) {
    $CI->db->where('device_type', 'ai07f')->update(db_prefix() . 'hr_zkteco_devices', ['device_type' => 'aiface']);
}
if ($CI->db->table_exists(db_prefix() . 'hr_attendance')) {
    $CI->db->where('source', 'ai07f')->update(db_prefix() . 'hr_attendance', ['source' => 'aiface']);
}
// Upgrade: the F18's own Cloud Server Setting screen has no configurable poll
// interval, and Delay= is now a fixed value in the ADMS handshake response
// (see Iclock::_handshake()) - drop the now-unused setting from existing installs.
if ($CI->db->table_exists(db_prefix() . 'hr_settings')) {
    $CI->db->where('setting_key', 'zkteco_sync_interval')->delete(db_prefix() . 'hr_settings');
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

// 10b. ZKTeco Raw Punches - one row per individual punch, so a day's
// aggregate hr_attendance in/out span can be inspected punch-by-punch
// (device, time, verify method) from the attendance list's "View Log" popup.
if (!$CI->db->table_exists(db_prefix() . 'hr_zkteco_punches')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_zkteco_punches` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `attendance_date` date NOT NULL,
      `punch_time` time NOT NULL,
      `device_id` int(11) DEFAULT NULL,
      `verify_mode` varchar(30) DEFAULT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_zkteco_punches`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_date` (`employee_id`, `attendance_date`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_zkteco_punches`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// Upgrade: the /iclock/* ADMS push endpoints can only be routed into this
// module via a route in application/config/my_routes.php - a module's own
// routes.php is only consulted once the URI's first segment already matches
// the module's own folder name (see DEVELOPER.md §10), so a bare
// /iclock/... path needs this global route file to exist. Writes it
// automatically (or appends to it if it already exists for something else)
// so a fresh install of this module works out of the box, with no manual
// server-config step required.
$hr_module_my_routes_path = APPPATH . 'config/my_routes.php';
$hr_module_iclock_routes  = "\$route['iclock/cdata']      = 'hr_module/iclock/cdata';\n"
    . "\$route['iclock/getrequest'] = 'hr_module/iclock/getrequest';\n"
    . "\$route['iclock/devicecmd']  = 'hr_module/iclock/devicecmd';\n";
if (!file_exists($hr_module_my_routes_path)) {
    $hr_module_my_routes_content = "<?php\n\ndefined('BASEPATH') or exit('No direct script access allowed');\n\n"
        . "/**\n * ZKTeco ADMS push protocol routes.\n"
        . " * These map the fixed device-facing paths required by the ZKTeco F18\n"
        . " * ADMS spec into the hr_module's Iclock controller.\n"
        . " * Auto-created by hr_module's installer - see modules/hr_module/install.php.\n"
        . " */\n" . $hr_module_iclock_routes;
    @file_put_contents($hr_module_my_routes_path, $hr_module_my_routes_content);
} else {
    $hr_module_my_routes_existing = @file_get_contents($hr_module_my_routes_path);
    if ($hr_module_my_routes_existing !== false && strpos($hr_module_my_routes_existing, "'iclock/cdata'") === false) {
        @file_put_contents(
            $hr_module_my_routes_path,
            $hr_module_my_routes_existing . "\n// Added by hr_module's installer for ZKTeco ADMS push routing.\n" . $hr_module_iclock_routes
        );
    }
}

// Upgrade: same reasoning as the ZKTeco block above, for the TIMMY AI05
// terminal - its firmware has no configurable request path and is
// hardcoded to always POST to /pub/api, so that fixed path needs the same
// global route file to reach hr_module's AiFace receiver. Written
// automatically so a fresh install (or an existing site picking up this
// version) works out of the box, with no manual server-config step.
$hr_module_pub_api_route = "\$route['pub/api'] = 'hr_module/aiface/receive';\n";
$hr_module_my_routes_existing = @file_get_contents($hr_module_my_routes_path);
if ($hr_module_my_routes_existing !== false && strpos($hr_module_my_routes_existing, "'pub/api'") === false) {
    @file_put_contents(
        $hr_module_my_routes_path,
        $hr_module_my_routes_existing . "\n// Added by hr_module's installer for the TIMMY AI05 push protocol.\n" . $hr_module_pub_api_route
    );
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
      `overtime_days` int(3) NOT NULL DEFAULT 0,
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
// Upgrade: track how many approved overtime days went into each payroll's overtime_amount,
// so the payroll list can show a day count alongside the pay - not just the money.
if ($CI->db->table_exists(db_prefix() . 'hr_payroll')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_payroll` LIKE 'overtime_days'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_payroll`
          ADD COLUMN `overtime_days` int(3) NOT NULL DEFAULT 0 AFTER `overtime_amount`");
        if ($CI->db->table_exists(db_prefix() . 'hr_overtime')) {
            $CI->db->query("UPDATE `" . db_prefix() . "hr_payroll` p
              SET p.overtime_days = (
                SELECT COUNT(*) FROM `" . db_prefix() . "hr_overtime` o
                WHERE o.employee_id = p.employee_id
                  AND o.status = 'approved'
                  AND MONTH(o.overtime_date) = p.pay_month
                  AND YEAR(o.overtime_date) = p.pay_year
              )");
        }
    }
}
// Upgrade: lets HR request/approve/reject a change to an already-generated (draft)
// payroll's loan deduction, recalculating the net (payable) amount once approved.
if ($CI->db->table_exists(db_prefix() . 'hr_payroll')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_payroll` LIKE 'deduction_request_amount'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_payroll`
          ADD COLUMN `deduction_request_amount` decimal(15,2) DEFAULT NULL AFTER `loan_deduction`,
          ADD COLUMN `deduction_request_reason` text DEFAULT NULL AFTER `deduction_request_amount`,
          ADD COLUMN `deduction_request_status` varchar(20) DEFAULT NULL AFTER `deduction_request_reason`");
    }
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
// Upgrade: tracks a skipped month's installment that the employee chose to carry
// into the next month's deduction (rather than extending the repayment term).
if ($CI->db->table_exists(db_prefix() . 'hr_loans')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_loans` LIKE 'carry_forward_amount'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_loans` ADD COLUMN `carry_forward_amount` decimal(15,2) NOT NULL DEFAULT 0.00 AFTER `monthly_installment`");
    }
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
      `batch_id` varchar(36) DEFAULT NULL,
      `overtime_date` date NOT NULL,
      `day_type` enum(\'weekend\',\'government_holiday\',\'company_holiday\') DEFAULT NULL,
      `holiday_name` varchar(191) DEFAULT NULL,
      `hours` decimal(5,2) NOT NULL DEFAULT 0.00,
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
      ADD KEY `status` (`status`),
      ADD KEY `batch_id` (`batch_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_overtime`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}
// Upgrade: overtime is day-based (weekend/govt/company holiday), not hourly - add the
// columns needed to record which kind of day was worked and relax `hours`, which is
// no longer collected from the user, so inserts don't need to supply it.
if ($CI->db->table_exists(db_prefix() . 'hr_overtime')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_overtime` LIKE 'day_type'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_overtime`
          ADD COLUMN `day_type` enum('weekend','government_holiday','company_holiday') DEFAULT NULL AFTER `overtime_date`,
          ADD COLUMN `holiday_name` varchar(191) DEFAULT NULL AFTER `day_type`,
          MODIFY `hours` decimal(5,2) NOT NULL DEFAULT 0.00");
    }
}
// Upgrade: an employee can log several overtime days in a month in a single request -
// group the rows a batch submission creates under one shared batch_id so the list can
// show/act on them as one request instead of N separate ones. Existing rows each become
// a batch of their own (batch_id = their own id) so nothing already submitted changes.
if ($CI->db->table_exists(db_prefix() . 'hr_overtime')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_overtime` LIKE 'batch_id'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_overtime`
          ADD COLUMN `batch_id` varchar(36) DEFAULT NULL AFTER `employee_id`,
          ADD KEY `batch_id` (`batch_id`)");
        $CI->db->query("UPDATE `" . db_prefix() . "hr_overtime` SET `batch_id` = `id` WHERE `batch_id` IS NULL");
    }
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

// 17b. Performance Tasks - a role-assigned person (HR/manager) assigns an employee a task
// with one or more evaluators; the employee marks their own progress and evaluators leave
// feedback. Replaces the single period-review workflow above with a task list.
if (!$CI->db->table_exists(db_prefix() . 'hr_performance_tasks')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_performance_tasks` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `assigned_by` int(11) DEFAULT NULL,
      `title` varchar(191) NOT NULL,
      `description` text DEFAULT NULL,
      `due_date` date DEFAULT NULL,
      `status` enum(\'pending\',\'in_progress\',\'partially_completed\',\'completed\') NOT NULL DEFAULT \'pending\',
      `completion_percentage` decimal(5,2) DEFAULT NULL,
      `employee_note` text DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_tasks`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_id` (`employee_id`),
      ADD KEY `status` (`status`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_tasks`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

if (!$CI->db->table_exists(db_prefix() . 'hr_performance_task_evaluators')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_performance_task_evaluators` (
      `id` int(11) NOT NULL,
      `task_id` int(11) NOT NULL,
      `staff_id` int(11) NOT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_task_evaluators`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `task_staff` (`task_id`,`staff_id`),
      ADD KEY `staff_id` (`staff_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_task_evaluators`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

if (!$CI->db->table_exists(db_prefix() . 'hr_performance_task_feedback')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_performance_task_feedback` (
      `id` int(11) NOT NULL,
      `task_id` int(11) NOT NULL,
      `evaluator_id` int(11) NOT NULL,
      `feedback` text NOT NULL,
      `rating` varchar(30) DEFAULT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_task_feedback`
      ADD PRIMARY KEY (`id`),
      ADD KEY `task_id` (`task_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_task_feedback`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 17c. Performance is Target-based, not a flat task list: a role-assigned person (HR/
// manager) assigns an employee an overall Target, which can contain several Sub-Targets
// - each with its own title/description/due date/status/completion/evaluators/feedback.
// Superseded the flat hr_performance_tasks above (kept in place, unused, for history).
$hr_targets_is_new = !$CI->db->table_exists(db_prefix() . 'hr_performance_targets');
if ($hr_targets_is_new) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_performance_targets` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `assigned_by` int(11) DEFAULT NULL,
      `title` varchar(191) NOT NULL,
      `description` text DEFAULT NULL,
      `due_date` date DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_targets`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_id` (`employee_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_targets`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

if (!$CI->db->table_exists(db_prefix() . 'hr_performance_sub_targets')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_performance_sub_targets` (
      `id` int(11) NOT NULL,
      `target_id` int(11) NOT NULL,
      `title` varchar(191) NOT NULL,
      `description` text DEFAULT NULL,
      `due_date` date DEFAULT NULL,
      `status` enum(\'pending\',\'in_progress\',\'partially_completed\',\'completed\') NOT NULL DEFAULT \'pending\',
      `completion_percentage` decimal(5,2) DEFAULT NULL,
      `employee_note` text DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_sub_targets`
      ADD PRIMARY KEY (`id`),
      ADD KEY `target_id` (`target_id`),
      ADD KEY `status` (`status`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_sub_targets`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

if (!$CI->db->table_exists(db_prefix() . 'hr_performance_sub_target_evaluators')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_performance_sub_target_evaluators` (
      `id` int(11) NOT NULL,
      `sub_target_id` int(11) NOT NULL,
      `staff_id` int(11) NOT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_sub_target_evaluators`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `sub_target_staff` (`sub_target_id`,`staff_id`),
      ADD KEY `staff_id` (`staff_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_sub_target_evaluators`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

if (!$CI->db->table_exists(db_prefix() . 'hr_performance_sub_target_feedback')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_performance_sub_target_feedback` (
      `id` int(11) NOT NULL,
      `sub_target_id` int(11) NOT NULL,
      `evaluator_id` int(11) NOT NULL,
      `feedback` text NOT NULL,
      `rating` varchar(30) DEFAULT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_sub_target_feedback`
      ADD PRIMARY KEY (`id`),
      ADD KEY `sub_target_id` (`sub_target_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_performance_sub_target_feedback`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// One-time migration: each existing flat hr_performance_tasks row becomes a Target with
// exactly one Sub-Target carrying over its status/completion/note/evaluators/feedback.
if ($hr_targets_is_new && $CI->db->table_exists(db_prefix() . 'hr_performance_tasks')) {
    $old_tasks = $CI->db->get(db_prefix() . 'hr_performance_tasks')->result();
    foreach ($old_tasks as $t) {
        $CI->db->insert(db_prefix() . 'hr_performance_targets', [
            'employee_id' => $t->employee_id,
            'assigned_by' => $t->assigned_by,
            'title'       => $t->title,
            'description' => $t->description,
            'due_date'    => $t->due_date,
            'created_at'  => $t->created_at,
            'updated_at'  => $t->updated_at,
        ]);
        $target_id = $CI->db->insert_id();

        $CI->db->insert(db_prefix() . 'hr_performance_sub_targets', [
            'target_id'             => $target_id,
            'title'                 => $t->title,
            'description'           => $t->description,
            'due_date'              => $t->due_date,
            'status'                => $t->status,
            'completion_percentage' => $t->completion_percentage,
            'employee_note'         => $t->employee_note,
            'created_at'            => $t->created_at,
            'updated_at'            => $t->updated_at,
        ]);
        $sub_target_id = $CI->db->insert_id();

        $evaluators = $CI->db->where('task_id', $t->id)->get(db_prefix() . 'hr_performance_task_evaluators')->result();
        foreach ($evaluators as $ev) {
            $CI->db->insert(db_prefix() . 'hr_performance_sub_target_evaluators', [
                'sub_target_id' => $sub_target_id,
                'staff_id'      => $ev->staff_id,
                'created_at'    => $ev->created_at,
            ]);
        }

        $feedback = $CI->db->where('task_id', $t->id)->get(db_prefix() . 'hr_performance_task_feedback')->result();
        foreach ($feedback as $f) {
            $CI->db->insert(db_prefix() . 'hr_performance_sub_target_feedback', [
                'sub_target_id' => $sub_target_id,
                'evaluator_id'  => $f->evaluator_id,
                'feedback'      => $f->feedback,
                'rating'        => $f->rating,
                'created_at'    => $f->created_at,
            ]);
        }
    }
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
// Upgrade: instructor is a real staff account (selected, not free text), so the
// instructor can log in and mark attendance themselves. `trainer` is kept as a
// read-only fallback label for older records that only ever had a free-text name.
if ($CI->db->table_exists(db_prefix() . 'hr_training')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_training` LIKE 'instructor_id'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_training` ADD COLUMN `instructor_id` int(11) DEFAULT NULL AFTER `trainer`");
    }
}
// Upgrade: an instructor hired from outside the company has no staff account
// to link via instructor_id - `trainer` (already a free-text name column) is
// reused as their name, alongside these two new optional contact fields. When
// an email is given they get an assignment notification too, same as an
// internal staff instructor, just without a CRM link since they can't log in.
if ($CI->db->table_exists(db_prefix() . 'hr_training')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_training` LIKE 'external_instructor_email'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_training` ADD COLUMN `external_instructor_email` varchar(191) DEFAULT NULL AFTER `instructor_id`");
    }
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_training` LIKE 'external_instructor_phone'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_training` ADD COLUMN `external_instructor_phone` varchar(50) DEFAULT NULL AFTER `external_instructor_email`");
    }
}

// Upgrade: the instructor can leave a closing note (summary/feedback) when they
// mark the training session complete.
if ($CI->db->table_exists(db_prefix() . 'hr_training')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_training` LIKE 'completion_note'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_training` ADD COLUMN `completion_note` text DEFAULT NULL AFTER `description`");
    }
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
      `attendance_status` enum(\'pending\',\'present\',\'absent\',\'partial\') NOT NULL DEFAULT \'pending\',
      `certificate` varchar(255) DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `employee_feedback` text DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_training_participants`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `training_employee` (`training_id`, `employee_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_training_participants`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}
// Upgrade: attendance is now Pending/Present/Absent (marked by the instructor or HR),
// not just a completed flag - backfill from the old boolean so existing data lines up.
if ($CI->db->table_exists(db_prefix() . 'hr_training_participants')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_training_participants` LIKE 'attendance_status'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_training_participants`
          ADD COLUMN `attendance_status` enum('pending','present','absent','partial') NOT NULL DEFAULT 'pending' AFTER `completion_date`");
        $CI->db->query("UPDATE `" . db_prefix() . "hr_training_participants` SET `attendance_status` = 'present' WHERE `completed` = 1");
    }
}
// Upgrade: attendance can be Partial (present on some days, absent on others) for
// a multi-day training, not just a strict all-or-nothing present/absent split.
if ($CI->db->table_exists(db_prefix() . 'hr_training_participants')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_training_participants` LIKE 'attendance_status'")->row();
    if ($col && strpos($col->Type, "'partial'") === false) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_training_participants`
          MODIFY COLUMN `attendance_status` enum('pending','present','absent','partial') NOT NULL DEFAULT 'pending'");
    }
}

// Upgrade: the instructor/HR can leave a private note about how an enrolled
// employee did, and the employee can leave their own feedback about the
// training/instructor - two independent notes on the same participant row.
if ($CI->db->table_exists(db_prefix() . 'hr_training_participants')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_training_participants` LIKE 'employee_feedback'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_training_participants` ADD COLUMN `employee_feedback` text DEFAULT NULL AFTER `notes`");
    }
}

// 19b. Training Daily Attendance - a multi-day training needs each calendar day
// confirmed separately by the instructor/HR, instead of one mark for the whole
// training. hr_training_participants.attendance_status stays as an overall summary
// (all days present / any day absent / still pending), recomputed after each daily mark.
if (!$CI->db->table_exists(db_prefix() . 'hr_training_attendance')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_training_attendance` (
      `id` int(11) NOT NULL,
      `training_id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `attendance_date` date NOT NULL,
      `status` enum(\'pending\',\'present\',\'absent\') NOT NULL DEFAULT \'pending\',
      `marked_by` int(11) DEFAULT NULL,
      `marked_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_training_attendance`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `training_employee_date` (`training_id`, `employee_id`, `attendance_date`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_training_attendance`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');

    // Backfill: generate one row per calendar day of each existing training for
    // each already-enrolled participant, carrying over their existing overall status.
    $trainings = $CI->db->select('id, start_date, end_date')->get(db_prefix() . 'hr_training')->result();
    foreach ($trainings as $t) {
        $participants = $CI->db->select('employee_id, attendance_status, completion_date')
            ->where('training_id', $t->id)->get(db_prefix() . 'hr_training_participants')->result();
        if (!$participants) continue;
        $start = new DateTime($t->start_date);
        $end   = new DateTime($t->end_date);
        if ($end < $start) $end = clone $start;
        foreach ($participants as $p) {
            $date = clone $start;
            while ($date <= $end) {
                $CI->db->insert(db_prefix() . 'hr_training_attendance', [
                    'training_id'     => $t->id,
                    'employee_id'     => $p->employee_id,
                    'attendance_date' => $date->format('Y-m-d'),
                    'status'          => $p->attendance_status,
                    'marked_at'       => $p->attendance_status !== 'pending' ? ($p->completion_date ?: null) : null,
                ]);
                $date->modify('+1 day');
            }
        }
    }
}

// 19c. Training Sessions - day-by-day schedule (each day picked individually, with
// its own start/end time) instead of a continuous start_date/end_date range. A
// training's start_date/end_date on hr_training are now DERIVED (min/max session
// date), kept only for quick list sorting/filtering.
if (!$CI->db->table_exists(db_prefix() . 'hr_training_sessions')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_training_sessions` (
      `id` int(11) NOT NULL,
      `training_id` int(11) NOT NULL,
      `session_date` date NOT NULL,
      `start_time` time DEFAULT NULL,
      `end_time` time DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_training_sessions`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `training_session_date` (`training_id`, `session_date`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_training_sessions`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');

    // Backfill: one session per calendar day of each existing training's old
    // start_date/end_date range - times are left blank since none were recorded.
    $trainings = $CI->db->select('id, start_date, end_date')->get(db_prefix() . 'hr_training')->result();
    foreach ($trainings as $t) {
        $start = new DateTime($t->start_date);
        $end   = new DateTime($t->end_date);
        if ($end < $start) $end = clone $start;
        $date = clone $start;
        while ($date <= $end) {
            $CI->db->insert(db_prefix() . 'hr_training_sessions', [
                'training_id'  => $t->id,
                'session_date' => $date->format('Y-m-d'),
            ]);
            $date->modify('+1 day');
        }
    }
}

// 20. HR Helpdesk Tickets
if (!$CI->db->table_exists(db_prefix() . 'hr_helpdesk')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_helpdesk` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) DEFAULT NULL,
      `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
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
// Upgrade: an employee can submit a ticket anonymously - identity is not stored
// at all (employee_id stays NULL) so even HR can't trace it back to them.
if ($CI->db->table_exists(db_prefix() . 'hr_helpdesk')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_helpdesk` LIKE 'is_anonymous'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_helpdesk` ADD COLUMN `is_anonymous` tinyint(1) NOT NULL DEFAULT 0 AFTER `employee_id`");
    }
    $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_helpdesk` MODIFY COLUMN `employee_id` int(11) DEFAULT NULL");
}
// Upgrade: an anonymous ticket has no one to reply back to, so instead of a
// back-and-forth reply thread, staff keep a single internal note on the ticket.
if ($CI->db->table_exists(db_prefix() . 'hr_helpdesk')) {
    $col = $CI->db->query("SHOW COLUMNS FROM `" . db_prefix() . "hr_helpdesk` LIKE 'internal_note'")->num_rows();
    if ($col === 0) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_helpdesk` ADD COLUMN `internal_note` text DEFAULT NULL AFTER `attachment`");
        $CI->db->query("ALTER TABLE `" . db_prefix() . "hr_helpdesk` ADD COLUMN `internal_note_attachment` varchar(255) DEFAULT NULL AFTER `internal_note`");
    }
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
      ('aiface_enabled', '0', '$now'),
      ('allow_data_removal_on_uninstall', '0', '$now')");
}
// Upgrade: add the AiFace integration toggle for installs whose hr_settings
// table already existed before this second device brand was added.
if ($CI->db->table_exists(db_prefix() . 'hr_settings')) {
    $exists = $CI->db->where('setting_key', 'aiface_enabled')->count_all_results(db_prefix() . 'hr_settings');
    if ($exists === 0) {
        $CI->db->insert(db_prefix() . 'hr_settings', [
            'setting_key'   => 'aiface_enabled',
            'setting_value' => '0',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}

// 25. Holidays
if (!$CI->db->table_exists(db_prefix() . 'hr_holidays')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_holidays` (
      `id` int(11) NOT NULL,
      `name` varchar(191) NOT NULL,
      `holiday_date` date NOT NULL,
      `type` enum(\'government\',\'company\') NOT NULL DEFAULT \'government\',
      `year` int(4) NOT NULL,
      `created_by` int(11) DEFAULT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_holidays`
      ADD PRIMARY KEY (`id`),
      ADD KEY `idx_year` (`year`),
      ADD KEY `idx_date` (`holiday_date`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_holidays`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 26. Loan Deduction Requests
if (!$CI->db->table_exists(db_prefix() . 'hr_loan_deduction_requests')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_loan_deduction_requests` (
      `id` int(10) unsigned NOT NULL,
      `loan_id` int(10) unsigned NOT NULL,
      `employee_id` int(10) unsigned NOT NULL,
      `pay_month` tinyint(3) unsigned NOT NULL,
      `pay_year` smallint(5) unsigned NOT NULL,
      `amount` decimal(15,2) NOT NULL,
      `status` enum(\'pending\',\'approved\',\'rejected\') NOT NULL DEFAULT \'pending\',
      `notes` text DEFAULT NULL,
      `reviewed_by` int(10) unsigned DEFAULT NULL,
      `reviewed_at` datetime DEFAULT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_loan_deduction_requests`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `uniq_loan_month_year` (`loan_id`,`pay_month`,`pay_year`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_loan_deduction_requests`
      MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 27. Policies - published record employees see. New policies and edits to existing
// ones both need admin approval before becoming visible; edits are staged in
// hr_policy_revisions (below) so the previously-approved content keeps showing until
// the edit itself is approved. `attachment` holds a JSON-encoded array of
// [{"file":"<stored name>","name":"<original name>"}, ...] to support multiple
// uploaded files per policy.
if (!$CI->db->table_exists(db_prefix() . 'hr_policies')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_policies` (
      `id` int(10) unsigned NOT NULL,
      `title` varchar(191) NOT NULL,
      `type` enum(\'public\',\'private\') NOT NULL DEFAULT \'public\',
      `department_id` int(11) DEFAULT NULL,
      `department_ids` text DEFAULT NULL,
      `content_type` enum(\'pdf\',\'text\') NOT NULL DEFAULT \'text\',
      `content` longtext DEFAULT NULL,
      `attachment` text DEFAULT NULL,
      `status` enum(\'pending\',\'published\',\'rejected\') NOT NULL DEFAULT \'pending\',
      `rejection_reason` text DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `approved_by` int(11) DEFAULT NULL,
      `approved_at` datetime DEFAULT NULL,
      `published_at` datetime DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_policies`
      ADD PRIMARY KEY (`id`),
      ADD KEY `department_id` (`department_id`),
      ADD KEY `status` (`status`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_policies`
      MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 28. Policy Revisions - a pending proposed change to an existing published policy.
if (!$CI->db->table_exists(db_prefix() . 'hr_policy_revisions')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_policy_revisions` (
      `id` int(10) unsigned NOT NULL,
      `policy_id` int(10) unsigned NOT NULL,
      `title` varchar(191) NOT NULL,
      `type` enum(\'public\',\'private\') NOT NULL DEFAULT \'public\',
      `department_id` int(11) DEFAULT NULL,
      `department_ids` text DEFAULT NULL,
      `content_type` enum(\'pdf\',\'text\') NOT NULL DEFAULT \'text\',
      `content` longtext DEFAULT NULL,
      `attachment` text DEFAULT NULL,
      `status` enum(\'pending\',\'approved\',\'rejected\') NOT NULL DEFAULT \'pending\',
      `rejection_reason` text DEFAULT NULL,
      `submitted_by` int(11) DEFAULT NULL,
      `reviewed_by` int(11) DEFAULT NULL,
      `reviewed_at` datetime DEFAULT NULL,
      `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_policy_revisions`
      ADD PRIMARY KEY (`id`),
      ADD KEY `policy_id` (`policy_id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_policy_revisions`
      MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// 29. Shift Types - defined on the Settings page (name + start/end time), e.g.
// Morning/Evening/Night. Employees are then assigned to one for a date range.
if (!$CI->db->table_exists(db_prefix() . 'hr_shift_types')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_shift_types` (
      `id` int(11) NOT NULL,
      `name` varchar(191) NOT NULL,
      `start_time` time NOT NULL,
      `end_time` time NOT NULL,
      `status` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_shift_types`
      ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_shift_types`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');

    // Default shifts so the feature isn't empty on first use.
    $now = date('Y-m-d H:i:s');
    $CI->db->query("INSERT INTO `" . db_prefix() . "hr_shift_types` (`name`, `start_time`, `end_time`, `status`, `created_at`) VALUES
      ('Morning Shift', '06:00:00', '14:00:00', 1, '$now'),
      ('Evening Shift', '14:00:00', '22:00:00', 1, '$now'),
      ('Night Shift', '22:00:00', '06:00:00', 1, '$now')");
}

// 30. Shift Assignments - an employee's shift for a specific date or date range.
// New assignments and their date range need approval before they count for the
// calendar/payroll shift column below.
if (!$CI->db->table_exists(db_prefix() . 'hr_shift_assignments')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'hr_shift_assignments` (
      `id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      `shift_type_id` int(11) NOT NULL,
      `from_date` date NOT NULL,
      `to_date` date NOT NULL,
      `status` enum(\'pending\',\'approved\',\'rejected\') NOT NULL DEFAULT \'pending\',
      `reason` text DEFAULT NULL,
      `rejection_reason` text DEFAULT NULL,
      `created_by` int(11) DEFAULT NULL,
      `approved_by` int(11) DEFAULT NULL,
      `approved_at` datetime DEFAULT NULL,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_shift_assignments`
      ADD PRIMARY KEY (`id`),
      ADD KEY `employee_id` (`employee_id`),
      ADD KEY `shift_type_id` (`shift_type_id`),
      ADD KEY `status` (`status`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'hr_shift_assignments`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

// Note: an earlier revision of this file created a custom hr_email_queue
// table here for bulk-send notifications. That's been superseded - bulk
// sends now rely on Perfex's own core mail queue (App_Email/mail_queue,
// gated by the site-wide "Enable email queue" setting) instead of a
// separate hr_module-specific one, so this table is intentionally no longer
// created. A site that already migrated to that earlier revision keeps a
// small, harmless, unused table - safe to ignore or drop manually.
