-- Migration: event edit audit fields + re-approval support.

ALTER TABLE events
    ADD COLUMN IF NOT EXISTS edited_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS edited_by INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS edit_reason VARCHAR(500) NULL;

-- Add FK only if missing (MySQL/MariaDB-friendly dynamic check).
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'events'
      AND CONSTRAINT_NAME = 'fk_events_edited_by'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql := IF(
    @fk_exists = 0,
    'ALTER TABLE events ADD CONSTRAINT fk_events_edited_by FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

