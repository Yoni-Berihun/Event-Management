-- Database schema for City-Wide Event Tracking System
-- This schema is intentionally simple and viva-friendly.

CREATE DATABASE IF NOT EXISTS city_events
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE city_events;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('attendee', 'organizer', 'admin') NOT NULL DEFAULT 'attendee',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organizer_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    location VARCHAR(150) NOT NULL,
    event_date DATETIME NOT NULL,
    capacity INT UNSIGNED NOT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    edited_at DATETIME NULL,
    edited_by INT UNSIGNED NULL,
    edit_reason VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_organizer FOREIGN KEY (organizer_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_events_edited_by FOREIGN KEY (edited_by) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE rsvps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rsvps_event FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_rsvps_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT uq_rsvps_event_user UNIQUE (event_id, user_id)
);

CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    parent_comment_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_event FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent FOREIGN KEY (parent_comment_id) REFERENCES comments(id)
        ON DELETE CASCADE
);

CREATE TABLE remember_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    selector VARCHAR(32) NOT NULL UNIQUE,
    validator_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_remember_tokens_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_remember_tokens_expires_at (expires_at)
);

