# HR Module — User Guide

This guide explains how to use the HR Management module inside the CRM. It's written for three kinds of readers:

- **Every employee** — you can apply for leave, request loans/overtime, check your payslips, and more, all for yourself.
- **HR managers / department heads** — everything an employee can do, plus reviewing and approving requests, running payroll, and managing company-wide records.
- **Admins** — everything above, plus module settings, notification setup, and integrations.

You'll only see the menu items and buttons your account has permission for — if something mentioned here doesn't appear on your screen, you likely don't have that permission, and your admin or HR manager can grant it if you need it.

If you're setting up or operating the module (roles, integrations, cron, troubleshooting) rather than just using it, see [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md) instead.

---

## 1. Getting started: the HR Dashboard

Open **HR Management > Dashboard** from the left sidebar (or just **HR Management**, which takes you straight there).

**If you're a regular employee**, you'll see your personal dashboard directly: today's attendance, leave balance, pending/approved leave counts, open helpdesk tickets, your net salary, any active loan, this month's overtime, your latest performance task, and any upcoming training you're enrolled in — plus a row of Quick Action buttons (Apply for Leave, My Leaves, My Attendance, My Payslips, New Ticket).

**If you're an HR manager or admin who is also an employee yourself**, you'll see two tabs at the top:

- **My Dashboard** — your own personal stats, exactly like a regular employee sees.
- **Company Dashboard** — company-wide numbers: total/active employees, departments, who's present/late/on leave today, and pending leave/loan/overtime counts, plus manager-level Quick Actions (Add Employee, Apply Leave, Mark Attendance, Generate Payroll, Reports).

"My Dashboard" opens by default — click "Company Dashboard" to switch, and back again any time.

**If you're an admin/manager with no personal employee profile** (e.g. a system administrator not tracked as company staff), you'll just see the Company Dashboard, with no tabs.

### Your Net Salary is private by default

On your personal dashboard, the Net Salary figure is hidden behind `****` by default. Click the small eye icon next to it to reveal the actual amount, and click it again to hide it. This is just a glance-privacy convenience (so the number isn't visible if someone looks at your screen) — it doesn't affect anything else, and clicking it never navigates you away from the dashboard.

---

## 2. Your Employee Profile

**HR Management > Employees** (only visible if you have permission to view the full employee directory — otherwise your own information is what you see on the dashboard and throughout the module).

Your profile has three tabs:

- **Work Info** — employee code, department, designation, joining/end date, basic salary, your maximum loan amount (if HR has set a custom one for you — see [Loans](#5-loans)), linked staff account, and notes.
- **Personal Info** — contact details, date of birth, blood group, marital status, national ID/passport, emergency contact, and address.
- **Bank Info** — bank name, account number, branch, and TIN.

HR/admins can add a new employee (linking an existing staff account to a new HR profile) or edit an existing one from this page. Your name, email, and phone always stay in sync with your linked staff account — they're not editable separately here. When adding a new employee, picking a staff member auto-fills the Department field from whatever department that staff account already has on the CRM side, and this year's leave balances are set up automatically the moment you save — no separate step needed.

---

## 3. Leave

**HR Management > Leave**

### Applying for leave

Click **Apply for Leave**. Pick the leave type first — this determines how you fill in the rest:

- **Regular leave types** (Casual, Sick, etc.) use a **day-by-day builder**: add one or more specific dates, and for each one choose Full Day, Half Day (before/after lunch), or Hourly. Click "Add another date" to cover multiple non-consecutive days in one request. As soon as you pick an employee and leave type, you'll see your **remaining balance** for that leave type update instantly.
- **Range-based leave types** (e.g. Maternity Leave) instead show a simple **From / To date range**.

Some leave types are restricted to one gender (e.g. Maternity Leave to female employees, Paternity Leave to male employees) — once an employee is selected, any leave type that doesn't apply to them simply won't appear in the Leave Type list, whether you're applying for yourself or (as HR) on someone else's behalf.

If two days you pick are separated only by a weekend or a public holiday, the system automatically counts that gap day too (the "sandwich rule") — you'll see it called out before you submit.

### Checking your balance and requests

The main Leave page lists every request with its status. **HR Management > Leave > Leave Balances** (visible to HR/managers) shows the full balance grid for every employee and leave type for a given year — a sortable, filterable table like every other list in the module — with a button to bulk-allocate a new year's balances. A new employee's balances are allocated automatically the moment their HR profile is created, so they don't need to wait for the next bulk-allocate run.

### Before HR's final decision: soft approval

If your role has been given the **Soft Approve/Reject** permission for Leave (typically a department head), you'll see extra "Soft Approve"/"Soft Reject" buttons on a pending request from someone in scope, above the real Approve/Reject buttons. This is purely a heads-up for whoever makes the real decision — your name and choice show right on the request, but it never blocks or changes what HR/the approver ultimately does. See [§17](#17-roles-in-plain-terms) for how this permission gets assigned.

### Cancelling a leave

- A **pending** request can simply be deleted.
- An **already-approved** request can't be deleted outright — instead, submit a **cancellation request**, which HR then approves or rejects.

---

## 4. Attendance

**HR Management > Attendance**

Your own attendance shows on your dashboard as "Present"/"Late"/"Absent"/"Half Day" for today, and you can view your full **monthly calendar** (a color-coded grid showing every day's status, with weekends and holidays marked) from this page.

HR/managers can additionally:
- Manually add or correct an attendance record for any employee.
- Run a **filtered range report** by date, department, employee, or status.
- **Import attendance in bulk** — the Attendance page accepts a plain CSV template (download it from the same page), a ZKTeco device's own exported report (CSV or XLSX), or a raw biometric device export file (`.dat`/`.txt`). Whichever format you use, employees are matched by their **Employee Code**, so make sure that matches what's set up on the device side.

---

## 5. Loans

**HR Management > Loans**

### Understanding your loan limit

Every employee has a **maximum loan amount** — this is the most **total** you can owe the company at once, not a per-request cap. It works like this:

1. If HR has set a **custom limit** on your employee profile, that's your ceiling.
2. Otherwise, the **site-wide default** (set in Settings by HR/admin) applies.
3. Your **remaining capacity** is that ceiling minus whatever you currently owe on **approved or active** loans. A loan that's still **pending** approval doesn't count against you yet, and a loan you've **fully repaid** stops counting the moment it's closed — so your capacity frees up automatically as you pay down existing loans.

**Example:** your limit is ৳100,000. You have one active loan with ৳40,000 still outstanding. You can request up to ৳60,000 more right now.

### Applying for a loan

Click **Apply for Loan**. As soon as you (or, if you're HR, the employee you're applying on behalf of) are selected, you'll see a live hint: *"You can request up to X more (Y outstanding against your Z maximum limit)."* Enter your amount, then pick a monthly installment from the dropdown — the repayment period (in months) is calculated automatically from whichever installment you choose. If you try to submit more than your remaining capacity, you'll get a clear message telling you exactly why.

### Repaying and adjusting a monthly installment

Once your loan is active, its normal installment is deducted automatically from payroll each month. If you need to **skip a month** or **request a different amount** for a specific month, use the deduction request option on the loan's detail page — choose whether any shortfall should be added to next month's deduction or should simply extend your repayment period by one month, and HR will review it.

---

## 6. Overtime

**HR Management > Overtime**

Click **Request Overtime** to submit one or more dates worked. You can only select dates within the **current calendar month** — this is intentional, since an earlier or later month's payroll may already be finalized by the time you'd otherwise pick a date from it. Each date is checked automatically for eligibility (weekend, government holiday, or company holiday) as you enter it.

Your dashboard's Overtime widget shows this month's **approved days** (overtime here is tracked per day, not hourly).

If your role has the **Soft Approve/Reject** permission for Overtime, the same pre-review step described under [Leave](#3-leave) is available here too.

---

## 7. Shifts

**HR Management > Shifts**

Request a shift assignment for one or more dates, picking a different shift type per date if needed (e.g. Night shift on one day, Morning on another). HR/managers review and approve or reject; you can still edit or delete your own request while it's pending.

If your role has the **Soft Approve/Reject** permission for Shifts, the same pre-review step described under [Leave](#3-leave) is available here too.

---

## 8. Payroll

**HR Management > Payroll**

Your own payslips are listed here, each showing basic salary, allowances, deductions, overtime, bonus, tax, loan deduction, and the final net salary — with a printable version available from any payslip.

HR/admins additionally **generate payroll** in bulk: pick a month, year, and the employees to include, and the system calculates everything automatically (already-generated employees for that period are skipped). **Payroll Items** (Settings-adjacent) lets HR define reusable allowance/deduction components — fixed amount or a percentage of basic salary, and whether each is taxable.

---

## 9. Performance

**HR Management > Performance**

If you've been assigned a performance target, you'll see it here with its **sub-targets** — smaller, measurable pieces of the overall goal. Update a sub-target's status (Pending / In Progress / Partially Completed / Completed) and add your own note as you make progress. If you've been assigned as an **evaluator** on someone else's sub-target, you can also add feedback and a rating on it, even if you don't otherwise have access to performance data company-wide.

If you have permission to assign targets, click **Assign Target** to create one for an employee, with as many sub-targets and evaluators as needed.

---

## 10. Training

**HR Management > Training**

Upcoming and in-progress trainings you're enrolled in show on your dashboard. Open a training's page to see its schedule, venue, and (if it spans multiple sessions) each session's date/time. If you're the assigned **instructor**, you can mark daily attendance for participants and mark the training complete with a closing note. Employees can leave feedback on a training they attended.

HR/managers create trainings, enroll participants, and can generate a printable report (or email it directly to the HR inbox) covering the full attendance history.

---

## 11. Helpdesk

**HR Management > Helpdesk**

Submit an HR-related question or issue as a ticket, with an optional category, priority, and attachment. If you'd rather not have your name attached, check **Submit anonymously** — HR still sees and responds to the ticket, just without your identity. HR can reply, add an internal note, and close/reopen tickets.

---

## 12. Contracts

**HR Management > HR Contracts**

Your employment contract(s) are listed here — type, dates, value, and signature status. Contracts automatically move to "Expired" once their end date passes, and you (or HR) get a notification 30 days ahead of expiry so renewals don't get missed.

---

## 13. Policies

**HR Management > Policies**

Company policies show here, filtered to what's relevant to you: public policies, plus any private policy targeted at your department. HR/managers can publish new policies or department-specific ones; edits to an already-published policy go through a revision-and-approval step rather than changing the live version immediately, and approval is limited to whichever staff have been configured as policy approvers.

---

## 14. Official Calendar

**HR Management > Official Calendar**

The company's holiday calendar, plus the configured weekly-off day(s) (e.g. Friday, or Friday+Saturday) — this is what the Leave and Overtime pages check against automatically. HR/admins manage the holiday list here and can trigger a manual holiday announcement (email and WhatsApp, if configured) instead of waiting for the automatic day-before reminder.

---

## 15. Reports

**HR Management > Reports** (HR/managers only)

Eleven ready-made reports: Attendance, Leave, Payroll, Loan, Overtime, Performance, Training, Headcount, Department, Salary, and Turnover — each filterable and exportable.

---

## 16. Settings (Admin / HR only)

**HR Management > Settings**

Covers general configuration (employee ID prefix, currency, fiscal year start, payroll day, **default maximum loan amount**), attendance/working-hours defaults and overtime rates, shift types, notification toggles (which HR inbox receives request notifications, and which events trigger an email), WhatsApp broadcast setup (for company-wide announcements only — never individual messages), the day-before holiday reminder, and ZKTeco device sync frequency. A "Danger Zone" section (admin-only) controls whether uninstalling the module deletes all its data or preserves it — it's off by default, so uninstalling never destroys your HR records unless you deliberately turn this on first.

**Email Templates** and **WhatsApp Templates** (reached via buttons on the Settings page) let you customize the wording of every automated notification, with a "send test" option to preview one before relying on it.

---

## 17. Roles in plain terms

| If you have... | You can... |
|---|---|
| Nothing (no HR profile, no permissions) | Not access the HR module |
| An HR profile only | See and manage your own leave, attendance, payroll, loans, overtime, tickets, contracts, and performance — nothing company-wide |
| "View (Own Department)" on a feature (Leave, Overtime, Shifts, Performance, or Training) | See and manage that feature for every employee **in your own department** — a middle ground between your own data only and the whole company |
| A specific "view" permission on a feature (e.g. Leave) | See and manage that feature for *every* employee, in addition to your own data everywhere else |
| "Soft Approve/Reject" on Leave, Overtime, or Shifts | Record an informational pre-approval on a pending request (your name and decision show on it) before HR's real decision — this never blocks or replaces the actual Approve/Reject |
| Full HR access | Manage everything across every feature |
| Admin | Everything above, plus Settings, notification setup, and the Danger Zone |

Permissions are granted per feature (Leave, Attendance, Payroll, Loans, Overtime, Performance, Training, Helpdesk, Contracts, Shifts, Policies, Reports, Settings, etc.) under **Setup > Staff > Roles** — every HR-related permission there is prefixed "HR " to keep it easy to find and tell apart from unrelated CRM permissions.

**Setting up a department head:** there's no separate "assign a department head" screen — it's just two ordinary permission checkboxes. Create or edit a role under **Setup > Staff > Roles**, and for Leave/Overtime/Shifts (and optionally Performance/Training) check **"View (Own Department)"** so they see their department's requests, and **"Soft Approve/Reject"** (Leave/Overtime/Shifts only) so they can record their pre-review. Assign that role to whichever staff member(s) should act as department head — they immediately see and can act on records for employees in their own department, nothing more.

---

## 18. Frequently asked questions

**Why can't I see the department filter on a list page?**
It only appears if you can see records beyond your own — if you only have "view your own" access, the filter is hidden since it wouldn't do anything for you.

**I have full access to a feature, but I don't see a self-service shortcut for it — why?**
Some quick-action buttons check for a specific "view your own" permission separately from the general "view" one. If you run into this, let your admin know — the fix is to make sure "full view" access always covers "your own" too, which should already be the case on the dashboard's Quick Actions.

**Why did my loan request get rejected with a message about "remaining capacity"?**
Your maximum loan amount is a total ceiling, not a per-request limit — see [Loans](#5-loans) above. The message tells you exactly how much of your limit is currently used and how much you actually have left to request.

**Does hiding my Net Salary on the dashboard hide it anywhere else, like on my payslip?**
No — the eye-toggle only affects that one dashboard widget, purely for a quick glance. Your payslip page always shows the full breakdown.

**Why don't I see Maternity Leave (or Paternity Leave) in the Leave Type list?**
Some leave types are restricted by gender — Maternity Leave to female employees, Paternity Leave to male employees, by default. Once you (or the employee you're applying on behalf of) are selected, only the leave types that actually apply to them are shown.

**What does "Soft Approved by [Name]" mean on my Leave/Overtime/Shift request?**
It means someone with the Soft Approve/Reject permission (typically your department head) has recorded an informational pre-review. It's just a note for whoever makes the real decision — it doesn't change your request's status, and HR/the approver still has to Approve or Reject it themselves.
