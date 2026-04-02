# Testing Checklist

Manual smoke tests to verify all features after migration.

## Prerequisites
1. Run the migration: `mysql -u root city_events < sql/migrations/20260331_03_rsvp_approval_events_enddate_feedback.sql`
2. Start PHP dev server: `php -S localhost:8000 -t public/`

## Auth
- [ ] Register as attendee — success flash, redirects to feed
- [ ] Register as organiser — success flash, Dashboard link appears
- [ ] Login with valid creds — redirects properly
- [ ] Login with bad creds — error flash, stays on login
- [ ] Logout — session cleared, redirected

## Event Create/Edit (Organiser)
- [ ] Create event with start + end datetime — pending badge appears
- [ ] **Validation**: end before start → error flash
- [ ] **Validation**: duration < 30 min → error flash
- [ ] **Validation**: start in the past → error flash
- [ ] Edit event (non-admin) → resets verification to pending, success flash
- [ ] Edit event with new image → old file deleted from `public/uploads/`
- [ ] Organiser buffer warning appears when overlapping own events

## Admin Approval
- [ ] Admin panel shows pending events with Approve button
- [ ] Approve event → badge changes to Verified, appears in public feed
- [ ] Toggle approval back → removed from public feed

## RSVP Approval Workflow
- [ ] Attendee RSVPs → status shows "pending organiser approval"
- [ ] Organiser Dashboard: pending RSVPs table shows with Approve/Reject
- [ ] Approve RSVP → attendee sees "confirmed" badge on event detail
- [ ] Reject RSVP → attendee sees "not approved" message
- [ ] **Capacity**: when event is full, approve button disappears, "Full" badge shows
- [ ] **Concurrency**: two browser tabs approve last seat simultaneously — only one succeeds

## Time-Conflict Detection
- [ ] RSVP for overlapping event → conflict modal appears
- [ ] Modal shows conflicting event name + times
- [ ] Click "Cancel" → no RSVP created
- [ ] Click "Continue Anyway" → pending RSVP created
- [ ] No conflict modal when no overlapping events

## Feedback (Post-Event)
- [ ] Before event ends: "Feedback opens after event ends" shown
- [ ] After event ends (approved attendee): star rating form visible
- [ ] Submit feedback with rating + comment → "Thank you" flash
- [ ] Try submitting again → "already submitted" error
- [ ] Non-attendee after event ends → "Only confirmed attendees" message
- [ ] Event detail shows feedback list with stars + comments
- [ ] Organiser dashboard shows avg rating per event

## Visibility / Permissions
- [ ] Pending RSVP does NOT grant access to organiser-only features
- [ ] Capacity counts only approved RSVPs (not pending)
- [ ] Event feed shows only verified events with pagination
- [ ] Seat bar on feed cards reflects approved-only count

## Responsive / Mobile (Chrome DevTools → mobile widths)
- [ ] Hamburger menu appears at ≤768px, opens/closes on tap
- [ ] Event cards stack in single column
- [ ] Dashboard forms stack fields on mobile
- [ ] Buttons become full-width on small screens
- [ ] Conflict modal is scrollable and usable on small screens
- [ ] Star rating has adequate tap targets (≥44px)
- [ ] Tables scroll horizontally when narrow

## Rate Limiting
- [ ] After 20+ rapid RSVP attempts → "Too many attempts" error
- [ ] After 20+ rapid feedback submissions → "Too many" error

## Accessibility
- [ ] Tab through navigation — focus outlines visible
- [ ] Flash messages have `role="alert"` and `aria-live`
- [ ] Star rating labels have `aria-label`
- [ ] Nav toggle has `aria-expanded` and `aria-controls`
