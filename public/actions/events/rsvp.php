<?php
// Handle RSVP submissions with capacity check, conflict detection, and pending status.
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/policy.php';
require_once __DIR__ . '/../../../includes/rate_limit.php';
require_once __DIR__ . '/../../../models/EventModel.php';
require_once __DIR__ . '/../../../models/RsvpModel.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('event_feed');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('event_feed');
}

$userId  = current_user_id() ?? 0;
$eventId = (int) ($_POST['event_id'] ?? 0);

if (!rate_limit_check('rsvp', RATE_LIMIT_MAX_ACTION, RATE_LIMIT_WINDOW_SECONDS)) {
    set_flash('error', 'Too many RSVP attempts. Please wait a few minutes.');
    redirect('event_detail', ['id' => $eventId]);
}

if ($eventId <= 0) {
    set_flash('error', 'Invalid event.');
    redirect('event_feed');
}

try {
    $event = event_get_by_id($eventId);
} catch (Throwable $e) {
    log_exception($e, 'RSVP DB read');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('event_feed');
}

if (!$event) {
    set_flash('error', 'Event not found.');
    redirect('event_feed');
}

// Gate 1: event must be approved before any RSVP is allowed.
if ((int) ($event['is_verified'] ?? 0) !== 1) {
    set_flash('error', 'RSVP is only available for approved events. This event is pending admin approval.');
    redirect('event_detail', ['id' => $eventId]);
}

if (!can_rsvp_event($userId, $event, null, true)) {
    set_flash('error', 'This event is not open for RSVP.');
    redirect('event_feed');
}

if (rsvp_user_has_rsvped($eventId, $userId)) {
    set_flash('error', 'You have already submitted an RSVP for this event.');
    redirect('event_detail', ['id' => $eventId]);
}

// Time-conflict detection.
$conflicts      = rsvp_detect_conflicts($eventId, $userId);
$confirmed      = ($_POST['conflict_confirmed'] ?? '') === '1';
$hasConflicts   = !empty($conflicts);

if ($hasConflicts && !$confirmed) {
    // Store conflict data in session; send back to detail with modal trigger.
    $_SESSION['rsvp_conflicts'] = [
        'event_id'  => $eventId,
        'conflicts' => $conflicts,
    ];
    redirect('event_detail', ['id' => $eventId, 'show_conflict' => 1]);
}

// Clear any stale conflict session data.
unset($_SESSION['rsvp_conflicts']);

try {
    $ok = rsvp_add($eventId, $userId);
} catch (Throwable $e) {
    log_exception($e, 'RSVP DB write');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('event_detail', ['id' => $eventId]);
}

set_flash(
    $ok ? 'success' : 'error',
    $ok ? 'RSVP submitted! It is pending organizer approval.' : 'Could not submit RSVP.'
);
redirect('event_detail', ['id' => $eventId]);
