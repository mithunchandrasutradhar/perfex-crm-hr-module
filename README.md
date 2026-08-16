# HR Management Module for Perfex CRM

A complete, self-contained HR module for [Perfex CRM](https://www.perfexcrm.com/) — leave, attendance, payroll, loans, overtime, performance, training, contracts, policies, shifts, helpdesk, and biometric device sync, all running inside the CRM your team already uses.

**Module slug:** `hr_module` · **Version:** 1.0.0 · **Requires:** Perfex CRM 3.3.x · **Author:** Alpha Net BD

---

## What's in this repo

This repository is the full Perfex CRM codebase with the HR module developed at [`modules/hr_module/`](modules/hr_module/). The module itself never modifies any core Perfex file — it's built entirely as an independent, installable extension using Perfex's own module hooks, permission system, and admin UI components.

## Documentation

| Doc | For | Covers |
|---|---|---|
| [`modules/hr_module/USER_GUIDE.md`](modules/hr_module/USER_GUIDE.md) | Employees, HR managers, admins | How to use every feature, organized by what you're trying to do, with a plain-language FAQ |
| [`modules/hr_module/DEVELOPER.md`](modules/hr_module/DEVELOPER.md) | Developers maintaining or extending the module | Architecture, database schema, the permission model, settings reference, the loan-capacity calculation, integrations, and a checklist for adding new features safely |

## Feature areas

- **Time & presence** — Attendance (with ZKTeco biometric device sync), Shifts, Overtime, Official Calendar
- **Compensation** — Payroll, Payroll Items, Loans (with a revolving capacity limit)
- **Growth & conduct** — Performance targets, Training, Contracts, Policies
- **Records & administration** — Employee directory, Leave, Helpdesk, Reports, module Settings

Every screen adapts to what the viewer is permitted to see — an employee manages their own records everywhere the module appears; an HR manager or admin additionally sees company-wide data and approval controls, layered entirely on Perfex's existing staff role/permission system.

## Installation

Standard Perfex CRM module activation:

1. Place the `hr_module` folder inside your Perfex installation's `modules/` directory.
2. Go to **Setup > Modules** in the CRM admin panel and activate **HR Management**.
3. Grant the relevant "HR ..." permissions to staff roles under **Setup > Staff > Roles**.
4. Visit **HR Management > Settings** to configure defaults (working hours, overtime rates, the default maximum loan amount, notification email, and — optionally — WhatsApp broadcast credentials).

The module's schema is self-migrating: reactivating after an update to an already-installed site automatically brings the database up to date on the next admin page load — no manual SQL or deactivate/reactivate cycle required.

## Support

For anything not covered by the docs above, see the "Extending the module" checklist in `DEVELOPER.md`, or reach out to the Alpha Net HR module team.
