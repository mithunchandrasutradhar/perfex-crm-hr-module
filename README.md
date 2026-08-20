<div align="center">

# HR Management Module for Perfex CRM

**A complete, self-contained HR system running inside the CRM your team already uses.**

![Version](https://img.shields.io/badge/version-1.0.0-blue?style=flat-square) ![Perfex CRM](https://img.shields.io/badge/Perfex%20CRM-3.3.x-6366f1?style=flat-square) ![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4?style=flat-square) ![Status](https://img.shields.io/badge/status-active-brightgreen?style=flat-square) ![License](https://img.shields.io/badge/module-Alpha%20Net%20BD-333333?style=flat-square)

[Documentation](#documentation) · [Feature areas](#feature-areas) · [Recent additions](#recent-additions) · [Installation](#installation) · [Support](#support)

</div>

---

Leave, attendance, payroll, loans, overtime, performance, training, contracts, policies, shifts, helpdesk, and biometric device sync — all built as a single independent module for [Perfex CRM](https://www.perfexcrm.com/), sharing its login, its staff/role system, and its admin UI.

**Module slug:** `hr_module` &nbsp;·&nbsp; **Version:** 1.0.0 &nbsp;·&nbsp; **Requires:** Perfex CRM 3.3.x &nbsp;·&nbsp; **Author:** Alpha Net BD

<details>
<summary><strong>What's in this repo</strong></summary>
<br>

This module lives inside a full Perfex CRM codebase checkout, at `modules/hr_module/` (this file's own folder). The module itself **never modifies any core Perfex file** — it's built entirely as an independent, installable extension using Perfex's own module hooks, permission system, and admin UI components.

</details>

## Documentation

| Doc | For | Covers |
|---|---|---|
| [`USER_GUIDE.md`](USER_GUIDE.md) | Employees, department heads, HR managers | How to use every feature, organized by what you're trying to do, role permissions in plain terms, and a FAQ |
| [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md) | Whoever operates the module | First-time setup checklist, role/permission recipes, WhatsApp & ZKTeco integration setup, cron configuration, and a troubleshooting quick-reference |
| [`DEVELOPER.md`](DEVELOPER.md) | Developers maintaining or extending the module | Architecture, database schema, the permission model, settings reference, the loan-capacity calculation, integrations, and a checklist for adding new features safely |

## Feature areas

<table>
<tr><td width="50%" valign="top">

**Time & presence**
- Attendance (ZKTeco biometric sync)
- Shifts
- Overtime
- Official Calendar

**Compensation**
- Payroll & Payroll Items
- Loans (revolving capacity limit)

</td><td width="50%" valign="top">

**Growth & conduct**
- Performance targets
- Training
- Contracts
- Policies

**Records & administration**
- Employee directory
- Leave
- Helpdesk
- Reports & module Settings

</td></tr>
</table>

Every screen adapts to what the viewer is permitted to see: an employee manages their own records everywhere the module appears; a department head sees their own department's data; an HR manager or admin additionally sees company-wide data and approval controls — all layered entirely on Perfex's existing staff role/permission system.

## Recent additions

- **Department-scoped visibility** — a new "View (Own Department)" permission (Leave, Overtime, Shifts, Performance, Training) lets a department head see their own department's records without full company-wide access.
- **Soft approval** — an informational pre-approval step on Leave, Overtime, and Shifts: a department head's decision is recorded and shown, but never blocks or replaces the real approval.
- **Gender-restricted leave types** — Maternity/Paternity Leave (or any custom type) can be locked to one gender, enforced server-side and reflected instantly in the apply form.
- **Full activity logging** — every create/update/approve/reject/delete across the module now writes to Perfex's core Activity Log.
- **Automatic leave-balance allocation** — a new employee's leave balances are set up the moment their profile is created, instead of waiting for the next manual allocation run.
- **Leave Balances page** rebuilt as a standard sortable/filterable table, matching every other list in the module.

<details>
<summary>Full technical changelog</summary>
<br>

See the **"Recent feature history"** section at the end of [`DEVELOPER.md`](DEVELOPER.md#13-recent-feature-history) for every change with implementation detail and cross-references into the architecture docs.

</details>

## Installation

1. Place the `hr_module` folder inside your Perfex installation's `modules/` directory.
2. Go to **Setup > Modules** in the CRM admin panel and activate **HR Management**.
3. Grant the relevant "HR ..." permissions to staff roles under **Setup > Staff > Roles** — see [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md#2-roles--permissions-setup) for role recipes.
4. Visit **HR Management > Settings** to configure defaults (working hours, overtime rates, the default maximum loan amount, notification email, and — optionally — WhatsApp broadcast credentials).
5. Follow the full **first-time setup checklist** in [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md#1-first-time-setup-checklist) for everything else (leave types, holidays, first balance allocation, cron).

> The module's schema is self-migrating: reactivating after an update to an already-installed site automatically brings the database up to date on the next admin page load — no manual SQL or deactivate/reactivate cycle required.

## Support

For anything not covered by the docs above:
- **Using a feature?** → [`USER_GUIDE.md`](USER_GUIDE.md)
- **Setting up or troubleshooting?** → [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md#11-troubleshooting-quick-reference)
- **Extending the code?** → the checklist in [`DEVELOPER.md`](DEVELOPER.md#12-extending-the-module--checklist)
- Otherwise, reach out to the Alpha Net HR module team.
