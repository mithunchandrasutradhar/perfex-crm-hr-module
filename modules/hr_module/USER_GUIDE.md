# HR Module — User Guide

This guide explains how to use the HR Management module inside the CRM — every feature is written as a numbered, click-by-click **Steps** walkthrough, so you can follow along on your own screen without needing anyone to show you first. It's written for three kinds of readers:

- **Every employee** — you can apply for leave, request loans/overtime, check your payslips, and more, all for yourself.
- **HR managers / department heads** — everything an employee can do, plus reviewing and approving requests, running payroll, and managing company-wide records.
- **Admins** — everything above, plus module settings, notification setup, and integrations.

You'll only see the menu items and buttons your account has permission for — if something mentioned here doesn't appear on your screen, you likely don't have that permission, and your admin or HR manager can grant it if you need it. Any step marked **(HR/managers)** or **(HR/admins)** is for reviewers/administrators, not a regular employee — skip it if it doesn't apply to your role.

**Just want a shorter, role-specific starting point instead of this full reference?**
- Plain employee (self-service only) → [`EMPLOYEE_GUIDE.md`](EMPLOYEE_GUIDE.md)
- Department head ("View (Own Department)" + "Soft Approve/Reject") → [`DEPARTMENT_HEAD_GUIDE.md`](DEPARTMENT_HEAD_GUIDE.md)
- Full admin → [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md) also serves as the admin role's own guide

If you're setting up or operating the module (roles, integrations, cron, troubleshooting) rather than just using it, see [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md) instead.

---

## 1. Getting started: the HR Dashboard

**Steps:**
1. Open **HR Management > Dashboard** from the left sidebar (or just click **HR Management**, which takes you straight there).
2. If you're an employee who is also an HR manager/admin, you'll see two tabs — **My Dashboard** (opens by default) and **Company Dashboard**. Click either one to switch, any time.

**What you'll see:**

- **Regular employee** → your personal dashboard directly: today's attendance, leave balance, pending/approved leave counts, open helpdesk tickets, your net salary, any active loan, this month's overtime, your latest performance task, and any upcoming training you're enrolled in — plus a row of Quick Action buttons (Apply for Leave, My Leaves, My Attendance, My Payslips, New Ticket).
- **HR manager/admin who is also an employee** → both tabs: **My Dashboard** (your own personal stats, same as above) and **Company Dashboard** (company-wide numbers: total/active employees, departments, who's present/late/on leave today, pending leave/loan/overtime counts, plus manager-level Quick Actions — Add Employee, Apply Leave, Mark Attendance, Generate Payroll, Reports).
- **Admin/manager with no personal employee profile** (e.g. a system administrator not tracked as company staff) → just the Company Dashboard, with no tabs.

### Revealing your Net Salary

Your Net Salary figure is hidden behind `****` by default, for glance-privacy.

**Steps:**
1. On your personal dashboard, find the Net Salary widget.
2. Click the small **eye icon** next to the figure to reveal the actual amount.
3. Click it again to hide it.

This never navigates you away from the dashboard, and doesn't affect anything else — see the FAQ below for whether it affects your payslip too.

---

## 2. Your Employee Profile

**HR Management > Employees** (only visible if you have permission to view the full employee directory — otherwise your own information is what you see on the dashboard and throughout the module).

### Viewing your profile

**Steps:**
1. Go to **HR Management > Employees**.
2. Find and click your own name (or, if you don't have directory access, your profile shows on your Dashboard/throughout the module instead).
3. Switch between the three tabs to see everything on file:
   - **Work Info** — employee ID, department, designation, joining/end date, basic salary, your maximum loan amount (if HR has set a custom one for you — see [Loans](#5-loans)), linked staff account, which biometric attendance device(s) you're set up to punch on and your Device Number on them, and notes.
   - **Personal Info** — contact details, date of birth, blood group, marital status, national ID/passport, emergency contact, and address.
   - **Bank Info** — bank name, account number, branch, and TIN.

Your name, email, and phone always stay in sync with your linked staff account — they're not editable separately here.

### Adding a new employee (HR/admin only)

**Steps:**
1. Go to **HR Management > Employees** and click **Add Employee**.
2. Pick the existing Perfex staff account to link this HR profile to — the **Department** field auto-fills from whatever department that staff account already has on the CRM side (you can still change it).
3. Fill in Work Info, Personal Info, and Bank Info as needed.
4. If the employee punches on a biometric device, select the device(s) and enter a unique **Device Number**.
5. Click **Save**.

This year's leave balances are set up automatically the moment you save — no separate step needed, and the new employee shows up on the Leave Balances page right away.

### Editing an existing employee (HR/admin only)

**Steps:**
1. Go to **HR Management > Employees**, find the employee, and click **Edit**.
2. Update whichever fields need changing.
3. Click **Save**.

If you change the Device Number/device selection and the ID you entered is already used by someone else on the same device, you'll see a warning after saving and that specific device mapping won't be saved — pick a different, unique Device Number and save again.

---

## 3. Leave

**HR Management > Leave**

### Applying for leave

**Steps:**
1. Go to **HR Management > Leave** and click **Apply for Leave**.
2. **Employee** field: if you have full company-wide access to Leave, this already defaults to yourself — leave it as-is, or pick a different employee if you're applying on their behalf. If you can only apply for yourself, this field is locked to you already.
3. **Leave Type**: pick it first — this decides how the rest of the form looks. Only leave types that apply to the selected employee's gender are shown (e.g. Maternity Leave only appears for a female employee, Paternity Leave only for a male one).
4. Fill in the days, depending on the type you picked:
   - **Regular leave types** (Casual, Sick, etc.) use a **day-by-day builder**: click **Add another date** for each day you need, and for each one choose **Full Day**, **Half Day** (before/after lunch), or **Hourly**. Add as many (non-consecutive or consecutive) dates as you need in one request.
   - **Range-based leave types** (e.g. Maternity Leave) instead show a simple **From / To** date range — just pick the start and end date.
5. Watch for two live indicators as you fill the form in:
   - Your **remaining balance** for the selected leave type updates instantly once you've picked an employee and leave type.
   - If two of your days are separated only by a weekend or public holiday, or if a day you're applying for bridges with a day you *already* have an approved/pending request for (e.g. you already have Thursday approved and you're now applying for Saturday, with Friday off in between), a warning appears calling out the extra gap day that will automatically be counted too (the "sandwich rule") — your earlier request is never changed by this, only the new one gets the extra day added.
6. Add a **Reason**, and attach a supporting document if the leave type requires one.
7. Click **Submit**.

### Checking your balance and requests

**Steps:**
1. Go to **HR Management > Leave** — every request you can see is listed here with its status (Pending / Approved / Rejected / Cancelled).
2. Click any request to see its full detail, including its day-by-day breakdown.
3. **HR/managers**: go to **HR Management > Leave > Leave Balances** to see the full balance grid for every employee and leave type for a given year — filter by department/year, and click **Allocate** to bulk-allocate a new year's balances (safe to run any time; a new employee's balances are already set up automatically the moment their HR profile is created, so you don't need to wait for this to see them).

### Approving, rejecting, or soft-approving a request (HR/managers)

**Steps:**
1. Go to **HR Management > Leave** and open the pending request.
2. If your role has the **Soft Approve/Reject** permission (typically a department head), you'll see extra **Soft Approve**/**Soft Reject** buttons — click one to record your informational pre-review. This is purely a heads-up for whoever makes the real decision: your name and choice show right on the request, but it never blocks or changes the actual outcome. See [§17](#17-roles-in-plain-terms) for how this permission is assigned.
3. Click **Approve** or **Reject** (the real, binding decision) — add a note/reason if prompted.

### Cancelling a leave

**Steps:**
1. Go to **HR Management > Leave** and open your request.
2. If it's still **pending**, click **Delete** — it's removed outright.
3. If it's **already approved**, click **Request Cancellation** instead and give a reason — this doesn't cancel it immediately; it submits a cancellation request for HR to approve or reject.
4. **HR**: to act on a cancellation request, open the request and click **Approve Cancellation** or **Reject Cancellation**.

---

## 4. Attendance

**HR Management > Attendance**

### Checking your own attendance

**Steps:**
1. Your dashboard already shows today's status — "Present"/"Late"/"Absent"/"Half Day".
2. Go to **HR Management > Attendance** for your full **monthly calendar** — a color-coded grid showing every day's status, with weekends and holidays marked.
3. If your office uses a biometric device (fingerprint/face/card, either ZKTeco or AiFace/AI-series), your punches record automatically the moment you scan — the first scan of the day is always your clock-in, and whichever scan is latest so far is your clock-out, no matter how many times you punch in between (e.g. stepping out and back for lunch).
4. Click **View Log** next to any day's record to see every individual punch behind it — the time, which device it came from, and how you verified (fingerprint, face, card, etc.).

### Correcting or adding a record (HR/managers)

**Steps:**
1. Go to **HR Management > Attendance**.
2. Click **Add Record** (or open an existing day and click **Edit**).
3. Pick the employee and date, set the in/out time and status, and click **Save**.

### Running an attendance report (HR/managers)

**Steps:**
1. Go to **HR Management > Attendance** and open the **Report** tab.
2. Filter by date range, department, employee, and/or status.
3. Click **Generate** — the result is filterable/exportable like every other list in the module.

### Importing attendance in bulk (HR/managers)

**Steps:**
1. Go to **HR Management > Attendance** and click **Import**.
2. Pick the format you have: a plain CSV template (downloadable from the same page), the device's own exported report (CSV or XLSX), or a raw export file (`.dat`/`.txt`).
3. Upload the file.
4. Review the import summary — employees are matched by their **Employee Code**, so make sure that matches what's set up on the device side; any unmatched rows are reported so you can fix and re-import them.

### Managing attendance devices (HR/admins)

**Steps:**
1. Go to **HR Management > Attendance Devices** to see every registered biometric device (any brand), whether it's currently online, and its last-contact time.
2. Click **Add Device** to register a new one, or **Edit** an existing one's serial number/location.
3. See [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md) §7 for the full device setup and mapping process.

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

**Steps:**
1. Go to **HR Management > Loans** and click **Apply for Loan**.
2. **Employee** field: if you have full company-wide access to Loans, this already defaults to yourself — leave it, or pick someone else if you're applying on their behalf.
3. Once the employee is selected, read the live hint: *"You can request up to X more (Y outstanding against your Z maximum limit)."*
4. Enter your requested **Amount**.
5. Pick a monthly **Installment** from the dropdown — the repayment period (in months) is calculated automatically from whichever installment you choose.
6. Click **Submit**. If your amount exceeds your remaining capacity, you'll get a clear message telling you exactly why instead of it going through.

### Requesting a skipped or adjusted monthly installment

**Steps:**
1. Go to **HR Management > Loans** and open your active loan's detail page.
2. Click the deduction-request option.
3. Choose whether to **skip** this month's deduction or request a **different amount**.
4. Choose whether any shortfall should be added to next month's deduction, or should simply extend your repayment period by one month.
5. Submit — HR will review and approve or reject it.

---

## 6. Overtime

**HR Management > Overtime**

### Requesting overtime

**Steps:**
1. Go to **HR Management > Overtime** and click **Request Overtime**.
2. **Employee** field: if you have full company-wide access to Overtime, this already defaults to yourself — leave it, or pick someone else if you're requesting on their behalf.
3. Add one or more dates worked — you can only pick dates within the **current calendar month** (intentional: an earlier or later month's payroll may already be finalized). Each date is checked automatically for eligibility (weekend, government holiday, or company holiday) as you enter it.
4. Click **Submit**.

Your dashboard's Overtime widget shows this month's **approved days** (overtime here is tracked per day, not hourly).

### Approving or soft-approving an overtime request (HR/managers)

**Steps:**
1. Go to **HR Management > Overtime** and open the pending request.
2. If your role has the **Soft Approve/Reject** permission, click **Soft Approve**/**Soft Reject** first, same informational pre-review as [Leave](#3-leave).
3. Click **Approve** or **Reject** for the real decision (this requires **Edit** permission on Overtime — see [§17](#17-roles-in-plain-terms)).

---

## 7. Shifts

**HR Management > Shifts**

### Requesting a shift assignment

**Steps:**
1. Go to **HR Management > Shifts** and click **Request Shift**.
2. **Employee** field: if you have full company-wide access to Shifts, this already defaults to yourself — leave it, or pick someone else if you're requesting on their behalf.
3. Add one or more dates, picking a different **Shift Type** per date if needed (e.g. Night shift on one day, Morning on another).
4. Click **Submit**.
5. While it's still **pending**, you can go back and **Edit** or **Delete** your own request.

### Approving or soft-approving a shift request (HR/managers)

**Steps:**
1. Go to **HR Management > Shifts** and open the pending request.
2. If your role has the **Soft Approve/Reject** permission, click **Soft Approve**/**Soft Reject** first, same informational pre-review as [Leave](#3-leave).
3. Click **Approve** or **Reject** for the real decision.

---

## 8. Payroll

**HR Management > Payroll**

### Viewing your payslip

**Steps:**
1. Go to **HR Management > Payroll** — your own payslips are listed here.
2. Click one to see the full breakdown: basic salary, allowances, deductions, overtime, bonus, tax, loan deduction, and the final net salary.
3. Click **Print** for a printable version.

### Generating payroll (HR/admins)

**Steps:**
1. Go to **HR Management > Payroll** and click **Generate Payroll**.
2. Pick the month, year, and the employees to include.
3. Click **Generate** — the system calculates everything automatically, and any employee already generated for that period is skipped rather than duplicated.

**Payroll Items** (Settings-adjacent) lets HR define reusable allowance/deduction components: go there, click **Add**, and set whether it's a fixed amount or a percentage of basic salary, and whether it's taxable.

### Marking a payroll Paid (HR/admins)

**Steps:**
1. Go to **HR Management > Payroll**, open the payslip, and click **Mark Paid**.
2. Pick the payment method and date, and confirm.

Deductions/loan repayments/tax are recalculated one final time at this point — if that would leave a **negative net salary**, marking it paid is blocked with a clear message instead of quietly recording a negative amount, so you can go review the underlying deductions first. If an approved overtime request's amount happens to differ from what's actually paid (e.g. a pay rate changed in between), a short note recording both figures is added to the payslip automatically — the amount actually paid is unaffected either way.

---

## 9. Performance

**HR Management > Performance**

### Updating your own sub-target progress

**Steps:**
1. Go to **HR Management > Performance** — any target assigned to you is listed with its **sub-targets** (smaller, measurable pieces of the overall goal).
2. Open a sub-target and update its **Status** (Pending / In Progress / Partially Completed / Completed).
3. Add your own note describing your progress, and click **Save**.

### Adding evaluator feedback

If you've been assigned as an **evaluator** on someone else's sub-target, you can do this even without company-wide performance access.

**Steps:**
1. Go to **HR Management > Performance** and open the sub-target you're evaluating.
2. Add your feedback and a rating.
3. Click **Save**.

### Assigning a target (HR/managers)

**Steps:**
1. Go to **HR Management > Performance** and click **Assign Target**.
2. **Employee** field: if you have full company-wide access, this already defaults to yourself — pick the employee you're actually assigning to.
3. Add as many **sub-targets** as needed, and assign an **evaluator** to each if applicable.
4. Click **Save**.

---

## 10. Training

**HR Management > Training**

### Viewing your training and leaving feedback

**Steps:**
1. Upcoming and in-progress trainings you're enrolled in show on your dashboard — click one, or go to **HR Management > Training** to see all of them.
2. Open a training's page to see its schedule, venue, and (if it spans multiple sessions) each session's date/time.
3. After attending, click **Leave Feedback** and submit your comments.

### Marking attendance and closing a training (assigned instructor)

**Steps:**
1. Go to **HR Management > Training** and open the training you're the instructor for.
2. For each session, mark each participant's attendance.
3. Once the training is finished, click **Mark Complete** and add a closing note.

### Creating a training (HR/managers)

**Steps:**
1. Go to **HR Management > Training** and click **Add Training**.
2. Fill in the schedule, venue, and session dates/times, and pick an instructor.
3. Click **Save**, then **Enroll Participants** to add employees to it.
4. Once it's finished, click **Generate Report** for a printable attendance-history report, or email it directly to the HR inbox from the same button.

---

## 11. Helpdesk

**HR Management > Helpdesk**

### Submitting a ticket

**Steps:**
1. Go to **HR Management > Helpdesk** and click **New Ticket**.
2. **Employee** field: if you have full company-wide access to Helpdesk, this already defaults to yourself — leave it, or pick someone else if you're submitting on their behalf.
3. Fill in a **Subject**, and optionally a category, priority, and attachment.
4. If you'd rather not have your name attached, check **Submit anonymously** — HR still sees and responds to the ticket, just without your identity.
5. Click **Submit**.

### Replying to and closing a ticket (HR)

**Steps:**
1. Go to **HR Management > Helpdesk** and open the ticket.
2. Type your reply (or add an internal note, visible only to HR) and click **Send**.
3. Click **Close** once resolved — **Reopen** later if needed.

---

## 12. Contracts

**HR Management > HR Contracts**

### Viewing your contracts

**Steps:**
1. Go to **HR Management > HR Contracts** — your employment contract(s) are listed with type, dates, value, and signature status.
2. Click one to see the full detail.

Contracts automatically move to "Expired" once their end date passes, and HR gets a one-time email + in-app notification 30 days ahead of expiry so renewals don't get missed (requires the server's cron job to be running — see [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md)).

### Adding a contract (HR/admins)

**Steps:**
1. Go to **HR Management > HR Contracts** and click **Add Contract**.
2. **Employee** field: if you have permission to add contracts, this already defaults to yourself — pick the employee you're actually adding a contract for.
3. Fill in the contract type, dates, value, and attach the document.
4. Click **Save**.

---

## 13. Policies

**HR Management > Policies**

### Viewing policies

**Steps:**
1. Go to **HR Management > Policies** — you'll see public policies, plus any private policy targeted at your department.
2. Click one to read it in full.

### Publishing or revising a policy (HR/managers)

**Steps:**
1. Go to **HR Management > Policies** and click **Add Policy**.
2. Write the policy content, and mark it **Public** or target it to a specific department.
3. Click **Publish**.
4. To change an already-published policy, open it and click **Revise** — this doesn't change the live version immediately; it queues a revision that a configured policy approver must approve first (see [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md) §12 for who that is).

---

## 14. Official Calendar

**HR Management > Official Calendar**

### Viewing the calendar

**Steps:**
1. Go to **HR Management > Official Calendar** to see the company's holiday list, plus the configured weekly-off day(s) (e.g. Friday, or Friday+Saturday) — this is exactly what the Leave and Overtime pages check against automatically.

### Adding a holiday (HR/admins)

**Steps:**
1. Go to **HR Management > Official Calendar** and click **Add Holiday**.
2. Enter the date and name, and click **Save**.

### Setting the weekly-off day(s) (HR/admins)

**Steps:**
1. Go to **HR Management > Official Calendar** and open the weekly-off setting.
2. Check the day(s) that should count as a weekly off (e.g. Friday, or Friday+Saturday).
3. Click **Save**.

### Sending a manual holiday announcement (HR/admins)

**Steps:**
1. Go to **HR Management > Official Calendar** and click **Send Announcement** next to the holiday.
2. Confirm — this sends immediately (email and WhatsApp, if configured), instead of waiting for the automatic day-before reminder.

---

## 15. Reports

**HR Management > Reports** (HR/managers only)

**Steps:**
1. Go to **HR Management > Reports**.
2. Pick a report type: Attendance, Leave, Payroll, Loan, Overtime, Performance, Training, Headcount, Department, Salary, or Turnover.
3. Set your filters (date range, department, employee, etc.) and click **Generate**.
4. Click **Export** to download it.

---

## 16. Settings (Admin / HR only)

**HR Management > Settings**

**Steps:**
1. Go to **HR Management > Settings**.
2. Update whichever section you need: general configuration (employee ID prefix, currency, fiscal year start, payroll day, **default maximum loan amount**), attendance/working-hours defaults and overtime rates, an **Income Tax Rate** used in payroll tax calculation, shift types, notification toggles (which HR inbox receives request notifications, and which events trigger an email), WhatsApp broadcast setup (for company-wide announcements only — never individual messages), the day-before holiday reminder, and separate enable/disable toggles for the ZKTeco and AiFace device integrations.
3. Click **Save**.

A "Danger Zone" section (admin-only) controls whether uninstalling the module deletes all its data or preserves it — it's off by default, so uninstalling never destroys your HR records unless you deliberately turn this on first.

### Customizing notification wording

**Steps:**
1. From the Settings page, click **Email Templates** or **WhatsApp Templates**.
2. Open the template you want to change and edit its wording.
3. Click **Send Test** to preview it before relying on it.
4. Click **Save**.

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

### Setting up a department head

There's no separate "assign a department head" screen — it's just two ordinary permission checkboxes.

**Steps:**
1. Go to **Setup > Staff > Roles** and create or edit a role (e.g. "Department Head").
2. For **Leave**, **Overtime**, and **Shifts** (and optionally Performance/Training), check **"View (Own Department)"** so they see their department's requests.
3. For the same three, also check **"Soft Approve/Reject"** so they can record their pre-review.
4. Save the role, then assign it to whichever staff member(s) should act as department head.

They immediately see and can act on records for employees in their own department, nothing more.

### Granting any other permission

**Steps:**
1. Go to **Setup > Staff > Roles** and create or edit a role.
2. Check the specific "HR ..." capability box(es) needed, per the table above.
3. Save, and assign the role to the relevant staff member(s).

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

**Why does the Leave Apply page warn me about a day I didn't even pick?**
The "sandwich rule" also checks against your *other* existing requests, not just the days in front of you right now — if you already have an approved or pending request for one day and you apply for another day with only a weekend/holiday gap between them, that gap day counts as leave too, and this page warns you about it live, before you submit. Your earlier request is never changed by this.

**Why does the Employee field on an Apply/Add page already show my own name?**
If you have full company-wide view access to that feature (not just access to your own records), the Employee field defaults to yourself as a convenience — you can still pick a different employee before submitting if you're acting on someone else's behalf. If you only have access to your own records, this field stays locked to you either way.
