# Database migrations (local-first)

This project is plain PHP + MySQL. To avoid re-importing the full schema every time the database changes, migrations live in `sql/migrations/`.

## How to run a migration

- **Option A (MySQL CLI)**

```bash
mysql -u your_user -p city_events < sql/migrations/20260330_01_create_remember_tokens.sql
mysql -u your_user -p city_events < sql/migrations/20260330_02_add_event_edit_audit_and_reapproval.sql
```

- **Option B (phpMyAdmin / MySQL Workbench)**

- Open migration files in `sql/migrations/` (including `20260330_01_*` and `20260330_02_*`)
- Run it against the `city_events` database.

## Migration notes

- Migrations are intended to be **safe to re-run** (`CREATE TABLE IF NOT EXISTS`).
- If you already imported `sql/schema.sql` after 2026-03-30, you may already have the `remember_tokens` table. Running the migration again is still safe.

