<?php
// Handle organizer event deletion (owner only).

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/policy.php';
require_once __DIR__ . '/../../../models/EventModel.php';

require_organizer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('dashboard');
}

$eventId = (int) ($_POST['event_id'] ?? 0);

if ($eventId <= 0) {
    set_flash('error', 'Invalid event.');
    redirect('dashboard');
}

try {
    $event = event_get_by_id($eventId);
    if (!$event || !can_edit_event(current_user_id() ?? 0, $event)) {
        set_flash('error', 'Event not found or you do not have permission to delete it.');
        redirect('dashboard');
    }

    if (policy_is_admin()) {
        $ok = event_delete($eventId, (int) $event['organizer_id']);
    } else {
        $ok = event_delete($eventId, current_user_id() ?? 0);
    }
} catch (PDOException $e) {
    log_exception($e, 'Delete event DB error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
} catch (Throwable $e) {
    log_exception($e, 'Delete event error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
}

set_flash($ok ? 'success' : 'error', $ok ? 'Event deleted.' : 'Could not delete event or you do not own it.');
redirect('dashboard');
