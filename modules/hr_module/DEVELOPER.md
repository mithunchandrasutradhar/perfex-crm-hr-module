# HR Module — Developer Documentation

**Module slug:** `hr_module` · **Display name:** HR Management · **Version:** 1.0.0
**Requires:** Perfex CRM 3.3.x (CodeIgniter 3 MVC) · **Author:** Alpha Net BD
**Schema version:** see `HR_MODULE_SCHEMA_VERSION` in `hr_module.php` (currently `3`)

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
| `Employees` | Employee directory & profiles | `hr_employees` | Identity fields sync from `tblstaff`; carries the per-employee `max_loan_amount` override |
| `Departments` | Department list | `hr_departments` | Read-only here — add/edit/delete are stubs; departments are managed via Perfex core Setup |
| `Designations` | Job titles | `hr_departments` | No separate capability; reached from Settings > Company Structure |
| `Leave` | Leave requests | `hr_leave` (+ `approve`) | Per-day type (full/half/hourly/bridge) or date-range; cancellation request sub-flow |
| `Leave_types` | Leave type config | `hr_leave` | Days/year, hours/day, carry-forward + cap, half-day/attachment/date-range flags |
| `Leave_balances` | Yearly balance grid + bulk allocation | `hr_leave` | `allocate()` needs admin or `approve` |
| `Attendance` | Daily attendance | `hr_attendance` | Manual CRUD, monthly calendar, range report, **multi-format import** (CSV template, ZKTeco report CSV/XLSX, raw `.dat`/`.txt` ATTLOG) |
| `Shifts` | Shift assignment requests | `hr_shifts` (+ `approve`) | One request can cover multiple dates, each with its own shift type |
| `Overtime` | Overtime requests | `hr_overtime` | Batched multi-date; self-service requests are restricted to the **current calendar month** |
| `Payroll` | Payslips | `hr_payroll` | Bulk `generate()` over selected employees/month, skips already-generated |
| `Payroll_items` | Allowance/deduction components | `hr_payroll` | Fixed or percentage; taxable flag |
| `Loans` | Employee loans | `hr_loans` | See [§7](#7-loan-capacity-model) for the capacity calculation; deduction-request sub-workflow (skip/adjust a month's installment) |
| `Performance` | Targets & sub-targets | `hr_performance` | `view()` also opens to any staff assigned as an **evaluator**, regardless of ownership |
| `Training` | Training programs | `hr_training` | `view()`/attendance-marking also open to the assigned **instructor** and enrolled participants |
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

---

## 4. Database schema

### 4.1 Tables

40 tables are created in `install.php`; 2 more (`hr_email_templates`, `hr_whatsapp_templates`) are created lazily by their models on first use. All are dropped by `uninstall.php`, but only if `allow_data_removal_on_uninstall` was explicitly turned on in Settings — **uninstall preserves data by default**.

| Table | Purpose |
|---|---|
| `hr_departments` | Department tree (parent_id, head_staff_id) |
| `hr_designations` | Job titles |
| `hr_employees` | Core employee master — linked to `tblstaff.staffid`, personal/bank/statutory data, salary, `max_loan_amount` override |
| `hr_leave_types` | Leave type configuration |
| `hr_leave_requests` / `hr_leave_request_days` | Leave request header / per-day breakdown |
| `hr_leave_balances` | Per employee/type/year allocated-used-carried days |
| `hr_attendance` | Daily punch record (in/out, hours, status, source, device) |
| `hr_zkteco_devices` / `hr_zkteco_mapping` / `hr_zkteco_sync_logs` | Biometric device registry, user-id↔employee mapping, sync audit |
| `hr_payroll_items` | Reusable earning/deduction components |
| `hr_payroll` / `hr_payroll_details` | Payslip header / line items |
| `hr_loans` / `hr_loan_repayments` / `hr_loan_deduction_requests` | Loan record, repayments, monthly skip/adjust requests |
| `hr_overtime` | Overtime request (batch_id groups a multi-date submission) |
| `hr_performance_reviews` / `hr_performance_tasks` (+ evaluators/feedback) | Legacy review/task model (superseded) |
| `hr_performance_targets` / `hr_performance_sub_targets` (+ evaluators/feedback) | Current performance model |
| `hr_training` / `hr_training_participants` / `hr_training_attendance` / `hr_training_sessions` | Training programs, enrolment, per-session attendance |
| `hr_helpdesk` / `hr_helpdesk_replies` | Tickets and threaded replies |
| `hr_contracts` | Employment contracts |
| `hr_audit_trail` | Generic audit log (module, action, record id, old/new value, actor, IP) — **no UI screen exists yet** |
| `hr_settings` | Key/value settings store (also holds `_schema_version`) |
| `hr_holidays` | Official calendar entries |
| `hr_policies` / `hr_policy_revisions` | Policy record and pending-revision queue |
| `hr_shift_types` / `hr_shift_assignments` | Named shifts, employee assignment requests |

### 4.2 Schema versioning & auto-migration

```php
define('HR_MODULE_SCHEMA_VERSION', 3);   // hr_module.php
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

---

## 5. Permission system

### 5.1 Registration

`hr_module_register_permissions()` (hooked on `admin_init`) registers 17 capability groups from three reusable sets:

- **`$cap_personal`** = `view_own`, `view`, `create`, `edit`, `delete`
- **`$cap_personal_approve`** = the above + `approve` — used by `hr_leave` and `hr_shifts` (genuinely gates their approve/reject actions)
- **`$cap_config`** = `view`, `create`, `edit`, `delete` (no `view_own`) — used by `hr_departments`, `hr_zkteco`

> `hr_payroll` and `hr_loans` also register an `approve` capability, but their controllers currently gate approve/reject on `edit`, not `approve`. Worth knowing if you're auditing role permissions — the checkbox exists but isn't wired to anything yet.

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
| Leave Balance | `SUM(allocated + carry_forward − used)` across `hr_leave_balances` for the current year |
| Pending / Approved Leaves | `hr_leave_requests` counts |
| Net Salary | Current-or-previous-month `hr_payroll` row — **masked behind an eye-toggle by default** (client-side only; the value is already the viewer's own, so this is a glance-privacy feature, not an access boundary) |
| Loan Outstanding | Latest `active`-status `hr_loans` row |
| Overtime (this month) | Approved-hours sum for the current month + any-time pending count |
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

---

## 12. Extending the module — checklist

When adding a new feature area, follow the established shape:

1. **Controller** extends `AdminController`; every action starts with a `staff_cant()`/`staff_can()` gate.
2. **Register the capability** in `hr_module_register_permissions()` using one of the three existing `$cap_*` sets unless there's a genuine reason for a new shape.
3. **Add a sidebar entry** in `hr_module_init_menu_items()` if it's a top-level feature, gated on the same capability check used by the controller's `index()`.
4. **If self-service applies**, use the `own_only` idiom verbatim (§5.2) rather than inventing a new access shape.
5. **New table/column** → guard it in `install.php`, bump `HR_MODULE_SCHEMA_VERSION`, and confirm `uninstall.php`'s table-drop list covers it if `allow_data_removal_on_uninstall` should remove it.
6. **New settings key** → add to `Settings::_save_settings()`'s `$allowed_keys` (and `$numeric_ranges` if it's a bounded number) — the `hr_settings` table itself needs no schema change, it's a generic key/value store.
7. **New notification** → add a `notify_xxx` toggle to Settings, wrap the send call in `notifications_enabled('notify_xxx')`, and add the email template to `Email_templates_model`'s seed list.
8. **Any UI element gated by permission** → double-check the visibility check matches exactly what the destination action requires (§5.3) — this is the single most common regression class in this module's history.
9. **Any per-selection dynamic UI** (a dropdown that changes a displayed number/hint) → prefer the preload-once-and-look-up-locally pattern (§9.1) over a fresh AJAX call per interaction.
10. **Live-verify, don't just lint.** Every fix in this module's history has been verified with real authenticated HTTP requests or real browser automation against disposable test data, then cleaned up — not just "the code looks right."

---

## 13. Recent feature history

For context on *why* certain things look the way they do:

- **Per-employee/default maximum loan amount** — revolving capacity cap (§7), replacing an unlimited-amount loan apply flow.
- **HR dashboard tabs** — managers who are also employees now get both their personal and managerial dashboard instead of only the managerial one (§8).
- **Net salary masking** — hidden behind an eye-toggle by default on the personal dashboard.
- **Default-UI consistency pass** — a broad sweep converting plain `<select>`/native date inputs to Perfex's own `selectpicker`/datepicker widgets across most feature pages, and normalizing icon classes to FontAwesome 6 (`fa-regular`/`fa-solid`) where the old FA4-style classes weren't rendering.
- **Role-based visibility pass** — department filters, action buttons, and report controls across most feature areas narrowed to match what the destination action actually requires (§5.3).
- **HR permission label prefixing** — every HR capability group's display label in Setup > Roles now starts with "HR " to disambiguate from core Perfex permissions of similar name.
