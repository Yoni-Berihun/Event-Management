<?php
// Handle admin approval/unapproval of events.

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../models/EventModel.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin_events');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('admin_events');
}

$eventId   = (int) ($_POST['event_id'] ?? 0);
$newStatus = ($_POST['is_verified'] ?? '0') === '1';

if ($eventId <= 0) {
    set_flash('error', 'Invalid event.');
    redirect('admin_events');
}

try {
    $ok = event_set_verified($eventId, $newStatus);
} catch (PDOException $e) {
    log_exception($e, 'Toggle approval DB error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('admin_events');
} catch (Throwable $e) {
    log_exception($e, 'Toggle approval error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('admin_events');
}

set_flash($ok ? 'success' : 'error', $ok ? 'Event status updated.' : 'Could not update event status.');
redirect('admin_events');
