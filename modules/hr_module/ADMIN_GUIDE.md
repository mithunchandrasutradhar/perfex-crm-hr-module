# HR Module — Admin Guide

This guide is for whoever **operates** the HR module day to day — configuring it, setting up roles, keeping integrations healthy, and troubleshooting when something looks wrong. It assumes you already have full admin access.

If you're looking for "how do I apply for leave" or "how do I approve a request," see [`USER_GUIDE.md`](USER_GUIDE.md) instead — this guide is about running the module, not using its features day to day. If you're extending the module's code, see [`DEVELOPER.md`](DEVELOPER.md).

---

## 1. First-time setup checklist

1. **Activate the module** — Setup > Modules > HR Management.
2. **Configure Settings** (HR Management > Settings): employee ID prefix, currency, fiscal year start, payroll generation day, default maximum loan amount, working hours/days, office start/end time, overtime rates, shift allowance amounts.
3. **Set up roles and permissions** — see [§2](#2-roles--permissions-setup) below. Don't skip this; without it, staff will either see nothing or see everything.
4. **Add departments and designations** if they don't already exist on the CRM side (Perfex core Setup, or HR Management > Designations for job titles).
5. **Review the seeded Leave Types** (HR Management > Leave > Leave Types) — adjust day counts, carry-forward rules, and gender restrictions to match your company's actual policy (see [§4](#4-leave-types-including-gender-restriction)).
6. **Set weekly off day(s) and add public holidays** — HR Management > Official Calendar.
7. **Allocate the first year's leave balances** — HR Management > Leave > Leave Balances > **Allocate**. (Every employee added *after* this point gets their balances automatically — see [§5](#5-leave-balances)).
8. **Configure notifications** — which inbox receives request notifications, and which events trigger an email (Settings page).
9. **Optional: WhatsApp broadcast** — see [§6](#6-whatsapp-waha-integration).
10. **Optional: Biometric attendance devices (ZKTeco / AiFace)** — see [§7](#7-biometric-device-integration).
11. **Set up the server cron job** — see [§8](#8-cron-job) — several automated features (day-before holiday reminders, contract auto-expiry) silently do nothing without this.

---

## 2. Roles & permissions setup

Every HR permission lives under **Setup > Staff > Roles**, prefixed "HR " to keep it apart from unrelated CRM permissions. Permissions are granted **per feature** (Leave, Attendance, Payroll, Loans, Overtime, Performance, Training, Helpdesk, Contracts, Shifts, Policies, Reports, Settings, Employees, Departments, Attendance Devices — this last one controls the device management screen for **both** ZKTeco and AiFace devices).

### The five capability tiers (not every feature has all five)

| Capability | Meaning |
|---|---|
| **View (Own)** | See and manage your own records for that feature only |
| **View (Own Department)** | See every record for employees in your own department — a middle ground, no company-wide access needed. *Leave, Overtime, Shifts, Performance, Training only.* |
| **View** | See and manage every employee's records for that feature, company-wide |
| **Create / Edit / Delete** | Standard CRUD, independent of the view tiers above |
| **Approve/Reject** | The real, binding approval decision (Leave, Shifts — Overtime and Payroll/Loans actually gate their approve/reject on **Edit**, not this checkbox — see the note below) |
| **Soft Approve/Reject** | An informational pre-approval that never blocks the real decision. *Leave, Overtime, Shifts only.* |

> **Known quirk, not a bug:** for `hr_overtime`, `hr_payroll`, and `hr_loans`, the "Approve/Reject" checkbox is registered but the actual approve/reject buttons check **Edit** instead. If you want someone to approve overtime, payroll, or loans, grant them **Edit**, not "Approve/Reject" — that checkbox currently does nothing for those three.

### Common role recipes

| Role | Checkboxes to grant |
|---|---|
| **Plain employee** | Nothing extra needed — an HR profile alone gives self-service access to their own Leave/Attendance/Payroll/Loans/Overtime/Tickets/Contracts/Performance |
| **Department head** | Leave/Overtime/Shifts: **View (Own Department)** + **Soft Approve/Reject**. Optionally Performance/Training: **View (Own Department)** too, if they should see their team's targets/training. |
| **HR manager / recruiter** | **View** + **Create/Edit/Delete** on whichever features they manage company-wide (typically Employees, Leave, Attendance, Overtime, Shifts) |
| **Payroll processor** | **View** + **Edit** on Payroll and Loans (remember: Edit, not Approve, actually gates their approve actions) |
| **Full HR admin** | Every capability, every feature |
| **System admin (Perfex admin)** | Automatically bypasses every permission check everywhere in this module (and every other Perfex module) — this is a Perfex core behavior, not something the module can restrict. An admin does **not** need to be explicitly granted any HR checkbox to use the module fully. |

### Setting up a department head, concretely

There's no separate "assign department head" screen — it's two ordinary permission checkboxes:

1. **Setup > Staff > Roles** — create a role (e.g. "Department Head") or edit an existing one.
2. Under **HR Leave**, **HR Overtime**, and **HR Shifts**, check **View (Own Department)** and **Soft Approve/Reject**.
3. Assign that role to whichever staff member(s) should act as a department head.
4. They immediately see and can soft-review requests for employees in their own department (matched via `hr_employees.department_id`) — nothing more, nothing less. One staff member can be the "head" of only their own department this way; there's no limit on how many staff can hold the role across different departments.

---

## 3. Employee setup

**HR Management > Employees > Add** links an existing Perfex staff account to a new HR profile. Two things happen automatically:

- The **Department** field pre-fills from whatever department that staff account already has on the CRM side (if any) — you can still change it before saving.
- Their **leave balances for the current year are allocated immediately** on save — they don't need to wait for the next bulk "Allocate" run, and they'll show up on the Leave Balances page right away.

**If an employee is missing from the Leave Balances page:** this almost always means their profile was created *before* the auto-allocation feature existed, or before the module was updated. Fix: HR Management > Leave > Leave Balances > **Allocate** — safe to run any time, it only fills in missing rows and never touches existing ones.

---

## 4. Leave types (including gender restriction)

**HR Management > Leave > Leave Types.** For each type you control:

- **Maximum Days Per Year**, **Hours Per Day** (for hourly leave), **Carry Forward** (+ max carry-forward cap), **Requires Attachment**, **Half-Day allowed**, **Applied as a date range** (e.g. Maternity Leave — staff pick a From/To range instead of individual days).
- **Gender Restriction** — Any / Male / Female. Seeded defaults: Maternity Leave → Female, Paternity Leave → Male, everything else → Any. Once set, that leave type simply won't appear in the Leave Type dropdown for an employee of the wrong gender — enforced on the server too, not just hidden in the UI.

**If you change a type's Maximum Days Per Year:** every employee's **already-allocated** balance for the current year updates automatically **only if they haven't used any of that leave yet** (used days = 0). An employee who's already taken some of that leave keeps their existing allocation as-is, so their already-consumed balance math isn't silently rewritten out from under them. If you need to force-correct someone's allocation after they've already used some, that's a manual balance edit (see below).

**If a leave type's data looks wrong** (allocated days don't match what you just configured, or used days don't match actual approved requests): this can happen if balances were allocated before a type's day-count was last changed, or from historical data inconsistencies. There's no self-service "recalculate" button today — this requires a direct database correction (allocated_days should match the type's current day count; used_days should match the sum of that employee's approved requests for the year). Ask your developer/technical contact if you hit this.

---

## 5. Leave Balances

**HR Management > Leave > Leave Balances** — a filterable table (by department, year) of every employee's allocated/used/remaining days per leave type.

- **Allocate** button: fills in missing balance rows for the selected year, for every active employee × every active leave type. **Safe to run repeatedly** — it only creates rows that don't already exist; it never overwrites or resets an existing row.
- New employees get allocated automatically on creation (§3) — you generally only need to click Allocate once per year, at the start of the year, plus any time you suspect someone's missing (e.g. imported employees, or employees added before this module version).
- The **Casual Leave** balance specifically is also what shows on each employee's personal dashboard widget — if that widget shows 0 for someone, check whether they have a Casual Leave balance row for the current year here.

---

## 6. WhatsApp (WAHA) integration

**HR Management > Settings** — WhatsApp section. Used **only** for company-wide broadcast announcements (leave announcements, holiday reminders, policy publications) to a single configured WhatsApp group — never for individual/personal messages.

Fields: **Base URL** (your self-hosted WAHA instance), **Session** (the session name configured on your WAHA server — see below), **API Key**, **Group ID**, **Phone Number** (test-message fallback target). A **Send Test Message** button lets you verify credentials before relying on it.

### Troubleshooting: "Session does not exist"

This means the **Session** field here doesn't match an actual running session on your WAHA server — WAHA doesn't auto-create sessions just because a message is sent to it.

1. Log into your WAHA server's own dashboard.
2. Find (or start) the session you intend to use, and confirm its exact name and that its status is **WORKING** (not `STARTING`/`SCOPED` — those mean it hasn't finished pairing with a phone via QR code yet).
3. Copy that **exact** session name into the Session field here (it does *not* have to be literally "default" — any name is fine as long as it matches).
4. Retry **Send Test Message**.

If you don't see any working session at all, you need to start one on the WAHA server and pair it with a WhatsApp account by scanning its QR code first — this module only ever *uses* a session, it doesn't create one.

---

## 7. Biometric device integration

Both supported device brands **push** attendance data to this server — the server never connects out to a device, so there's no IP/port on the device to "test" from this end for either brand. Device management for both lives in one place: **HR Management > Attendance Devices**.

### 7.1 Adding any device

**Attendance Devices > Add Device.** Fill in:
- **Device Name** — any label you want (e.g. "Main Gate", "Second Office Entrance"). The specific hardware model (AI07F, AI03FC, F18, etc.) goes here too, since there's no separate model field — just type it into the name or notes.
- **Device Type** — `ZKTeco` or `AiFace / AI-Series` — this only controls which setup instructions the form shows you and which brand-specific label appears on the device list; it does **not** affect how the device authenticates (that's always by Serial Number).
- **Serial Number** — exactly as shown on the physical device. This is how an incoming push is matched to a device record and authorized; unregistered or inactive serial numbers are rejected outright.
- **Location** — free text, shown on the device card and in punch logs — use it to tell multiple devices apart (e.g. "Dhaka Office (Inside)" vs "Dhaka Office (Outside)").

Once saved, map the device to employees from each employee's own Edit page (Attendance Devices multi-select + a required, unique Device Number) — not from a separate mapping screen. Attendance only resolves correctly for mapped employees; an unmapped device's punches are silently accepted but discarded.

### 7.2 ZKTeco setup (on the device)

On the device's own keypad, go to **Comm. > Cloud Server Setting** and set:
- **Server Mode:** `ADMS`
- **Server Address:** this server's address (IP or domain)
- **Server Port:** this server's port
- **Enable Proxy Server:** `OFF`

This particular screen has no "Request Path" field to fill in — the path (`/iclock/cdata`) is fixed on the server side. **ZKTeco Enabled** in Settings gates the whole integration; while off, every ZKTeco device request is rejected outright.

### 7.3 AiFace/AI-series setup (on the device)

On the device's `Comm. set → Server` screen, set:
- **Server Req:** `Yes`
- **Use domainNm:** `Yes`
- **DomainNm:** the **same domain your CRM already runs on** (e.g. `demo1.crm.com.bd`) — no separate subdomain is needed or used
- **Port:** `443` (or `80`)

**One required one-time setup step for this brand only:** a small snippet must be added to the site's root `index.php` file for AiFace push to work at all — this can't be done through the CRM's admin UI or the module installer, since it has to run before the CRM's own routing does. Ask whoever deployed this module for the exact snippet (documented in `DEVELOPER.md` §10.3) if AiFace devices aren't receiving any traffic despite everything above looking correct — this is the single most common reason a fresh AiFace setup doesn't work. **AiFace Enabled** in Settings gates the whole integration, same as ZKTeco's own toggle.

### 7.4 Once it's running

Within a few seconds/minutes of a successful connection, the device list shows it **Online** with a recent "Last Contact" timestamp (offline threshold is 5 minutes of silence). If a device never shows a recent contact time despite everything being configured correctly, the fault is almost always on the device's own network side (no real IP, DNS not resolving, or a firewall at that location blocking outbound traffic) rather than anything in this CRM — see the troubleshooting table in [§11](#11-troubleshooting-quick-reference).

Punches also arrive through a second, independent path for ZKTeco specifically: the Attendance page's file import (CSV/XLSX/raw `.dat`/`.txt` export), for a device you can't point at this server directly. There's no sync-interval to configure for either brand's live push.

---

## 8. Cron job

Several features are entirely cron-dependent and will **silently never fire** without a working cron job: the day-before holiday reminder (email + WhatsApp), and automatic contract expiry + 30-day expiry warnings. Biometric device attendance (either brand) is not cron-dependent — devices push on their own schedule.

Setup > Settings > Cron Job tab shows the exact command Perfex expects:
```
wget -q -O- <your-site-url>/cron/index
```
Add that as a real, recurring cron job on your **server** (typically every 1–5 minutes) — via your hosting control panel's cron manager, `crontab -e` on the host, or your container orchestration's scheduled-task equivalent if you're running in Docker (a container itself has no cron daemon by default; add one to the host, or install `cron` inside the image and run it alongside the web server process).

**Quick check that it's actually running:** Setup > Settings > Cron Job tab has a **Run Cron Manually** link (admin-only) — use it to confirm the underlying logic works, separately from confirming the *scheduled* job is actually configured on your server.

---

## 9. Notifications

Settings page: a single **HR notification inbox** email address receives every "new request submitted" notification, plus individual per-event toggles (leave apply/approve/cancellation, loan apply/approve/deduction, overtime, helpdesk, shift, policy, training, payroll). All toggles **default to enabled** the first time you save Settings — a fresh install is never silently quiet by omission.

**Email Templates** and **WhatsApp Templates** (buttons on the Settings page) let you edit the wording of every automated message, each with its own **Send Test** option.

---

## 10. Danger Zone (uninstall behavior)

Settings page, admin-only section: a single toggle, **off by default**, controlling whether deactivating/uninstalling the module deletes all its data or preserves it. Leave it off unless you deliberately want a full data wipe on uninstall — the default is that uninstalling the module never destroys your HR records.

---

## 11. Troubleshooting quick reference

| Symptom | Likely cause | Fix |
|---|---|---|
| An employee's dashboard shows "0.0 days" for leave balance | No balance row allocated for them this year | Leave Balances > Allocate (§5) |
| Not every employee shows up on the Leave Balances page | Same as above — that page only lists employees who already have a balance row | Leave Balances > Allocate |
| A leave type's allocated days don't match what you just configured | Existing balance rows only auto-sync if the employee hasn't used any of that leave yet | See [§4](#4-leave-types-including-gender-restriction) |
| WhatsApp test message fails with "Session does not exist" | Session name in Settings doesn't match a real, working session on your WAHA server | See [§6](#6-whatsapp-waha-integration) |
| Holiday reminders / contract expiry warnings never arrive | No cron job configured on the server | See [§8](#8-cron-job) |
| A staff member sees fewer buttons/pages than expected | Missing the specific capability the destination action requires — "View" and "View (Own)"/"View (Own Department)" are independent checkboxes, not a hierarchy that auto-includes lower tiers | Setup > Staff > Roles — grant the specific capability that page/button needs |
| Overtime/Payroll/Loans approve button doesn't work for a role that has "Approve/Reject" checked | Those three features actually gate on **Edit**, not the Approve/Reject checkbox (§2) | Grant **Edit** instead |
| A department head can't soft-approve anything | Missing the **Soft Approve/Reject** capability, or the employee/request isn't actually in their department | Setup > Staff > Roles (§2); confirm the employee's Department field is set correctly (§3) |
| An AiFace device never shows a recent "Last Contact" | Most commonly: the required `index.php` snippet (§7.3) was never added, or the device itself has no working network connection at that location | Confirm the snippet is in place; separately verify the device has a real local IP (not stuck on a documentation-only placeholder address) and can actually reach the internet from where it's installed |
| An AiFace device's own screen shows a placeholder-looking IP like `192.0.2.x` | The device never successfully obtained a real network address — a basic local connectivity issue, unrelated to anything in this CRM | Check the device's Network/Ethernet screen: confirm DHCP is on, and the cable/WiFi connection is actually established, before troubleshooting anything server-side |
| A device shows Online once, then goes Offline and never reconnects on its own | The one successful contact was likely a manual test from a developer/technician, not the physical device itself | Confirm by checking whether the "Last Contact" timestamp corresponds to an actual device event (a real punch, a scheduled re-registration) rather than a one-off manual check |
