# Contributing Guide

Thanks for contributing to this project.

Team note: keep changes explainable for coursework and easy to review.

## Branching

- Use short feature branches from your latest main branch:
  - `feature/<topic>`
  - `fix/<topic>`
  - `docs/<topic>`

## Commit style

- Keep commits focused and small.
- Use clear intent in commit subject:
  - `feat: ...`
  - `fix: ...`
  - `docs: ...`
  - `refactor: ...`

## Pull request checklist

- Code runs locally.
- No unrelated files changed.
- Security-sensitive paths reviewed (`public/actions/*`, auth/session, uploads).
- Docs updated when behavior changes.
- Add or update manual test steps in PR description.

## Review expectations

- Prioritize correctness and security over style changes.
- Preserve plain PHP conventions used in this repository.
- Prefer simple and explicit logic over abstract patterns.

## Local setup reminder

- Follow `README.md` setup instructions.
- Import `sql/schema.sql` before testing new flows.

## Suggested PR template

Use this in PR descriptions:

```md
## What changed
- ...

## Why
- ...

## Manual test
- [ ] Scenario 1
- [ ] Scenario 2

## Notes for teammate
- ...
```
