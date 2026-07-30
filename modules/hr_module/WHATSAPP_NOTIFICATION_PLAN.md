# WhatsApp Notification Plan (via WAHA)

Status: **Planned, not implemented.** No code has been written yet. This document
is the reference to pick the work back up from.

## Goal
Add an optional WhatsApp channel (via the existing WAHA instance at
`https://waha.abutalha.com.bd`) alongside the current email + central-notification
system, sending to a configured group and/or person, fully gated by a
Settings-page enable switch.

## 1. Key facts from the WAHA class/docs that shape the design
- `sendText($phone, $text)` takes a **phone number** and runs it through
  `formatJid()`, which only handles individual contacts (`8801xxxxxxxxx@c.us`).
  It does **not** handle WhatsApp **group** JIDs (format
  `xxxxxxxxxxxxx-xxxxxxxxxx@g.us` or similar), so a group target must be
  entered as a raw JID and passed straight through — not reformatted.
- Auth is via an optional `X-Api-Key` header; base URL/session are per-server
  config, matching the module's existing "one small settings row per key"
  pattern (`hr_settings` table).
- The class throws nothing — it always returns `['status' => bool, ...]`,
  which fits the module's existing "never let a notification failure break
  the real workflow" rule (same rule `send_notification_email()` already
  follows).

## 2. New settings (Settings page, new "WhatsApp Notifications" panel)
Added to the same `hr-settings-form` / `hr_settings` key-value store used by
every other setting, next to the existing ZKTeco panel:

| Key | Type | Notes |
|---|---|---|
| `whatsapp_enabled` | checkbox | Master on/off switch — nothing sends if unchecked |
| `whatsapp_base_url` | text | Defaults to `https://waha.abutalha.com.bd` |
| `whatsapp_session` | text | Defaults to `default` |
| `whatsapp_api_key` | password-style text | Optional |
| `whatsapp_group_id` | text | Raw group JID (e.g. `1203...@g.us`), optional |
| `whatsapp_phone_number` | text | A single person's number (e.g. `01765447530`), optional |

Both `whatsapp_group_id` and `whatsapp_phone_number` are independently
optional — if both are filled, the message goes to both; if only one, just
that one. (This was my assumption for "a group or a person" — revisit if a
strict either/or selector is wanted instead.)

A **"Send Test Message"** button next to the panel (mirroring ZKTeco's "Test
Connection" convention) posts to a new AJAX endpoint that sends a canned
message to whichever target(s) are configured, so the admin can verify
credentials without waiting for a real event.

## 3. New library: `modules/hr_module/libraries/Waha_lib.php`
Adapted from the provided `WAHAService` class, following the same shape as
the existing `Zkteco_lib.php` (stateless methods, config passed in per call
rather than held in a constructor):
- `send_text($base_url, $session, $api_key, $target, $text)` — detects
  whether `$target` already ends in `@g.us`/`@c.us` (pass through as-is) or
  looks like a phone number (run through `formatJid()`), then calls
  `/api/sendText`.
- Keeps `sendImage`/`sendDocument`/`sendImageBase64` ported over for
  potential future use (e.g. attaching a payslip PDF), but only `send_text`
  is wired into notifications for this phase.

## 4. New model helper: `Hr_module_model::send_whatsapp_notification($subject, $lines)`
Parallel to the existing `send_notification_email()`:
- No-ops immediately if `whatsapp_enabled` isn't `1`, or if neither target is
  configured — never throws, same as the email senders.
- Builds a plain-text WhatsApp message (WhatsApp markdown, not HTML):
  `*{$subject}*` header, then `label: value` lines, then the record's link as
  plain text (WhatsApp auto-links URLs).
- Loops over the configured group/person targets and calls
  `Waha_lib::send_text()` for each, logging failures via `log_activity()`
  without affecting the caller.
- A new small helper `format_whatsapp_details($rows)` mirrors
  `format_notification_details($rows)` but emits `label: value\n` lines
  instead of an HTML table, so callers can reuse the same `$details` array
  they already build for email.

## 5. Where it hooks in (reusing the 15 existing notification call sites)
Every place that currently calls `send_notification_email(...)` /
`notify_by_permission(...)` gets one added line calling
`send_whatsapp_notification(...)` right alongside it, with the same
subject/details already assembled for the email — no new business logic, no
new events:
- Leave: apply, approve/reject, cancellation request
- Loans: apply, status change, deduction request
- Overtime: apply
- Helpdesk: ticket submit
- Policies: published/updated
- Shifts: applied, status change

Since each call site already builds a `$details` array for
`format_notification_details()`, adding the WhatsApp line is additive and
low-risk — it does not change the existing email/central-notification
behavior at all.

## 6. Safety/scope notes
- API key is stored in `hr_settings` like other secrets in this module (same
  pattern SMTP creds already use in core Perfex) — not exposed in any JS/
  front-end payload.
- All sends wrapped in try/catch inside the library/model layer; a WAHA
  outage must never block a leave approval, loan decision, etc.
- No schema changes beyond new rows in the existing `hr_settings` table (same
  lazy-insert pattern already used everywhere else).
- Nothing about existing email or central-notification behavior changes —
  this is purely additive.

## Open items to confirm before implementing
1. Group **and** person simultaneously (as planned above), or should it
   strictly be one-or-the-other?
2. Should the master toggle apply to *all* 15 events uniformly (as planned),
   or per-event checkboxes like the existing (currently unused)
   `notify_leave_apply` etc. settings?
3. Confirm the WAHA base URL/session/API key to default in Settings — plan
   defaults to the values in the provided README but leaves them editable.

## Source material
- `waha_whatsapp_sender.php` — the provided `WAHAService` helper class
  (sendText/sendImage/sendImageBase64/sendDocument via cURL to WAHA's HTTP
  API).
- `WAHA_README.md` — server config (base URL `https://waha.abutalha.com.bd`,
  session `default`), phone→JID formatting rule, image-sending methods,
  webhook receiver example.
