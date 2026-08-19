# HR Module — Developer Documentation

**Module slug:** `hr_module` · **Display name:** HR Management · **Version:** 1.0.0
**Requires:** Perfex CRM 3.3.x (CodeIgniter 3 MVC) · **Author:** Alpha Net BD
**Schema version:** see `HR_MODULE_SCHEMA_VERSION` in `hr_module.php` (currently `4`)

This document describes the module **as it exists today** — architecture, database schema, permission model, settings, integrations, and the conventions to follow when extending it. It is kept in the repo next to the code so it stays versioned alongside it.

---

## 1. Ground rules

These are non-negotiable and every file in this module already follows them:

1. **Never modify Perfex core.** Everything is implemented as an independent, installable module under `modules/hr_module/`. Core behavior that needs adjusting (e.g. the sidebar's active-link highlighting) is patched from *this* module via hooks, not by editing core files — see [§9.3](#93-sidebar-active-link-fix).
2. **Use Perfex's own systems** — module hooks, the staff capability/permission system, the default admin UI components (`selectpicker`, the `.datepicker`/`.datetimepicker` markup, DataTables via `render_datatable()`/`get_table_data()`), language files, and the cron hook — instead of inventing parallel infrastructure.
3. **Idempotent schema.** `install.php` is written so it can run against a brand-new database *or* an already-populated one without erroring — every `CREATE TABLE` is guarded with `table_exists()` and every added column is guarded with a `SHOW COLUMNS ... LIKE` check. See [§4.2](#42-schema-versioning--auto-migration).
4. **Attachments are never served as static files** — every uploaded file (contracts, loan documents, payslips, policy PDFs, ticket/reply attachments, training materials, employee photos) is streamed through a permission-checked controller action, and every `uploads/hr_module/*` directory gets a `Deny from all` `.htaccess` dropped into it the first time it's created (`hr_lock_upload_dir()`).

---

## 2. Directory layout

```
modules/hr_module/
├── hr_module.php              # Module bootstrap: hooks, menu, permissions, cron, schema-version guard
├── install.php                # Idempotent schema (CREATE/ALTER, guarded)
├── uninstall.php              # Drops tables only if allow_data_removal_on_uninstall is on
├── config/routes.php          # Explicit route map
├── controllers/                # One controller per feature area (see §3)
├── models/                     # One model per controller, plus Hr_module_model (shared helpers)
├── views/<feature>/            # index / form / view / table (DataTable AJAX endpoints)
├── libraries/
│   ├── Waha_lib.php            # WhatsApp (WAHA) HTTP client
│   └── Zkteco_lib.php           # ZKTeco binary TCP protocol client
├── language/english/hr_module_lang.php
└── assets/css|js/               # Placeholders only — styling is Tailwind utility classes (tw-*) inline in views
```

Routing follows `admin/hr_module/<controller>/<method>` (see `config/routes.php` for the explicit map, including aliases like `hr_contracts`).

---

## 3. Controllers (feature inventory)

Every controller extends `AdminController`. All permission checks use the `hr_<feature>` capability namespace (e.g. `hr_leave`, `hr_payroll`).

| Controller | Feature | Capability | Notes |
|---|---|---|---|
| `Hr_module` | Dashboard (landing page) | — | Renders "My Dashboard" / "Company Dashboard" tabs — see [§8](#8-dashboard) |
| `Employees` | Employee directory & profiles | `hr_employees` | Identity fields sync from `tblstaff`; carries the per-employee `max_loan_amount` override; Add form auto-selects Department from the linked staff's existing Perfex core department (`tblstaff_departments`); `add()` auto-allocates the new employee's leave balances for the current year (§3.4) |
| `Departments` | Department list | `hr_departments` | Read-only here — add/edit/delete are stubs; departments are managed via Perfex core Setup |
| `Designations` | Job titles | `hr_departments` | No separate capability; reached from Settings > Company Structure |
| `Leave` | Leave requests | `hr_leave` (+ `approve`, `soft_approve`, `view_department`) | Per-day type (full/half/hourly/bridge) or date-range; cancellation request sub-flow; gender-restricted types (§3.2); soft-approval pre-step (§3.3) |
| `Leave_types` | Leave type config | `hr_leave` | Days/year, hours/day, carry-forward + cap, half-day/attachment/date-range flags, optional gender restriction (§3.2) |
| `Leave_balances` | Yearly balance grid + bulk allocation | `hr_leave` | `allocate()` needs admin or `approve`; now a standard `render_datatable()`/AJAX list (was a plain PHP-loop table) |
| `Attendance` | Daily attendance | `hr_attendance` | Manual CRUD, monthly calendar, range report, **multi-format import** (CSV template, ZKTeco report CSV/XLSX, raw `.dat`/`.txt` ATTLOG) |
| `Shifts` | Shift assignment requests | `hr_shifts` (+ `approve`, `soft_approve`, `view_department`) | One request can cover multiple dates, each with its own shift type; soft-approval pre-step (§3.3) |
| `Overtime` | Overtime requests | `hr_overtime` (+ `soft_approve`, `view_department`) | Batched multi-date; self-service requests are restricted to the **current calendar month**; soft-approval pre-step (§3.3) |
| `Payroll` | Payslips | `hr_payroll` | Bulk `generate()` over selected employees/month, skips already-generated |
| `Payroll_items` | Allowance/deduction components | `hr_payroll` | Fixed or percentage; taxable flag |
| `Loans` | Employee loans | `hr_loans` | See [§7](#7-loan-capacity-model) for the capacity calculation; deduction-request sub-workflow (skip/adjust a month's installment) |
| `Performance` | Targets & sub-targets | `hr_performance` (+ `view_department`) | `view()` also opens to any staff assigned as an **evaluator**, regardless of ownership |
| `Training` | Training programs | `hr_training` (+ `view_department`) | `view()`/attendance-marking also open to the assigned **instructor** and enrolled participants |
| `Helpdesk` | HR ticketing | `hr_helpdesk` | Supports anonymous tickets |
| `Hr_contracts` | Employment contracts | `hr_contracts` | Auto-expires past `end_date`; 30-day expiry warnings via cron |
| `Policies` | HR policies | `hr_policies` | Department-scoped managers, publish/revision workflow, approval restricted to a configured approver list (see [§3.1](#31-policies-access-model)) |
| `Holidays` | Official calendar + weekly-off config | `hr_holidays` | `get_for_year()` is an unauthenticated-within-admin AJAX endpoint consumed by the leave-apply form |
| `Zkteco` | Biometric device management | `hr_zkteco` | See [§10](#10-zkteco-device-integration) |
| `Reports` | Reporting hub | `hr_reports` (`view` only) | 11 report types: attendance, leave, payroll, loan, overtime, performance, training, headcount, department, salary, turnover |
| `Settings` | Module configuration | `hr_settings` | See [§6](#6-settings-reference) |
| `Email_templates` | Editable notification bodies | `hr_settings` | ~26 seeded templates |
| `Whatsapp_templates` | Editable WhatsApp broadcast bodies | `hr_settings` | 5 seeded templates |
| `Demo_data` | Demo/seed data tool | `is_admin()` only | Seeds/resets a full demo dataset; no sidebar entry |

### 3.1 Policies access model

`Policies` doesn't use the plain own_only idiom — it has three layers:
- `_is_global_manager()` = `is_admin() || staff_can('view', 'hr_policies')` → sees everything.
- A **department-scoped manager** (only `view_own` + create/edit) may only manage policies targeting their own department.
- Everyone else sees only **published** policies that are `public` or target their own department.
- Approval is restricted to the staff IDs in the `policy_approver_ids` setting, **falling back to any admin** while that setting is empty.

### 3.2 Gender-restricted leave types

`hr_leave_types.gender` (`NULL` | `'male'` | `'female'`) optionally locks a type to one gender — seeded on install as `Maternity Leave → female`, `Paternity Leave → male`, everything else `NULL` (open to anyone). Enforced in two layers:

- **Server-side (authoritative):** `Leave_model::apply()` compares the applying employee's `hr_employees.gender` against the type's `gender` (case-insensitive) and rejects with `hr_leave_gender_mismatch` on a mismatch, before any other validation.
- **Client-side (UX only):** `views/leave/apply.php` hides (not just disables — bootstrap-select's `[hidden]`/`data-hidden` option filtering) any leave-type `<option>` whose `data-gender` doesn't match the selected employee's gender, via a `gEmployeeGenders` map (`employee_id → gender`) preloaded from `Hr_module_model::get_active_employees_genders()`. Works identically for every role — an admin picking a different employee re-triggers the same filter, since it's keyed off the employee dropdown, not the viewer's identity.

Editable per-type from the Leave Type add/edit form (a plain "Any / Male / Female" `<select name="gender">`).

### 3.3 Soft approval (`soft_approve`)

Leave, Shifts, and Overtime each support an **informational-only pre-approval step**: a staff member holding the feature's `soft_approve` capability (e.g. a department head, granted via **Setup > Staff > Roles** like any other capability — there is no separate "assign a department head" screen) can record their own approve/reject on a still-pending request. It is purely advisory:

- It **never blocks or gates** the real `approve()`/`reject()` — those work exactly as before, regardless of the soft decision (or lack of one).
- It's shown on the request's detail page as an extra "Soft Approved/Rejected by X — timestamp" line, independent of the real "Approved/Rejected by" line.
- Storage: `soft_status` (`'approved'`|`'rejected'`), `soft_approved_by` (staff id), `soft_approved_at` — one column set per table (`hr_leave_requests`, `hr_shift_assignments`, `hr_overtime`), added via a **self-healing constructor check** in each model (`_ensure_soft_approval_columns()`), not via `install.php`/schema version — see [§4.2](#42-schema-versioning--auto-migration) for why.
- Only actionable while the request's overall `status` is still `pending`; Overtime's version acts on the whole `batch_id`, matching how its real `approve()`/`reject()` already do.
- Routes: `soft_approve/(:num)` and `soft_reject/(:num)` alongside each feature's existing `approve`/`reject` routes.

### 3.4 Leave balance auto-allocation on employee creation

`Employees::add()` calls `Leave_model::allocate_for_employee($employee_id)` immediately after a new HR profile is created, so the employee has this year's balance rows (and therefore shows up on the Leave Balances page and can apply for leave) without waiting for the next site-wide `Leave_balances::allocate()` run. `allocate_for_employee()` shares its row-creation logic (`_allocate_balance_row()`, including carry-forward math) with `allocate_balances()` — same behavior, just scoped to one employee. **This is forward-looking only** — it doesn't retroactively backfill employees created before this feature existed; run `Leave_balances::allocate()` once to cover those.

---

## 4. Database schema

### 4.1 Tables

40 tables are created in `install.php`; 2 more (`hr_email_templates`, `hr_whatsapp_templates`) are created lazily by their models on first use. All are dropped by `uninstall.php`, but only if `allow_data_removal_on_uninstall` was explicitly turned on in Settings — **uninstall preserves data by default**.

| Table | Purpose |
|---|---|
| `hr_departments` | Department tree (parent_id, head_staff_id) |
| `hr_designations` | Job titles |
| `hr_employees` | Core employee master — linked to `tblstaff.staffid`, personal/bank/statutory data, salary, `max_loan_amount` override |
| `hr_leave_types` | Leave type configuration, incl. optional `gender` restriction (§3.2) |
| `hr_leave_requests` / `hr_leave_request_days` | Leave request header (incl. `soft_status`/`soft_approved_by`/`soft_approved_at`, §3.3) / per-day breakdown |
| `hr_leave_balances` | Per employee/type/year allocated-used-carried days |
| `hr_attendance` | Daily punch record (in/out, hours, status, source, device) |
| `hr_zkteco_devices` / `hr_zkteco_mapping` / `hr_zkteco_sync_logs` | Biometric device registry, user-id↔employee mapping, sync audit |
| `hr_payroll_items` | Reusable earning/deduction components |
| `hr_payroll` / `hr_payroll_details` | Payslip header / line items |
| `hr_loans` / `hr_loan_repayments` / `hr_loan_deduction_requests` | Loan record, repayments, monthly skip/adjust requests |
| `hr_overtime` | Overtime request (batch_id groups a multi-date submission; `soft_status`/`soft_approved_by`/`soft_approved_at`, §3.3) |
| `hr_performance_reviews` / `hr_performance_tasks` (+ evaluators/feedback) | Legacy review/task model (superseded) |
| `hr_performance_targets` / `hr_performance_sub_targets` (+ evaluators/feedback) | Current performance model |
| `hr_training` / `hr_training_participants` / `hr_training_attendance` / `hr_training_sessions` | Training programs, enrolment, per-session attendance |
| `hr_helpdesk` / `hr_helpdesk_replies` | Tickets and threaded replies |
| `hr_contracts` | Employment contracts |
| `hr_audit_trail` | Generic audit log (module, action, record id, old/new value, actor, IP) — **no UI screen exists yet** |
| `hr_settings` | Key/value settings store (also holds `_schema_version`) |
| `hr_holidays` | Official calendar entries |
| `hr_policies` / `hr_policy_revisions` | Policy record and pending-revision queue |
| `hr_shift_types` / `hr_shift_assignments` | Named shifts, employee assignment requests (`hr_shift_assignments` also carries `soft_status`/`soft_approved_by`/`soft_approved_at`, §3.3) |

### 4.2 Schema versioning & auto-migration

```php
define('HR_MODULE_SCHEMA_VERSION', 4);   // hr_module.php
```

`hr_module_ensure_schema()` is hooked on `admin_init` and runs on **every** admin page load. It's cheap (one `table_exists()` + one row read) and short-circuits immediately once the site is current:

```php
function hr_module_ensure_schema() {
    if (!$CI->db->table_exists(db_prefix().'hr_settings')) return;
    $applied = (int) get_setting('_schema_version', 0);
    if ($applied >= HR_MODULE_SCHEMA_VERSION) return;
    require_once(__DIR__.'/install.php');
    hr_module_mark_schema_current();
}
```

**Rule for every future change:** if you add a table or column to `install.php`, guard it (`table_exists()` before CREATE, `SHOW COLUMNS ... LIKE` before ALTER), and **bump `HR_MODULE_SCHEMA_VERSION`**. This is what lets an already-activated site pick up the change automatically on its very next page load — no manual deactivate/reactivate, no manual SQL. This exact mechanism is what fixed a real production 500 error (a column existed in `install.php` but had never actually been added to an already-installed site's table, because nothing had re-run the migration).

**A second, independent self-healing path exists for individual models.** `Leave_model`, `Shifts_model`, and `Overtime_model` each run their own guarded `SHOW COLUMNS ... LIKE` + `ALTER TABLE` check **inside their constructor** (e.g. `Leave_model::_ensure_cancellation_columns()`, `_ensure_soft_approval_columns()` on all three) — completely bypassing `install.php`/`HR_MODULE_SCHEMA_VERSION`. This is deliberate, not an oversight: it means the column self-heals the instant that specific model is touched, regardless of whether the table was created by `install.php` (`hr_leave_requests`, `hr_overtime`) or lazily by the model itself on first use (`hr_shift_assignments`, via `Shifts_model::_ensure_tables()`) — sidestepping any ordering question about which mechanism runs first. **Use this pattern instead of the schema-version path** when: the column belongs to a table already known to be model-owned/lazily-created, or when you want the fix to land the moment that one model is used rather than waiting for the next full admin page load's `admin_init` hook. Use the `install.php`/schema-version path for everything else (new tables, or columns on tables `install.php` itself owns) — it's the more visible, centrally-documented mechanism and should stay the default.

---

## 5. Permission system

### 5.1 Registration

`hr_module_register_permissions()` (hooked on `admin_init`) registers 17 capability groups, built up from five reusable sets (each just `array_merge()`s onto the previous one):

- **`$cap_personal`** = `view_own`, `view`, `create`, `edit`, `delete`
- **`$cap_personal_approve`** = `$cap_personal` + `approve` — used by `hr_payroll` and `hr_loans`
- **`$cap_personal_soft_approve`** = `$cap_personal_approve` + `soft_approve` (§3.3)
- **`$cap_personal_dept`** = `$cap_personal` + `view_department` — used by `hr_performance`, `hr_training`
- **`$cap_personal_soft_approve_dept`** = `$cap_personal_soft_approve` + `view_department` — used by `hr_leave`, `hr_overtime`, `hr_shifts` (the only three features with both the soft-approval step and department-scoped viewing)
- **`$cap_config`** = `view`, `create`, `edit`, `delete` (no `view_own`) — used by `hr_departments`, `hr_zkteco`

> `hr_payroll` and `hr_loans` also register an `approve` capability, but their controllers currently gate approve/reject on `edit`, not `approve`. Worth knowing if you're auditing role permissions — the checkbox exists but isn't wired to anything yet. `hr_overtime`'s real `approve()`/`reject()` similarly gate on `edit`, not `approve` — but its `soft_approve` capability (added later, §3.3) *is* correctly wired to its own dedicated `soft_approve()`/`soft_reject()` actions.

### 5.1.1 Department-scoped viewing (`view_department`)

A third visibility tier alongside `view` (everyone) and `view_own` (just yourself) — sees every record belonging to employees in **the viewer's own department**, without needing company-wide `view`. Available on `hr_leave`, `hr_overtime`, `hr_shifts`, `hr_performance`, `hr_training`. Key points:

- **No department-to-head mapping exists or is needed.** It's a plain capability, assigned to whichever staff/role should have it via **Setup > Staff > Roles**, exactly like every other capability in this module. (An earlier `head_staff_id` column on a legacy, now-unused `hr_departments` table was considered and explicitly rejected — employees actually link to Perfex's **core** `tbldepartments` via `hr_employees.department_id`, which has no head/manager concept of its own.)
- `hr_get_own_department_id()` (in `hr_module.php`, alongside `hr_get_own_employee_id()`) resolves the caller's own `hr_employees.department_id`, or `0` if none.
- **List pages:** each feature's `table.php` sets `$filters['department_id'] = hr_get_own_department_id()` instead of `$filters['employee_id']`/`own_or_evaluator`/`own_or_instructor` when the viewer has `view_department` but not `view`. `Leave_model`/`Shifts_model`/`Overtime_model`/`Performance_model` all already accepted a plain `department_id` filter for their admin-facing department dropdown, so no model change was needed for those three — `Training_model::get_for_table()` needed a new `department_id` branch (an `EXISTS` join through `hr_training_participants` → `hr_employees`, since one training has many participants, possibly across departments) and `Leave_model::get_request()` needed one added.
- **Direct-link access (`view($id)`):** each controller's detail-view gate was extended so a department match (comparing the record's `employee_department_id` — added to each model's single-row `SELECT` — against the viewer's own) grants access even without `view`/`view_own`, closing the gap where someone could otherwise be blocked on the list but still open another department's record by URL.
- **Training is the one genuine special case:** since a training has multiple participants, "their department" means *any* participant from that department, not a single `employee_id` comparison — see `Training_model::has_department_participant()`/`has_department_training()`.

### 5.2 The `own_only` idiom

This is the single most important pattern in the module — nearly every self-service feature uses it verbatim:

```php
$own_only   = !is_admin() && !staff_can('view', 'hr_xxx');
$own_emp_id = $own_only ? hr_get_own_employee_id() : 0;
```

What it produces, end to end:

1. **List/entry gate** accepts either capability: `if (staff_cant('view','X') && staff_cant('view_own','X')) access_denied('X');`
2. **Record-level gate** on detail/download actions: if the caller only has `view_own`, compare `$record->employee_id` against `hr_get_own_employee_id()` and deny otherwise.
3. **Anti-spoofing on create**: when `$own_only` is true, the posted `employee_id` is **discarded** and replaced with `$own_emp_id` server-side — a self-service user cannot file a request on someone else's behalf by editing form fields.
4. **UI narrowing**: the employee dropdown collapses to a single disabled option; `$own_only` is passed to the view so it can hide manager-only controls (department filters, other employees' data).
5. **Data narrowing**: list queries and any preloaded JSON (balance maps, capacity maps) switch to an own-employee-only query instead of the whole-company one.

`hr_get_own_employee_id()` (in `hr_module.php`) resolves `get_staff_user_id()` → `hr_employees.id` via `Employees_model::get_by_staff_id()`, returning `0` if the logged-in staff member has no linked HR profile.

On the five features that also register `view_department` (§5.1.1), the gates above are extended with a third branch rather than replaced — `view` still means everyone, `view_own` still means just the caller's own records, and `view_department` sits between them.

**Known deviations from the plain pattern** (each for a good reason — don't "fix" these into the generic shape):
- `Employees` has no own-only *list* — a `view_own` staff member sees their own data through the Dashboard, not the directory (the sidebar entry itself is hidden for them).
- `Performance::view()` also opens to any staff assigned as an **evaluator** on one of the target's sub-targets, regardless of employee ownership.
- `Training` also opens to the assigned **instructor** and to enrolled participants with `view_own`.
- `Policies` uses department scoping instead of per-employee ownership (see [§3.1](#31-policies-access-model)).
- `Overtime` and `Shifts` let the owner edit/delete their own request while `create`-only and status is still `pending`.

### 5.3 "Broken link" bug class — a recurring lesson

A UI element that's visible to a role but whose destination action requires a *different* capability than the one gating the element is a real, recurring bug class in this codebase (multiple department-filter and quick-action buttons have shipped with this mismatch and been fixed after the fact). **When adding or changing any button/link visibility check, verify it matches exactly what the destination controller action actually requires** — not a looser or tighter check. A user with *more* privilege (e.g. full `view`) should never see *fewer* UI elements than one with only `view_own`.

---

## 6. Settings reference

All settings live in the generic key/value `hr_settings` table and are read via `Hr_module_model::get_setting($key, $default)` (cached per-request) and written via `save_settings($array)`. Only keys listed in `Settings::_save_settings()`'s `$allowed_keys` array are persisted from the form; 12 numeric keys are additionally range-validated **server-side** (the view's `min`/`max` HTML attributes are not trusted as the enforcement).

| Key | Purpose |
|---|---|
| `employee_id_prefix` | Prefix for auto-generated employee codes |
| `currency` | Display currency |
| `fiscal_year_start_month` (1–12) | HR fiscal year start |
| `payroll_generation_day` (1–31) | Expected monthly payroll run day |
| `default_max_loan_amount` (0–99,999,999.99) | Site-wide loan ceiling — see [§7](#7-loan-capacity-model) |
| `working_days_per_week` (1–7), `working_hours_per_day` (1–24) | Baseline working schedule |
| `office_start_time`, `office_end_time` | Baseline office hours |
| `late_threshold_minutes` (0–120) | Grace period before a punch is flagged "late" |
| `default_overtime_rate` (1–5), `overtime_holiday_rate` (1–5) | OT pay multipliers (normal day / holiday-or-weekly-off) |
| `overtime_day_divisor` (1–31) | Divides monthly salary into a daily rate for OT amount calculation |
| `shift_allowance_evening_amount`, `shift_allowance_night_amount` | Per-shift allowance amounts |
| `weekly_off_days` | **Not in `$allowed_keys`** — saved separately by `Holidays::save_weekly_off()` as CSV of day indexes (0=Sun..6=Sat) |
| `hr_notification_email` | Single inbox that receives every "request submitted" HR notification |
| `policy_approver_ids` | CSV of staff IDs allowed to approve policies (empty = any admin) |
| `notify_leave_apply`, `notify_leave_approve`, `notify_leave_cancellation`, `notify_loan_apply`, `notify_loan_approve`, `notify_loan_deduction`, `notify_overtime`, `notify_helpdesk`, `notify_shift`, `notify_policy`, `notify_training`, `notify_payroll` | Per-event email toggles — **default enabled** when never saved |
| `whatsapp_enabled` | WhatsApp master switch |
| `whatsapp_base_url`, `whatsapp_session`, `whatsapp_api_key`, `whatsapp_group_id`, `whatsapp_phone_number` | WAHA connection + broadcast target; `api_key` is a password field never re-rendered (blank submit = keep existing) |
| `whatsapp_notify_leave_announcement`, `whatsapp_notify_leave_cancellation_announcement`, `whatsapp_notify_holiday_reminder`, `whatsapp_notify_policy_announcement` | Per-event WhatsApp toggles |
| `holiday_reminder_enabled`, `holiday_reminder_time` | Day-before holiday broadcast (cron-driven) |
| `zkteco_enabled`, `zkteco_sync_interval` (5–1440 min) | Biometric device auto-sync |
| `allow_data_removal_on_uninstall` | Admin-only "Danger Zone" flag — deliberately excluded from `$allowed_keys`, written only when `is_admin()` |
| `_schema_version` | Internal — not user-editable |

---

## 7. Loan capacity model

`max_loan_amount` is a **revolving cap on total current exposure**, not a per-request limit. Added in [`Loans.php`](controllers/Loans.php):

```
remaining capacity = ceiling − SUM(outstanding) across this employee's
                      APPROVED and ACTIVE loans only
ceiling = employee's own hr_employees.max_loan_amount if set,
          otherwise the site-wide default_max_loan_amount setting,
          otherwise the absolute hr_loans.amount column ceiling (99,999,999.99)
```

- **Pending** requests don't count against the cap — a request is only "real" exposure once approved.
- **Rejected** loans never happened — excluded.
- **Closed** (fully repaid) loans free up their capacity automatically, since the sum is over `outstanding`, not the original `amount`.
- The employee-facing hint on the apply form ("You can request up to X more...") is computed from a **preloaded map** (one query for all employees' ceilings, one aggregate query for all employees' exposure) rather than a per-selection AJAX call — see [§9.1](#91-preload-instead-of-per-selection-ajax).
- Enforcement is server-side in `Loans::apply()` (`_get_remaining_loan_capacity()`); the client-side hint and `max` attribute are a courtesy, not the actual boundary.

---

## 8. Dashboard

`Hr_module::index()` computes two independent booleans:

```php
$is_manager  = is_admin() || staff_can('view', 'hr_employees');
$employee_id = hr_get_own_employee_id();
```

- **Both true** (an HR manager/admin who is *also* a company employee): the view renders a `nav-tabs` switch — **"My Dashboard"** (personal stats, active by default) and **"Company Dashboard"** (managerial stats) — both computed and both rendered, switching is pure CSS/Bootstrap tab machinery, no re-fetch.
- **Only `employee_id`**: personal dashboard only, no tabs (identical to pre-tab behavior).
- **Only `is_manager`**: managerial dashboard only, no tabs (identical to pre-tab behavior).
- **Neither**: "no HR profile linked" placeholder.

Personal-dashboard widgets and their data sources (`Hr_module_model::get_employee_dashboard_stats()`):

| Widget | Source |
|---|---|
| Today's Attendance | `hr_attendance` row for today |
| Leave Balance | `SUM(allocated + carry_forward − used)` across `hr_leave_balances`, **filtered to the `Casual Leave` type only** (joined via `hr_leave_types.name`) for the current year — deliberately one specific type, not a combined total across every leave type |
| Pending / Approved Leaves | `hr_leave_requests` counts |
| Net Salary | Current-or-previous-month `hr_payroll` row — **masked behind an eye-toggle by default** (client-side only; the value is already the viewer's own, so this is a glance-privacy feature, not an access boundary) |
| Loan Outstanding | Latest `hr_loans` row with status **`approved` or `active`** (matches the definition used everywhere else in the module — `Loans_model`, the Loans list, the loan detail page — a loan stays `approved` until its first payroll deduction flips it to `active`; checking `active` alone missed a just-approved loan with no payroll cycle run against it yet) |
| Overtime (this month) | **Count of approved `hr_overtime` day-rows** for the current month (+ any-time pending count) — originally summed a legacy `hours` column that `Overtime_model` never actually populates (overtime is tracked per day, not hourly), so it always read `0.0`; fixed to count days and relabeled the widget from "hrs" to "day"/"days" |
| Performance | Latest sub-target across the employee's targets + open (pending/in_progress/partially_completed) count |
| Upcoming Training | Up to 3 enrolled, not-yet-completed trainings with status `scheduled`/`in_progress` |

Managerial-dashboard widgets come from `get_dashboard_stats()`: total/active employees, department count, present/late/on-leave today, pending leave/loan/overtime counts.

**Quick Action button visibility bug class:** several buttons in earlier versions checked `view_own` only, with no `view` fallback — meaning a user with *full* `view`/`create`/`edit`/`delete` (but not the separately-grantable `view_own` checkbox) saw *fewer* buttons than a plain self-service employee would. Fixed by adding `staff_can('view', X) || staff_can('view_own', X)` everywhere a personal-dashboard shortcut is gated. Keep this in mind for any new dashboard widget.

---

## 9. Performance & UI patterns worth reusing

### 9.1 Preload instead of per-selection AJAX

Two features (leave balance on the apply form, loan capacity on the apply form) originally fired a fresh AJAX request every time the employee/type selection changed. In this environment, the full CodeIgniter admin bootstrap costs roughly 1–2 seconds *per request*, independent of query complexity (the actual DB query is sub-millisecond) — so any feature that fires a request per user interaction will feel slow regardless of how well-indexed the underlying query is.

The fix, applied to both features and worth reusing for any similar "pick an employee, see their numbers" widget: compute the *entire relevant dataset* once in the controller (one query, or one small aggregate query, covering every employee the current viewer could possibly select), serialize it to a JSON map keyed by employee id, and have the client-side JS do a local object lookup on selection change. Fall back to the original AJAX endpoint only for a combination genuinely not present in the preloaded set (kept alive for robustness, effectively dead code in the common case).

### 9.2 `_l()` and the `%s` placeholder trap

Perfex's `_l($line, $label = '')` helper **always** runs the language string through `sprintf()`/`vsprintf()` internally — even when you don't pass a `$label`, it sprintfs with `''`. That means:

```php
_l('some_key_with_percent_s_in_it');            // the %s gets silently eaten, replaced with ''
sprintf(_l('some_key'), $value);                 // BROKEN — nothing left to substitute into
_l('some_key', $value);                          // CORRECT — pass the substitution as the label
_l('some_key', [$value1, $value2]);              // CORRECT for multiple %s — pass an array
```

If you need the **raw, un-substituted** template (e.g. to hand a string containing `%s` to client-side JS for its own later substitution), bypass `_l()` entirely and read `$this->lang->line('key')` directly.

### 9.3 Sidebar active-link fix

Perfex core's sidebar JS (`assets/js/main.js`) only marks a menu item active/expanded on an **exact URL match** against the registered `href` — so it works for the HR Dashboard link (no deeper subpages) but breaks for every other HR item one level below its list page (e.g. `hr_module/leave/apply`). Since that JS is core and shared by every module, it's patched from *this* module instead: `hr_module_sidebar_active_fix()` is hooked on `after_render_aside_menu` and injects a small script that opens the HR parent menu and highlights the longest-prefix-matching child whenever the current URL is anywhere under `hr_module`.

Implementation note if you ever touch this: don't use a fixed `window.addEventListener('load', ...)` to wait for core's sidebar plugin (metisMenu) to finish initializing — this script is injected early (inside `init_head()`), and core's own `load`-event registration for `main.js` happens later in body-order, so a plain `load` listener registered here would actually fire *before* metisMenu's init (same-event listeners fire in registration order, not DOM order). Instead, poll for metisMenu's own jQuery data key (`$('#side-menu').data('metisMenu')`) and proceed only once it's genuinely ready.

---

## 10. ZKTeco device integration

- **`libraries/Zkteco_lib.php`** — hand-rolled ZKTeco binary TCP protocol client over `fsockopen` (default port 4370, 10s timeout). Public entry point `fetch_attendance($ip, $port)`.
- **`models/Zkteco_model.php`** — device CRUD, connection test, `sync($device_id)` (fetch punches → resolve device user id to an employee via the mapping table → create/update the day's attendance row, recomputing working hours on a later punch → log the sync), `auto_sync_all_devices()` for cron.
- Punches enter the system through **three independent paths** that all resolve employees through the same mapping table: live device sync, cron auto-sync (`zkteco_enabled` + `zkteco_sync_interval`), and the Attendance screen's file import (CSV/XLSX/raw `.dat`/`.txt` ATTLOG export).

---

## 11. Notifications

### 11.1 Email

`Hr_module_model` provides three senders: `send_notification_email()` (to the single HR inbox), `send_employee_email()` (direct, e.g. approval outcomes), and `send_leave_announcement()`/`send_policy_announcement()` (company-wide BCC broadcast to every active linked employee). There's also an in-app bell-notification layer (`notify_staff()`, `notify_by_permission($capability, $feature, ...)`) that resolves recipients by testing `staff_can()` against every active staff member.

Every call site is wrapped in `notifications_enabled('notify_xxx')`, which **defaults to enabled** when the setting was never explicitly saved — a fresh install is never silently quiet. Templates live in the admin-editable `hr_email_templates` table, rendered with `{placeholder}` substitution.

### 11.2 WhatsApp (WAHA)

`Waha_lib` is a stateless client for a self-hosted WAHA (WhatsApp HTTP API) instance's `POST /api/sendText`. `Hr_module_model::send_whatsapp_announcement()` is the dispatcher — it's used **only for public broadcast announcements to a single configured WhatsApp group**, never for individual/HR-only notices or personal phone numbers. Gated by `whatsapp_enabled` + the specific per-event toggle + a non-empty `whatsapp_group_id`.

### 11.3 Cron (`hr_module_cron_tasks`, hooked on `after_cron_run`)

1. Auto-sync all ZKTeco devices (if enabled)
2. Auto-expire contracts past `end_date`
3. 30-day-ahead contract expiry warnings
4. Day-before holiday reminder broadcast (email + WhatsApp)

### 11.4 Activity logging

Every mutating action across the module — leave apply/approve/reject/cancel/soft-decide, holiday CRUD, contract CRUD/sign, loan apply/approve/reject/repay/deduction, overtime request/approve/reject/soft-decide, payroll generate/mark-paid/revert/delete, performance target/sub-target CRUD/status, training CRUD/enroll/attendance, ZKTeco device CRUD/sync, shift type CRUD/assignment delete/soft-decide, settings saves, and email/WhatsApp template edits — writes to Perfex's **core** activity log via the global `log_activity($description)` helper (no `load->helper()` needed). Convention, followed everywhere:

```php
log_activity('HR <Entity> <Action> [ID: ' . $id . ', <extra identifying info>]');
```

Always prefixed `'HR '`, always includes the relevant id(s), logged **only on the success path** (never inside an early-return failure branch), and passed with the implicit default `$staffid = null` (attributes to whoever's logged in, or shows as system-triggered for cron-driven events like the day-before holiday reminder or contract auto-expiry). Viewable at **Utilities > Activity Log** in the admin panel. Two deliberate exceptions to "log on success": WhatsApp/email notification *sends* only log on **failure** (a successful broadcast is expected/frequent and not worth a log line; a failed one is actionable).

---

## 12. Extending the module — checklist

When adding a new feature area, follow the established shape:

1. **Controller** extends `AdminController`; every action starts with a `staff_cant()`/`staff_can()` gate.
2. **Register the capability** in `hr_module_register_permissions()` using one of the existing `$cap_*` sets (§5.1) unless there's a genuine reason for a new shape.
3. **Add a sidebar entry** in `hr_module_init_menu_items()` if it's a top-level feature, gated on the same capability check used by the controller's `index()`.
4. **If self-service applies**, use the `own_only` idiom verbatim (§5.2) rather than inventing a new access shape.
5. **New table/column** → guard it in `install.php`, bump `HR_MODULE_SCHEMA_VERSION`, and confirm `uninstall.php`'s table-drop list covers it if `allow_data_removal_on_uninstall` should remove it. (Or use the self-healing constructor pattern instead — [§4.2](#42-schema-versioning--auto-migration) — for a column on an already model-owned/lazily-created table.)
6. **New mutating action** → add a `log_activity('HR ...')` call on the success path, following the convention in [§11.4](#114-activity-logging).
7. **New settings key** → add to `Settings::_save_settings()`'s `$allowed_keys` (and `$numeric_ranges` if it's a bounded number) — the `hr_settings` table itself needs no schema change, it's a generic key/value store.
8. **New notification** → add a `notify_xxx` toggle to Settings, wrap the send call in `notifications_enabled('notify_xxx')`, and add the email template to `Email_templates_model`'s seed list.
9. **Any UI element gated by permission** → double-check the visibility check matches exactly what the destination action requires (§5.3) — this is the single most common regression class in this module's history.
10. **Any per-selection dynamic UI** (a dropdown that changes a displayed number/hint) → prefer the preload-once-and-look-up-locally pattern (§9.1) over a fresh AJAX call per interaction.
11. **Live-verify, don't just lint.** Every fix in this module's history has been verified with real authenticated HTTP requests, real browser automation, or direct SQL/`php -l` execution against disposable test data, then cleaned up — not just "the code looks right."

---

## 13. Recent feature history

For context on *why* certain things look the way they do:

- **Per-employee/default maximum loan amount** — revolving capacity cap (§7), replacing an unlimited-amount loan apply flow.
- **HR dashboard tabs** — managers who are also employees now get both their personal and managerial dashboard instead of only the managerial one (§8).
- **Net salary masking** — hidden behind an eye-toggle by default on the personal dashboard.
- **Default-UI consistency pass** — a broad sweep converting plain `<select>`/native date inputs to Perfex's own `selectpicker`/datepicker widgets across most feature pages, and normalizing icon classes to FontAwesome 6 (`fa-regular`/`fa-solid`) where the old FA4-style classes weren't rendering.
- **Role-based visibility pass** — department filters, action buttons, and report controls across most feature areas narrowed to match what the destination action actually requires (§5.3).
- **HR permission label prefixing** — every HR capability group's display label in Setup > Roles now starts with "HR " to disambiguate from core Perfex permissions of similar name.
- **Leave Balances page → standard DataTable UI** (§3) — was a plain PHP-loop table with full-page-reload filtering; now `render_datatable()`/AJAX like every other list page in the module, same filters (department, year), same Allocate button.
- **Dashboard "Leave Balance" widget narrowed to Casual Leave only** (§8) — was a combined total across every leave type.
- **Gender-restricted leave types** (§3.2) — `hr_leave_types.gender`, enforced server-side in `Leave_model::apply()` and hidden client-side on the apply form; Maternity/Paternity seeded female/male.
- **Activity logging sweep** (§11.4) — every mutating action across the module that wasn't already logged now writes to the core Activity Log; previously only a handful of features (Employees, Designations, Shifts, Policies) logged anything.
- **Soft approval step** (§3.3) — a new `soft_approve` capability on Leave/Shifts/Overtime lets a role-based reviewer (e.g. a department head) record an informational pre-approval, purely advisory, never blocking the real approve/reject.
- **Department-scoped viewing (`view_department`)** (§5.1.1) — a new capability on Leave/Overtime/Shifts/Performance/Training between `view` and `view_own`: see everyone in your own department without full company-wide access.
- **Department auto-select on Add Employee** (§3.4) — the Department dropdown now pre-fills from the linked staff's existing Perfex core department, instead of always starting blank.
- **Leave balance auto-allocation on employee creation** (§3.4) — new employees get this year's leave balances immediately, instead of being invisible on the Leave Balances page/dashboard until the next manual "Allocate" run.
- **Dashboard "Overtime" and "Loan Outstanding" widget fixes** (§8) — Overtime now counts approved days instead of summing an always-zero legacy `hours` column; Loan Outstanding now matches `approved` or `active` status instead of `active` only.
