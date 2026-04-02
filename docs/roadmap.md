# Roadmap (Post-Phase 1)

This roadmap is optimized for a student project: plain PHP, locally runnable, easy to explain.

## Phase 2 - Validation and authorization consistency

- Centralize reusable request validation helpers.
- Ensure every write action enforces ownership/role checks consistently.
- Normalize error messages and failure handling.

Deliverables:
- Validation helper functions.
- Policy checklist per action file.
- Updated implementation notes.

## Phase 3 - Config quality (local-first)

- Keep local-first setup, but reduce hardcoded configuration coupling.
- Introduce a simple optional env-loading approach (without heavy framework).
- Update setup steps to avoid confusion between machines.

Deliverables:
- Config strategy doc.
- Updated `README.md` setup section.

## Phase 4 - Database lifecycle hygiene

- Separate baseline schema from sample data clearly.
- Define versioned SQL changes for future updates.
- Add rollback notes for team safety.

Deliverables:
- SQL organization guide in docs.
- Initial migration/version convention.

## Phase 5 - Testing foundation

- Add basic tests around auth, event CRUD, RSVP limits, approval flow.
- Start with small, high-value smoke/integration checks.

Deliverables:
- Minimal test structure and run instructions.
- First test cases for core flows.

## Phase 6 - CI quality gate

- Run PHP syntax check + tests automatically.
- Add a lightweight pull request checklist gate.

Deliverables:
- CI workflow file.
- "Definition of done" checklist in docs.
