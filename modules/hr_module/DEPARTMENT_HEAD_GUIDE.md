# HR Module — Department Head Guide

This is the quick-start guide for a **department head** — your role has been granted **"View (Own Department)"** (and usually **"Soft Approve/Reject"**) on Leave, Overtime, and/or Shifts, and possibly Performance/Training too. That means, on top of everything a regular employee can do for themselves, you can also **see and pre-review requests from every employee in your own department** — nothing outside it, and nothing that overrides HR's actual decision.

- **Everything for your own records** (applying for your own leave, checking your own payslip, etc.) works exactly as described in [`EMPLOYEE_GUIDE.md`](EMPLOYEE_GUIDE.md) — start there for that part.
- **Not sure if you actually have this role?** Check with your admin, or see [ADMIN_GUIDE.md §2](ADMIN_GUIDE.md#2-roles--permissions-setup) for exactly which checkboxes make someone a department head.

---

## What "department head" actually means here

There's no separate "assign a department head" screen in this module — it's just two permission checkboxes (**View (Own Department)** + **Soft Approve/Reject**) applied to your role. In practice:

- You see every **Leave / Overtime / Shift** request from employees in **your own department** — matched via each employee's Department field, nothing more.
- You do **not** automatically see other departments, and you do **not** get the real Approve/Reject buttons unless you're separately granted those too (most department heads aren't — see below).
- If your role also has **View (Own Department)** on **Performance** or **Training**, the same department-scoped visibility applies there too.

## Reviewing your department's Leave / Overtime / Shift requests

**Steps:**
1. Go to **HR Management > Leave** (or **Overtime** / **Shifts**) — the list already narrows to your own department automatically; no filter to set.
2. Open a pending request.
3. Click **Soft Approve** or **Soft Reject** and add a note if you'd like.

This is an **informational pre-review only** — your name and decision show right on the request (e.g. "Soft Approved by [You]"), but the **real** Approve/Reject decision is still made separately by whoever holds that capability (typically HR). Your soft decision never blocks or changes the outcome either way.

→ Full detail on applying/approving each of these: [USER_GUIDE.md §3 (Leave)](USER_GUIDE.md#3-leave), [§6 (Overtime)](USER_GUIDE.md#6-overtime), [§7 (Shifts)](USER_GUIDE.md#7-shifts).

## Viewing your department's Performance / Training (if granted)

If your role also has **View (Own Department)** on Performance and/or Training:

**Steps:**
1. Go to **HR Management > Performance** (or **Training**) — targets/trainings for employees in your department are visible here, in addition to your own.
2. Open one to see its detail — you can act on it (add evaluator feedback, mark attendance, etc.) only if you're also specifically assigned to it (as an evaluator, or instructor).

→ Full detail: [USER_GUIDE.md §9 (Performance)](USER_GUIDE.md#9-performance), [§10 (Training)](USER_GUIDE.md#10-training).

## What you can't do

- You can't approve or reject anything for real (unless separately granted **Edit**/**Approve** on that feature — check with your admin if a button you expect isn't there).
- You can't see or act on another department's requests.
- You can't change module Settings, integrations, or other staff's roles — that's [`ADMIN_GUIDE.md`](ADMIN_GUIDE.md) territory.

---

## Frequently asked

See [USER_GUIDE.md §18](USER_GUIDE.md#18-frequently-asked-questions) for the general FAQ. Two department-head-specific ones:

**Why can't I soft-approve a request I can see?**
Soft Approve/Reject is only actionable while the request is still **pending** — once HR has made the real decision, the soft-review buttons no longer apply.

**An employee says they're in my department but I don't see their request — why?**
Department matching is based on the employee's **Department** field on their HR profile, not their team/manager in conversation. Ask HR to confirm that field is set correctly for that employee.
