# Deviations and Additions Beyond Course Notes

This file explains what was added beyond the basic PDF examples and why.

Team note: use this to justify practical engineering choices during review.

## 1) Open redirect allowlist check

- Added in: `public/actions/auth/login.php`
- Why this is beyond basic notes:
  - The course material covers form handling and sessions but does not detail open-redirect protection patterns.
- Practical value:
  - Prevents login redirect abuse to external domains.

## 2) Stronger upload validation (MIME + size + uploaded-file checks)

- Added in:
  - `public/actions/events/create_event.php`
  - `public/actions/events/update_event.php`
- Why this is beyond basic notes:
  - PDF introduces upload mechanics, but production-safe validation details are minimal.
- Practical value:
  - Reduces risk of unsafe or malformed uploads.

## 3) Explicit event visibility policy for unverified events

- Added in: `views/pages/event_detail.php`
- Why this is beyond basic notes:
  - Course material focuses on session/cookies and CRUD fundamentals, not moderation visibility policy.
- Practical value:
  - Aligns user access with approval workflow.

## 4) Reply authorization policy for comments

- Added in:
  - `public/actions/events/comment.php`
  - `models/CommentModel.php` (`comment_get_by_id`)
- Why this is beyond basic notes:
  - Basic comment insertion is covered by CRUD concepts; role-based reply policy is application-specific.
- Practical value:
  - Keeps organizer/admin reply flow consistent and explainable.
