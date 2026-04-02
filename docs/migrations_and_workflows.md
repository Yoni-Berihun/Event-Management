# Migration & New Workflows

## Running the Migration

Execute the latest migration file against your MySQL database:

```bash
mysql -u root city_events < sql/migrations/20260331_03_rsvp_approval_events_enddate_feedback.sql
```

This migration adds:
- `rsvps.status` (`pending|approved|rejected`), `approved_at`, `approved_by` columns
- `events.event_end` column for proper time spans
- `feedback` table with unique `(event_id, user_id)` constraint
- `rate_limit_hits` table for IP+user sliding-window rate limiting
- Indexes for conflict detection and RSVP status queries

The migration is idempotent (uses `IF NOT EXISTS`/`IF NOT EXISTS` guards).

## New Configuration Keys (`config/config.php`)

| Constant | Default | Purpose |
|---|---|---|
| `MIN_EVENT_DURATION_MINUTES` | 30 | Minimum event length (start to end) |
| `ORGANIZER_BUFFER_MINUTES` | 15 | Minimum gap between same-organiser events |
| `RATE_LIMIT_MAX_LOGIN` | 10 | Max login attempts per 5-minute window |
| `RATE_LIMIT_MAX_ACTION` | 20 | Max RSVP/feedback attempts per 5-minute window |
| `RATE_LIMIT_WINDOW_SECONDS` | 300 | Sliding window duration |

DB credentials now also read from environment variables (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) with in-code defaults.

## New Workflows

### RSVP Approval Workflow
1. Attendee clicks **RSVP Now** on event detail page
2. System checks for time conflicts with user's existing RSVPs
3. If conflicts found: shows modal listing overlapping events with **Cancel** / **Continue Anyway**
4. RSVP is created with `status = 'pending'`
5. Organiser sees pending RSVPs in their Dashboard → clicks **Approve** or **Reject**
6. Admin can also approve/reject from the Admin Panel
7. Approval uses a DB transaction with `SELECT ... FOR UPDATE` to prevent overbooking

### Time-Conflict Detection
- Triggered on RSVP attempt for any event with `event_end` set
- Compares [event_date, event_end] against user's pending/approved RSVPs
- Shows conflict warning modal; user must explicitly confirm

### Post-Event Feedback
1. After event ends, confirmed attendees see a star-rating form (1-5) + optional comment
2. Feedback can only be submitted once per user per event (immutable)
3. Organisers see avg rating + count per event on their Dashboard
4. All attendees see feedback list on the event detail page

## New Files

| Path | Purpose |
|---|---|
| `models/FeedbackModel.php` | Feedback CRUD + summaries |
| `includes/rate_limit.php` | IP+user sliding window limiter |
| `public/actions/events/approve_rsvp.php` | Approve pending RSVP |
| `public/actions/events/reject_rsvp.php` | Reject pending RSVP |
| `public/actions/events/submit_feedback.php` | Post-event feedback |
| `sql/migrations/20260331_03_...` | DB migration |
