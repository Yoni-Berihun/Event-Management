-- Migration: RSVP approval workflow, event end_date, feedback table
-- Safe to run once; uses IF NOT EXISTS / IF EXISTS guards throughout.

USE city_events;

-- ── 1. RSVPs: add status + approval audit columns ───────────────────────────
ALTER TABLE rsvps
    ADD COLUMN IF NOT EXISTS `status`      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER user_id,
    ADD COLUMN IF NOT EXISTS approved_at   DATETIME NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS approved_by   INT UNSIGNED NULL AFTER approved_at;

-- Only add FK if it doesn't already exist (MySQL 8+).
-- Wrapped defensively; will silently fail if already present.
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'city_events'
      AND TABLE_NAME = 'rsvps'
      AND CONSTRAINT_NAME = 'fk_rsvps_approved_by'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE rsvps ADD CONSTRAINT fk_rsvps_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index to speed up "pending RSVPs for event" queries.
CREATE INDEX IF NOT EXISTS idx_rsvps_event_status ON rsvps (event_id, `status`);

-- Index to speed up time-conflict detection queries (user's upcoming events).
CREATE INDEX IF NOT EXISTS idx_rsvps_user_status ON rsvps (user_id, `status`);

-- ── 2. Events: add start_date / end_date for proper time spans ───────────────
-- event_date keeps its existing semantic meaning (equals "start" for BC compat).
-- We add event_end for the end time; validated start < end in PHP on create/update.
ALTER TABLE events
    ADD COLUMN IF NOT EXISTS event_end DATETIME NULL AFTER event_date;

-- Index to speed up organiser overlap / conflict detection.
CREATE INDEX IF NOT EXISTS idx_events_organizer_dates ON events (organizer_id, event_date, event_end);

-- ── 3. Feedback table ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS feedback (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id   INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_feedback_event_user UNIQUE (event_id, user_id),
    CONSTRAINT fk_feedback_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Index for aggregate queries (avg rating per event).
CREATE INDEX IF NOT EXISTS idx_feedback_event ON feedback (event_id);

-- ── 4. Rate-limiting table (sliding window, lightweight) ─────────────────────
CREATE TABLE IF NOT EXISTS rate_limit_hits (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`      VARCHAR(190) NOT NULL,
    hit_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rate_limit_key_hit (`key`, hit_at)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
