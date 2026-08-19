# HR Management Module for Perfex CRM

![Version](https://img.shields.io/badge/version-1.0.0-blue) ![Perfex CRM](https://img.shields.io/badge/Perfex%20CRM-3.3.x-6366f1) ![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4) ![Status](https://img.shields.io/badge/status-active-brightgreen)

A complete, self-contained HR module for [Perfex CRM](https://www.perfexcrm.com/) — leave, attendance, payroll, loans, overtime, performance, training, contracts, policies, shifts, helpdesk, and biometric device sync, all running inside the CRM your team already uses.

**Module slug:** `hr_module` · **Version:** 1.0.0 · **Requires:** Perfex CRM 3.3.x · **Author:** Alpha Net BD

---

## What's in this repo

This repository is the full Perfex CRM codebase with the HR module developed at [`modules/hr_module/`](modules/hr_module/). The module itself never modifies any core Perfex file — it's built entirely as an independent, installable extension using Perfex's own module hooks, permission system, and admin UI components.

## Documentation

| Doc | For | Covers |
|---|---|---|
| [`modules/hr_module/USER_GUIDE.md`](modules/hr_module/USER_GUIDE.md) | Employees, HR managers/department heads, admins | How to use every feature, organized by what you're trying to do, role permissions explained in plain terms, and a FAQ |
| [`modules/hr_module/DEVELOPER.md`](modules/hr_module/DEVELOPER.md) | Developers maintaining or extending the module | Architecture, database schema, the permission model, settings reference, the loan-capacity calculation, integrations, and a checklist for adding new features safely |

## Feature areas

- **Time & presence** — Attendance (with ZKTeco biometric device sync), Shifts, Overtime, Official Calendar
- **Compensation** — Payroll, Payroll Items, Loans (with a revolving capacity limit)
- **Growth & conduct** — Performance targets, Training, Contracts, Policies
- **Records & administration** — Employee directory, Leave, Helpdesk, Reports, module Settings

Every screen adapts to what the viewer is permitted to see — an employee manages their own records everywhere the module appears; a department head sees their own department's data; an HR manager or admin additionally sees company-wide data and approval controls — all layered entirely on Perfex's existing staff role/permission system.

## Recent additions

- **Department-scoped visibility** — a new "View (Own Department)" permission (Leave, Overtime, Shifts, Performance, Training) lets a department head see their own department's records without full company-wide access.
- **Soft approval** — an informational pre-approval step on Leave, Overtime, and Shifts: a department head's decision is recorded and shown, but never blocks or replaces the real approval.
- **Gender-restricted leave types** — Maternity/Paternity Leave (or any custom type) can be locked to one gender, enforced server-side and reflected instantly in the apply form.
- **Full activity logging** — every create/update/approve/reject/delete across the module now writes to Perfex's core Activity Log.
- **Automatic leave-balance allocation** — a new employee's leave balances are set up the moment their profile is created, instead of waiting for the next manual allocation run.
- **Leave Balances page** rebuilt as a standard sortable/filterable table, matching every other list in the module.

See `DEVELOPER.md`'s "Recent feature history" section for the full technical changelog.

## Installation

Standard Perfex CRM module activation:

1. Place the `hr_module` folder inside your Perfex installation's `modules/` directory.
2. Go to **Setup > Modules** in the CRM admin panel and activate **HR Management**.
3. Grant the relevant "HR ..." permissions to staff roles under **Setup > Staff > Roles**.
4. Visit **HR Management > Settings** to configure defaults (working hours, overtime rates, the default maximum loan amount, notification email, and — optionally — WhatsApp broadcast credentials).

The module's schema is self-migrating: reactivating after an update to an already-installed site automatically brings the database up to date on the next admin page load — no manual SQL or deactivate/reactivate cycle required.

## Support

For anything not covered by the docs above, see the "Extending the module" checklist in `DEVELOPER.md`, or reach out to the Alpha Net HR module team.
