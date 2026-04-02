# 📍 Real-World Evolution: City-Wide Event Tracking Overhaul

## What changed
- **Intelligent RSVP Workflow**: Implemented a state-aware RSVP system (Pending ↔ Approved/Rejected) with strict capacity enforcement.
- **Admin Command Center**: Added a comprehensive **User Management** suite allowing admins to mutate roles and manage accounts.
- **Organiser Intelligence**: Upgraded dashboards with **Rich Attendee Data**, showing avatars, emails, and millisecond-accurate RSVP timing.
- **Smart Conflict Detection**: Integrated a session-based validation engine that detects and warns users of overlapping event schedules.
- **Production UI/UX**: Redesigned the entire frontend with **Glassmorphism**, Inter/Playfair typography, and fluid CSS micro-animations.
- **Robust Security**: Hardened the system with sliding-window **Rate Limiting**, CSRF-protected actions, and automated upload cleanup.

## Why
- **Scalability**: Transitions the app from a simple class project to a multi-role, production-ready "City-Wide" platform.
- **User Experience**: Prioritizes "Visual Excellence" and "Wowed" interactions (vibrant badges, smooth transitions, and intuitive layouts).
- **Control & Safety**: Provides organisers and admins with the tools needed to manage large-scale regional events securely.

## Scope
- [x] Feature
- [ ] Bug fix
- [x] Docs
- [x] Refactor
- [x] Security

## Manual test checklist
- [x] App loads locally (`php -S localhost:8000`)
- [x] Main changed flow works (Attendee RSVP → Organiser Approve → Admin Manage)
- [x] Error/invalid input path is handled (Rate limiting & Validation)
- [x] No unrelated behavior regression observed

## Security review (required for auth/actions/uploads)
- [x] **Input validation reviewed**: Strict types and regex patterns on all forms.
- [x] **Authorization/ownership checks reviewed**: Per-organiser event isolation verified.
- [x] **CSRF coverage reviewed**: All POST actions use verified tokens.

---

## 🚀 Key Features Implemented

### RSVP Approval Workflow
All RSVPs now start as `pending`. Organisers gate-keep their events. Capacity is calculated on the fly using `SELECT ... FOR UPDATE` to prevent over-booking.

### Admin User Management
A new power-user panel to search users, toggle roles (Attendee ↔ Organiser ↔ Admin), and perform account deletions with full DB cascading.

### Time-Conflict Validation
Real-time detection of overlapping event spans. Users get a warning modal if a new RSVP clashes with their existing confirmed schedule.

### Additional Highlights
*   **Organiser Dashboard**: Rich attendee lists with **Avatar Initials**, name, email, and exact RSVP timestamps.
*   **Visual Polish**: Glassmorphism headers, page fade-in transitions, focus glow animations, and polished badge shadows.
*   **Hawassa Rotaract Integration**: Added historical Rotaract events with high-quality AI-generated thumbnails.

## 🛠 Technical Deep-Dive
*   **Backend**: `UserModel`, `RsvpModel`, and `EventModel` rewritten for ACID compliance and status-aware logic.
*   **Asset Logic**: Automatic cleanup—deletes old image files from `public/uploads` when an organiser replaces an event image.
*   **Configuration**: Full environment variable support (`getenv()`) with safe local fallbacks for DB credentials.

## 🔍 How to Verify
1.  **Migration**: `mysql -u root city_events < sql/migrations/20260331_03_rsvp_approval_events_enddate_feedback.sql`
2.  **Fix Seed**: `php fix_seed.php` (Resets all passwords to `password` and fixes event dates).
3.  **Local Server**: `php -S localhost:8000 -t public/`
4.  **Test Roles**:
    *   **Admin**: `admin@cityevents.local` | `password`
    *   **Organiser**: `dawit@hawassa.et` | `password`
    *   **Attendee**: `meron@hawassa.et` | `password`

---

**Notes for teammate:**
- Ensure the `uploads/` directory has write permissions (`chmod -R 755`).
- The rate limiter is currently set to **20 actions per 5 minutes**—can be adjusted in `config.php`.
