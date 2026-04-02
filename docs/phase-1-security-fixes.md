# Phase 1 (P0) Security and Consistency Fixes

Scope: only the approved P0 items.

Team note: this is the "what changed and why" file before feature work starts.

## Implemented fixes

1. Open redirect hardening
- File: `public/actions/auth/login.php`
- Change:
  - Redirect target now comes from POST hidden field.
  - Redirect is allowed only for safe in-app relative paths (`/...`), not external hosts/schemes.
- Why:
  - Prevents attacker-controlled redirect destinations.

2. Login redirect propagation
- File: `views/pages/login.php`
- Change:
  - Added hidden `redirect` field populated from incoming query param.
- Why:
  - Preserves intended post-login return path in a controlled way.

3. Upload validation hardening
- Files:
  - `public/actions/events/create_event.php`
  - `public/actions/events/update_event.php`
- Change:
  - Added max size check (2MB).
  - Added `is_uploaded_file()` check.
  - Added MIME validation via `finfo` (jpeg/png/gif only).
  - Upload directory creation check.
  - Randomized file names via `random_bytes`.
- Why:
  - Reduces unsafe file upload risks and inconsistent behavior.

4. Comment reply authorization consistency
- Files:
  - `public/actions/events/comment.php`
  - `models/CommentModel.php`
- Change:
  - Added `comment_get_by_id()`.
  - Reply posting is now restricted to event organizer/admin.
  - Parent comment must exist and belong to same event.
  - Added event existence + unverified event commenting guard.
- Why:
  - Enforces policy expected by role model and avoids cross-event reply misuse.

5. Event visibility policy in detail page
- File: `views/pages/event_detail.php`
- Change:
  - Unverified event detail can be viewed only by its organizer or admin.
  - Other users receive a clear "pending approval" notice.
- Why:
  - Aligns page behavior with moderation workflow.

## Quick manual test checklist

- Login with `redirect=/some/local/path` and confirm redirect works.
- Try redirect payload like `https://evil.test` and confirm it is ignored.
- Upload invalid file type or oversize image; confirm graceful error.
- Try reply as attendee; confirm blocked.
- Open unverified event as non-owner/non-admin; confirm blocked.

## Deferred (not part of Phase 1)

- `.env`/secrets refactor
- Automated test suite
- CI pipeline
