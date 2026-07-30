<?php
defined('BASEPATH') or exit('No direct script access allowed');

$route['hr_module']                               = 'hr_module/Hr_module/index';
$route['hr_module/dashboard']                     = 'hr_module/Hr_module/index';

// Settings
$route['hr_module/settings']                      = 'hr_module/Settings/index';
$route['hr_module/settings/save']                 = 'hr_module/Settings/save';
$route['hr_module/settings/add_shift']            = 'hr_module/Settings/add_shift';
$route['hr_module/settings/edit_shift/(:num)']    = 'hr_module/Settings/edit_shift/$1';
$route['hr_module/settings/delete_shift/(:num)']  = 'hr_module/Settings/delete_shift/$1';

// Email Templates
$route['hr_module/email_templates']               = 'hr_module/Email_templates/index';
$route['hr_module/email_templates/edit/(:num)']   = 'hr_module/Email_templates/edit/$1';
$route['hr_module/email_templates/send_test/(:num)'] = 'hr_module/Email_templates/send_test/$1';

// Employees
$route['hr_module/employees']                              = 'hr_module/Employees/index';
$route['hr_module/employees/add']                          = 'hr_module/Employees/add';
$route['hr_module/employees/edit/(:num)']                  = 'hr_module/Employees/edit/$1';
$route['hr_module/employees/view/(:num)']                  = 'hr_module/Employees/view/$1';
$route['hr_module/employees/delete/(:num)']                = 'hr_module/Employees/delete/$1';

// Departments
$route['hr_module/departments']                   = 'hr_module/Departments/index';
$route['hr_module/departments/add']               = 'hr_module/Departments/add';
$route['hr_module/departments/edit/(:num)']       = 'hr_module/Departments/edit/$1';
$route['hr_module/departments/delete/(:num)']     = 'hr_module/Departments/delete/$1';

// Designations
$route['hr_module/designations']                  = 'hr_module/Designations/index';
$route['hr_module/designations/add']              = 'hr_module/Designations/add';
$route['hr_module/designations/edit/(:num)']      = 'hr_module/Designations/edit/$1';
$route['hr_module/designations/delete/(:num)']    = 'hr_module/Designations/delete/$1';

// Leave Requests
$route['hr_module/leave']                         = 'hr_module/Leave/index';
$route['hr_module/leave/apply']                   = 'hr_module/Leave/apply';
$route['hr_module/leave/edit/(:num)']             = 'hr_module/Leave/edit/$1';
$route['hr_module/leave/view/(:num)']             = 'hr_module/Leave/view/$1';
$route['hr_module/leave/approve/(:num)']          = 'hr_module/Leave/approve/$1';
$route['hr_module/leave/reject/(:num)']           = 'hr_module/Leave/reject/$1';
$route['hr_module/leave/cancel/(:num)']           = 'hr_module/Leave/cancel/$1';
$route['hr_module/leave/request_cancellation/(:num)']  = 'hr_module/Leave/request_cancellation/$1';
$route['hr_module/leave/approve_cancellation/(:num)']  = 'hr_module/Leave/approve_cancellation/$1';
$route['hr_module/leave/reject_cancellation/(:num)']   = 'hr_module/Leave/reject_cancellation/$1';
$route['hr_module/leave/delete/(:num)']           = 'hr_module/Leave/delete/$1';

// Leave Types
$route['hr_module/leave_types']                   = 'hr_module/Leave_types/index';
$route['hr_module/leave_types/add']               = 'hr_module/Leave_types/add';
$route['hr_module/leave_types/edit/(:num)']       = 'hr_module/Leave_types/edit/$1';
$route['hr_module/leave_types/delete/(:num)']     = 'hr_module/Leave_types/delete/$1';

// Leave — balance AJAX
$route['hr_module/leave/get_balance_ajax']        = 'hr_module/Leave/get_balance_ajax';

// Leave Balances
$route['hr_module/leave_balances']                = 'hr_module/Leave_balances/index';
$route['hr_module/leave_balances/allocate']       = 'hr_module/Leave_balances/allocate';

// Attendance
$route['hr_module/attendance']                    = 'hr_module/Attendance/index';
$route['hr_module/attendance/add']                = 'hr_module/Attendance/add';
$route['hr_module/attendance/edit/(:num)']        = 'hr_module/Attendance/edit/$1';
$route['hr_module/attendance/delete/(:num)']      = 'hr_module/Attendance/delete/$1';
$route['hr_module/attendance/report']             = 'hr_module/Attendance/report';
$route['hr_module/attendance/monthly']            = 'hr_module/Attendance/monthly';
$route['hr_module/attendance/import']             = 'hr_module/Attendance/import';

// ZKTeco Devices
$route['hr_module/zkteco']                        = 'hr_module/Zkteco/index';
$route['hr_module/zkteco/add']                    = 'hr_module/Zkteco/add';
$route['hr_module/zkteco/edit/(:num)']            = 'hr_module/Zkteco/edit/$1';
$route['hr_module/zkteco/delete/(:num)']          = 'hr_module/Zkteco/delete/$1';
$route['hr_module/zkteco/sync/(:num)']            = 'hr_module/Zkteco/sync/$1';
$route['hr_module/zkteco/sync_logs']              = 'hr_module/Zkteco/sync_logs';
$route['hr_module/zkteco/mapping']                = 'hr_module/Zkteco/mapping';
$route['hr_module/zkteco/test_connection/(:num)'] = 'hr_module/Zkteco/test_connection/$1';
$route['hr_module/zkteco/delete_mapping/(:num)']  = 'hr_module/Zkteco/delete_mapping/$1';

// Payroll
$route['hr_module/payroll']                       = 'hr_module/Payroll/index';
$route['hr_module/payroll/generate']              = 'hr_module/Payroll/generate';
$route['hr_module/payroll/view/(:num)']           = 'hr_module/Payroll/view/$1';
$route['hr_module/payroll/mark_paid/(:num)']      = 'hr_module/Payroll/mark_paid/$1';
$route['hr_module/payroll/delete/(:num)']         = 'hr_module/Payroll/delete/$1';
$route['hr_module/payroll/slip/(:num)']           = 'hr_module/Payroll/slip/$1';

// Payroll Items
$route['hr_module/payroll_items']                 = 'hr_module/Payroll_items/index';
$route['hr_module/payroll_items/add']             = 'hr_module/Payroll_items/add';
$route['hr_module/payroll_items/edit/(:num)']     = 'hr_module/Payroll_items/edit/$1';
$route['hr_module/payroll_items/delete/(:num)']   = 'hr_module/Payroll_items/delete/$1';

// Loans
$route['hr_module/loans']                         = 'hr_module/Loans/index';
$route['hr_module/loans/apply']                   = 'hr_module/Loans/apply';
$route['hr_module/loans/view/(:num)']             = 'hr_module/Loans/view/$1';
$route['hr_module/loans/approve/(:num)']          = 'hr_module/Loans/approve/$1';
$route['hr_module/loans/reject/(:num)']           = 'hr_module/Loans/reject/$1';
$route['hr_module/loans/delete/(:num)']           = 'hr_module/Loans/delete/$1';
$route['hr_module/loans/add_repayment/(:num)']    = 'hr_module/Loans/add_repayment/$1';

// Overtime
$route['hr_module/overtime']                      = 'hr_module/Overtime/index';
$route['hr_module/overtime/request']              = 'hr_module/Overtime/request';
$route['hr_module/overtime/preview']              = 'hr_module/Overtime/preview';
$route['hr_module/overtime/edit/(:num)']          = 'hr_module/Overtime/edit/$1';
$route['hr_module/overtime/view/(:num)']          = 'hr_module/Overtime/view/$1';
$route['hr_module/overtime/approve/(:num)']       = 'hr_module/Overtime/approve/$1';
$route['hr_module/overtime/reject/(:num)']        = 'hr_module/Overtime/reject/$1';
$route['hr_module/overtime/delete/(:num)']        = 'hr_module/Overtime/delete/$1';

// Performance
$route['hr_module/performance']                   = 'hr_module/Performance/index';
$route['hr_module/performance/add']               = 'hr_module/Performance/add';
$route['hr_module/performance/edit/(:num)']       = 'hr_module/Performance/edit/$1';
$route['hr_module/performance/view/(:num)']       = 'hr_module/Performance/view/$1';
$route['hr_module/performance/delete/(:num)']     = 'hr_module/Performance/delete/$1';
$route['hr_module/performance/update_status/(:num)'] = 'hr_module/Performance/update_status/$1';
$route['hr_module/performance/add_feedback/(:num)']  = 'hr_module/Performance/add_feedback/$1';
$route['hr_module/performance/employee_report/(:num)'] = 'hr_module/Performance/employee_report/$1';
$route['hr_module/performance/add_sub_target/(:num)']  = 'hr_module/Performance/add_sub_target/$1';
$route['hr_module/performance/edit_sub_target/(:num)'] = 'hr_module/Performance/edit_sub_target/$1';
$route['hr_module/performance/delete_sub_target/(:num)'] = 'hr_module/Performance/delete_sub_target/$1';

// Training
$route['hr_module/training']                      = 'hr_module/Training/index';
$route['hr_module/training/add']                  = 'hr_module/Training/add';
$route['hr_module/training/edit/(:num)']          = 'hr_module/Training/edit/$1';
$route['hr_module/training/view/(:num)']          = 'hr_module/Training/view/$1';
$route['hr_module/training/delete/(:num)']        = 'hr_module/Training/delete/$1';
$route['hr_module/training/enroll/(:num)']                     = 'hr_module/Training/enroll/$1';
$route['hr_module/training/remove_participant/(:num)/(:num)']  = 'hr_module/Training/remove_participant/$1/$2';
$route['hr_module/training/mark_attendance/(:num)/(:num)']     = 'hr_module/Training/mark_attendance/$1/$2';
$route['hr_module/training/mark_complete/(:num)']              = 'hr_module/Training/mark_complete/$1';
$route['hr_module/training/mark_daily_attendance/(:num)/(:num)'] = 'hr_module/Training/mark_daily_attendance/$1/$2';
$route['hr_module/training/report/(:num)']                     = 'hr_module/Training/report/$1';
$route['hr_module/training/email_report/(:num)']               = 'hr_module/Training/email_report/$1';
$route['hr_module/training/save_employee_note/(:num)/(:num)']     = 'hr_module/Training/save_employee_note/$1/$2';
$route['hr_module/training/save_employee_feedback/(:num)/(:num)'] = 'hr_module/Training/save_employee_feedback/$1/$2';

// Helpdesk
$route['hr_module/helpdesk']                      = 'hr_module/Helpdesk/index';
$route['hr_module/helpdesk/submit']               = 'hr_module/Helpdesk/submit';
$route['hr_module/helpdesk/view/(:num)']          = 'hr_module/Helpdesk/view/$1';
$route['hr_module/helpdesk/reply/(:num)']         = 'hr_module/Helpdesk/reply/$1';
$route['hr_module/helpdesk/save_note/(:num)']     = 'hr_module/Helpdesk/save_note/$1';
$route['hr_module/helpdesk/close/(:num)']         = 'hr_module/Helpdesk/close/$1';
$route['hr_module/helpdesk/delete/(:num)']        = 'hr_module/Helpdesk/delete/$1';

// Contracts — URL uses hr_contracts so ucfirst() resolves to Hr_contracts.php
$route['hr_module/hr_contracts']                       = 'hr_module/Hr_contracts/index';
$route['hr_module/hr_contracts/add']                   = 'hr_module/Hr_contracts/add';
$route['hr_module/hr_contracts/edit/(:num)']           = 'hr_module/Hr_contracts/edit/$1';
$route['hr_module/hr_contracts/view/(:num)']           = 'hr_module/Hr_contracts/view/$1';
$route['hr_module/hr_contracts/delete/(:num)']         = 'hr_module/Hr_contracts/delete/$1';
$route['hr_module/hr_contracts/sign/(:num)']           = 'hr_module/Hr_contracts/sign/$1';
$route['hr_module/hr_contracts/set_status/(:num)']     = 'hr_module/Hr_contracts/set_status/$1';

// Reports
$route['hr_module/reports']                       = 'hr_module/Reports/index';
$route['hr_module/reports/attendance']            = 'hr_module/Reports/attendance';
$route['hr_module/reports/leave']                 = 'hr_module/Reports/leave';
$route['hr_module/reports/payroll']               = 'hr_module/Reports/payroll';
$route['hr_module/reports/loan']                  = 'hr_module/Reports/loan';
$route['hr_module/reports/overtime']              = 'hr_module/Reports/overtime';
$route['hr_module/reports/performance']           = 'hr_module/Reports/performance';
$route['hr_module/reports/training']              = 'hr_module/Reports/training';
$route['hr_module/reports/headcount']             = 'hr_module/Reports/headcount';
$route['hr_module/reports/department']            = 'hr_module/Reports/department';
$route['hr_module/reports/salary']                = 'hr_module/Reports/salary';
$route['hr_module/reports/turnover']              = 'hr_module/Reports/turnover';

// Policies
$route['hr_module/policies']                          = 'hr_module/Policies/index';
$route['hr_module/policies/add']                      = 'hr_module/Policies/add';
$route['hr_module/policies/edit/(:num)']               = 'hr_module/Policies/edit/$1';
$route['hr_module/policies/view/(:num)']               = 'hr_module/Policies/view/$1';
$route['hr_module/policies/delete/(:num)']             = 'hr_module/Policies/delete/$1';
$route['hr_module/policies/approve/(:num)']            = 'hr_module/Policies/approve/$1';
$route['hr_module/policies/reject/(:num)']             = 'hr_module/Policies/reject/$1';
$route['hr_module/policies/approve_revision/(:num)']   = 'hr_module/Policies/approve_revision/$1';
$route['hr_module/policies/reject_revision/(:num)']    = 'hr_module/Policies/reject_revision/$1';

// Shifts
$route['hr_module/shifts']                  = 'hr_module/Shifts/index';
$route['hr_module/shifts/apply']            = 'hr_module/Shifts/apply';
$route['hr_module/shifts/view/(:num)']      = 'hr_module/Shifts/view/$1';
$route['hr_module/shifts/approve/(:num)']   = 'hr_module/Shifts/approve/$1';
$route['hr_module/shifts/reject/(:num)']    = 'hr_module/Shifts/reject/$1';
$route['hr_module/shifts/delete/(:num)']    = 'hr_module/Shifts/delete/$1';
