# Course Concept Alignment (Best Effort)

This document maps key concepts from `Advanced Internet Programming - Full Reading Material.pdf` to the current codebase.

Team note: use this during demo/viva to explain "which concept is implemented where".

## 1) Server-side scripting and routing

- Concept: PHP as server-side logic and dynamic page assembly.
- In code:
  - `public/index.php` (front controller)
  - `config/routes.php` (route map)
  - `views/layout/header.php` + `views/layout/footer.php` + page views
- Status: Implemented.

## 2) Forms with GET/POST and request handling

- Concept: Handling form submission with `$_POST`/`$_GET`.
- In code:
  - Auth actions: `public/actions/auth/*.php`
  - Event actions: `public/actions/events/*.php`
  - Form pages: `views/pages/login.php`, `views/pages/register.php`, `views/pages/dashboard.php`, `views/pages/event_detail.php`
- Status: Implemented.

## 3) include/require for modular PHP

- Concept: Code reuse with `require_once`.
- In code:
  - Shared modules in `includes/` and `models/`
  - Action/view files consistently load dependencies with `require_once`
- Status: Implemented.

## 4) Sessions and authentication state

- Concept: session lifecycle and role-based access checks.
- In code:
  - `includes/session.php` (`session_start`, auth session helpers, CSRF token lifecycle)
  - `includes/auth_guard.php` (`require_login`, `require_role`, role guards)
  - Login/logout actions in `public/actions/auth/`
- Status: Implemented.

## 5) Cookies with sessions

- Concept: cookie-backed session IDs and logout cleanup.
- In code:
  - `includes/session.php` in `logout_user()` clears session cookie params + destroys session.
- Status: Implemented at basic level.

## 6) Database connectivity and CRUD

- Concept: PHP + MySQL operations.
- In code:
  - `config/db.php` (PDO connection)
  - `models/UserModel.php`, `models/EventModel.php`, `models/RsvpModel.php`, `models/CommentModel.php`
  - Schema in `sql/schema.sql`
- Status: Implemented.

## 7) Prepared statements / SQL injection mitigation

- Concept: parameterized queries.
- In code:
  - Model functions mostly use PDO prepared statements and bound parameters.
- Status: Implemented.

## 8) File upload basics

- Concept: `multipart/form-data`, `$_FILES`, file upload processing.
- In code:
  - `public/actions/events/create_event.php`
  - `public/actions/events/update_event.php`
- Status: Implemented and hardened in Phase 1 (MIME + size + upload checks).

## 9) Access control and authorization

- Concept: restricted pages/actions by role and ownership.
- In code:
  - `includes/auth_guard.php`
  - `views/pages/event_detail.php` (approval visibility policy)
  - `public/actions/events/comment.php` (reply authorization policy)
- Status: Implemented and tightened in Phase 1.

## 10) Security basics

- Concept: safe output, CSRF, secure server-side checks.
- In code:
  - Output escaping: `includes/helpers.php` (`e()`)
  - CSRF generation/verification: `includes/session.php`
  - CSRF checks in action handlers
  - Redirect hardening: `public/actions/auth/login.php`
- Status: Implemented with Phase 1 improvements.

## Concepts intentionally deferred

- Environment file (`.env`) config style: deferred to later phase.
- Automated testing + CI: deferred to later phase.
- Deployment hardening: deferred (project is local-run coursework for now).
# Course Concept Alignment (Best Effort)

This document maps key concepts from `Advanced Internet Programming - Full Reading Material.pdf` to the current codebase.

Team note: use this during demo/viva to explain "which concept is implemented where".

## 1) Server-side scripting and routing

- Concept: PHP as server-side logic and dynamic page assembly.
- In code:
  - `public/index.php` (front controller)
  - `config/routes.php` (route map)
  - `views/layout/header.php` + `views/layout/footer.php` + page views
- Status: Implemented.

## 2) Forms with GET/POST and request handling

- Concept: Handling form submission with `$_POST`/`$_GET`.
- In code:
  - Auth actions: `public/actions/auth/*.php`
  - Event actions: `public/actions/events/*.php`
  - Form pages: `views/pages/login.php`, `views/pages/register.php`, `views/pages/dashboard.php`, `views/pages/event_detail.php`
- Status: Implemented.

## 3) include/require for modular PHP

- Concept: Code reuse with `require_once`.
- In code:
  - Shared modules in `includes/` and `models/`
  - Action/view files consistently load dependencies with `require_once`
- Status: Implemented.

## 4) Sessions and authentication state

- Concept: session lifecycle and role-based access checks.
- In code:
  - `includes/session.php` (`session_start`, auth session helpers, CSRF token lifecycle)
  - `includes/auth_guard.php` (`require_login`, `require_role`, role guards)
  - Login/logout actions in `public/actions/auth/`
- Status: Implemented.

## 5) Cookies with sessions

- Concept: cookie-backed session IDs and logout cleanup.
- In code:
  - `includes/session.php` in `logout_user()` clears session cookie params + destroys session.
- Status: Implemented at basic level.

## 6) Database connectivity and CRUD

- Concept: PHP + MySQL operations.
- In code:
  - `config/db.php` (PDO connection)
  - `models/UserModel.php`, `models/EventModel.php`, `models/RsvpModel.php`, `models/CommentModel.php`
  - Schema in `sql/schema.sql`
- Status: Implemented.

## 7) Prepared statements / SQL injection mitigation

- Concept: parameterized queries.
- In code:
  - Model functions mostly use PDO prepared statements and bound parameters.
- Status: Implemented.

## 8) File upload basics

- Concept: `multipart/form-data`, `$_FILES`, file upload processing.
- In code:
  - `public/actions/events/create_event.php`
  - `public/actions/events/update_event.php`
- Status: Implemented and hardened in Phase 1 (MIME + size + upload checks).

## 9) Access control and authorization

- Concept: restricted pages/actions by role and ownership.
- In code:
  - `includes/auth_guard.php`
  - `views/pages/event_detail.php` (approval visibility policy)
  - `public/actions/events/comment.php` (reply authorization policy)
- Status: Implemented and tightened in Phase 1.

## 10) Security basics

- Concept: safe output, CSRF, secure server-side checks.
- In code:
  - Output escaping: `includes/helpers.php` (`e()`)
  - CSRF generation/verification: `includes/session.php`
  - CSRF checks in action handlers
  - Redirect hardening: `public/actions/auth/login.php`
- Status: Implemented with Phase 1 improvements.

## Concepts intentionally deferred

- Environment file (`.env`) config style: deferred to later phase.
- Automated testing + CI: deferred to later phase.
- Deployment hardening: deferred (project is local-run coursework for now).
