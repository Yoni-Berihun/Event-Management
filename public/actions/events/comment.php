<?php
// Handle adding comments and organizer replies.

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/policy.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../models/CommentModel.php';
require_once __DIR__ . '/../../../models/EventModel.php';

/**
 * Redirect back to event detail when possible.
 */
function redirect_to_event_or_feed(int $eventId): void
{
    if ($eventId > 0) {
        redirect('event_detail', ['id' => $eventId]);
    }

    redirect('event_feed');
}

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('event_feed');
}

$eventId = (int) ($_POST['event_id'] ?? 0);

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect_to_event_or_feed($eventId);
}

$body     = trim($_POST['body'] ?? '');
$parentId = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== ''
    ? (int) $_POST['parent_comment_id']
    : null;

if ($eventId <= 0) {
    set_flash('error', 'Invalid event.');
    redirect('event_feed');
}

foreach ([
    validate_required($body, 'Feedback'),
    validate_max_length($body, 1000, 'Feedback'),
] as $err) {
    if ($err !== true) {
        set_flash('error', $err);
        redirect_to_event_or_feed($eventId);
    }
}

$userId = current_user_id() ?? 0;
try {
    $event = event_get_by_id($eventId);
} catch (PDOException $e) {
    log_exception($e, 'Comment DB read error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect_to_event_or_feed($eventId);
} catch (Throwable $e) {
    log_exception($e, 'Comment read error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect_to_event_or_feed($eventId);
}

if ($userId <= 0) {
    set_flash('error', 'You must be logged in to comment.');
    redirect('login', ['redirect' => url_for('event_detail', ['id' => $eventId])]);
}

if (!$event) {
    set_flash('error', 'Event not found.');
    redirect('event_feed');
}

$isAdmin         = policy_is_admin();
$isOrganizerOwner = policy_is_event_owner($userId, $event);
$eventHasEnded = has_datetime_passed((string) ($event['event_date'] ?? ''));

if (!can_comment_event($userId, $event, null, true)) {
    set_flash('error', 'This event is not available for comments yet.');
    redirect_to_event_or_feed($eventId);
}

// Only allow attendee feedback after the event ends.
if ($parentId === null && !$eventHasEnded && !$isAdmin && !$isOrganizerOwner) {
    set_flash('error', 'You can submit your feedback only after the event ends.');
    redirect_to_event_or_feed($eventId);
}

try {
    if ($parentId === null) {
        $ok = comment_add($eventId, $userId, $body);
    } else {
        // Keep replies controlled: only the event organizer/admin can post replies.
        if (!$isAdmin && !$isOrganizerOwner) {
            set_flash('error', 'Only the event organizer can reply to comments.');
            redirect_to_event_or_feed($eventId);
        }

        $parentComment = comment_get_by_id($parentId);
        if (!$parentComment || (int) $parentComment['event_id'] !== $eventId) {
            set_flash('error', 'Invalid parent comment for this event.');
            redirect_to_event_or_feed($eventId);
        }

        $ok = comment_add_reply($eventId, $userId, $parentId, $body);
    }
} catch (PDOException $e) {
    log_exception($e, 'Comment DB error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect_to_event_or_feed($eventId);
} catch (Throwable $e) {
    log_exception($e, 'Comment unexpected error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect_to_event_or_feed($eventId);
}

set_flash($ok ? 'success' : 'error', $ok ? 'Comment posted.' : 'Could not post comment.');
redirect('event_detail', ['id' => $eventId]);
