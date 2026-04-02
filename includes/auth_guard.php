<?php
// Auth guards: protect routes based on login status and role.

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';

/**
 * Require any logged-in user or redirect to login page.
 */
function require_login(): void
{
    if (current_user_id() === null) {
        redirect('login', ['redirect' => $_SERVER['REQUEST_URI'] ?? null]);
    }
}

/**
 * Require a specific user role or show a 403 page.
 */
function require_role(string $role): void
{
    require_login();

    if (!user_has_role($role)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

/**
 * Require an admin user role for admin-only pages.
 */
function require_admin(): void
{
    require_role('admin');
}

/**
 * Require an organizer user role for organizer pages.
 */
function require_organizer(): void
{
    require_role('organizer');
}

/**
 * Require either admin or organizer role.
 */
function require_admin_or_organizer(): void
{
    require_login();

    if (!user_has_role('admin') && !user_has_role('organizer')) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

