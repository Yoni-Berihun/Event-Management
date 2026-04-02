<?php
// Handle logout by ending the user session.

declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid logout request.');
        redirect('event_feed');
    }

    logout_user();
    set_flash('success', 'You have been logged out.');
}

redirect('event_feed');
