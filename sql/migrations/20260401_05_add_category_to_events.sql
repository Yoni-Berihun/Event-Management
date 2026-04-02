-- Migration: Add category to events table
-- Safe to run multiple times; uses IF NOT EXISTS guards.
-- Run this AFTER migration 20260331_04_fix_seed_data.sql

USE city_events;

-- ── 1. Add category column to events ─────────────────────────────────────────
-- Using VARCHAR(60) to keep it flexible (admin-defined taxonomy in the future).
ALTER TABLE events
    ADD COLUMN IF NOT EXISTS category VARCHAR(60) NULL DEFAULT NULL AFTER location;

-- Index to support filtering events by category.
CREATE INDEX IF NOT EXISTS idx_events_category ON events (category);

-- ── 2. Back-fill existing events with a sensible default ─────────────────────
-- Set all currently NULL categories to 'General' so the UI doesn't show blanks.
UPDATE events
SET category = 'General'
WHERE category IS NULL;

-- ── 3. Verify (manual check, comment out if running non-interactively) ────────
-- SELECT id, title, category FROM events LIMIT 10;
