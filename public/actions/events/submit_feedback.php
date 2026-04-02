<?php
// Submit post-event feedback (rating + optional comment).
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/rate_limit.php';
require_once __DIR__ . '/../../../models/EventModel.php';
require_once __DIR__ . '/../../../models/RsvpModel.php';
require_once __DIR__ . '/../../../models/FeedbackModel.php';

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

if (!rate_limit_check('feedback', RATE_LIMIT_MAX_ACTION, RATE_LIMIT_WINDOW_SECONDS)) {
    set_flash('error', 'Too many feedback submissions. Please wait a few minutes.');
    redirect('event_detail', ['id' => $eventId]);
}

$event = $eventId > 0 ? event_get_by_id($eventId) : null;
if (!$event) {
    set_flash('error', 'Event not found.');
    redirect('event_feed');
}

// Gate: event must have ended.
if (!has_datetime_passed((string) ($event['event_end'] ?? $event['event_date'] ?? ''))) {
    set_flash('error', 'Feedback can only be submitted after the event ends.');
    redirect('event_detail', ['id' => $eventId]);
}

// Gate: user must be an approved attendee.
if (!rsvp_user_is_approved_attendee($eventId, $userId)) {
    set_flash('error', 'Only confirmed attendees can submit feedback.');
    redirect('event_detail', ['id' => $eventId]);
}

// Gate: only once.
if (feedback_has_submitted($eventId, $userId)) {
    set_flash('error', 'You have already submitted feedback for this event.');
    redirect('event_detail', ['id' => $eventId]);
}

$rating  = (int) ($_POST['rating'] ?? 0);
$comment = request_string($_POST, 'comment');

if ($rating < 1 || $rating > 5) {
    set_flash('error', 'Please select a rating between 1 and 5.');
    redirect('event_detail', ['id' => $eventId]);
}

if (($err = validate_max_length($comment, 1000, 'Comment')) !== true) {
    set_flash('error', $err);
    redirect('event_detail', ['id' => $eventId]);
}

try {
    $ok = feedback_submit($eventId, $userId, $rating, $comment);
} catch (Throwable $e) {
    log_exception($e, 'Feedback submit DB');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('event_detail', ['id' => $eventId]);
}

set_flash($ok ? 'success' : 'error', $ok ? 'Thank you for your feedback!' : 'Could not submit feedback.');
redirect('event_detail', ['id' => $eventId]);
