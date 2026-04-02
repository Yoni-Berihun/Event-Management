# Import Events from Chat Export

This importer reads Telegram export files from `ChatExport_2026-03-11/` and inserts announcement-style posts into `events`.

## What it imports

- Only posts that contain:
  - date cues
  - time cues
  - location cues
- Uses one existing organizer email for all imported events.
- Copies source photos into `public/uploads/` and stores `events.image_path` as `uploads/<file>`.

## Script

- `scripts/import_channel_announcements.php`

## Run (dry-run first)

```bash
php scripts/import_channel_announcements.php --organizer-email="your-organizer@example.com" --dry-run
```

## Run live import

```bash
php scripts/import_channel_announcements.php --organizer-email="your-organizer@example.com" --default-capacity=120 --verified=1
```

## Optional arguments

- `--source-dir="path/to/export"` (default is `ChatExport_2026-03-11`)
- `--default-capacity=120`
- `--verified=1` (or `0`)
- `--dry-run`

## Notes

- Import is de-duplicated by organizer + title + event_date + location.
- If a matching event already exists, it is skipped.
- If a post matches announcement cues but date/location parsing fails, it is skipped as invalid.

## Project-specific helpers (this repo)

- **Organizer account (CLI):** `scripts/setup_organizer_user.php`  
  Default email: `bengaminfish@gmail.com`. **Do not put passwords in repo files.** Set once in the shell, then run:
  - PowerShell: `$env:ORGANIZER_PASSWORD = 'your-password-here'` then `php scripts/setup_organizer_user.php` then `Remove-Item Env:ORGANIZER_PASSWORD`
  - Or: `php scripts/setup_organizer_user.php --prompt` (type password when asked)
- **Import wrapper (same organizer email):** `scripts/import_channel_announcements_bengamin.ps1`  
  From project root: `.\scripts\import_channel_announcements_bengamin.ps1 -DryRun` then without `-DryRun` for live import.
