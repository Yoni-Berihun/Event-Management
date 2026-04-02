# Before pushing to GitHub

## 1. Exclude private / heavy data

The folder `ChatExport_2026-03-11/` contains channel export HTML and photos. It is **ignored by `.gitignore`** because:

- It can include **private messages** and personal data.
- It is **large** and not needed for the app to run (import is optional via scripts).

Teammates who need sample data: use `sql/seed.sql` if present, or run `scripts/import_channel_announcements.php` with their **own** export placed locally (not committed).

## 2. Do not commit uploads

`public/uploads/` is ignored except `.gitkeep` so the folder exists. Real images stay local.

## 3. Database credentials

`config/config.php` may contain local DB user/password. For coursework, empty `root` password is common; if you set a real password, either:

- Keep repo private and accept local config in history, or
- Later: use `config.example.php` + local `config.php` ignored (planned in roadmap).

## 4. Quick checklist

- [ ] `.gitignore` present; `ChatExport_2026-03-11/` not staged (`git status`).
- [ ] No `public/uploads/*` files staged (only `.gitkeep` if needed).
- [ ] No `.log` files staged.
- [ ] `README.md` and `CONTRIBUTING.md` up to date for teammates.
- [ ] First commit message clear (e.g. `chore: initial coursework project`).

## 5. First push (example)

```bash
git init
git add .
git status   # verify ChatExport and uploads are not listed
git commit -m "chore: initial Event Tracking System coursework"
git branch -M main
git remote add origin https://github.com/Bini-fish/Event_Tracking_with_PHP.git
git push -u origin main
```
