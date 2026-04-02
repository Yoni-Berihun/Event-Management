<?php
// Organiser event creation with full validation, form preservation, and centralized upload.
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../includes/upload.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/policy.php';
require_once __DIR__ . '/../../../models/EventModel.php';

require_organizer();

if (!can_create_event(current_user_id() ?? 0)) {
    set_flash('error', 'You do not have permission to create events.');
    redirect('event_feed');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('dashboard');
}

// ── Read inputs ───────────────────────────────────────────────────────────────
$title       = request_string($_POST, 'title');
$description = request_string($_POST, 'description');
$location    = request_string($_POST, 'location');
$category    = request_string($_POST, 'category');
$eventDate   = request_string($_POST, 'event_date');
$eventEnd    = request_string($_POST, 'event_end');
$capacity    = request_int($_POST, 'capacity');
$orgId       = current_user_id() ?? 0;

// ── Preserve old input immediately (before any redirect on error) ─────────────
$oldInput = [
    'title'       => $title,
    'description' => $description,
    'location'    => $location,
    'category'    => $category,
    'event_date'  => $eventDate,
    'event_end'   => $eventEnd,
    'capacity'    => $capacity > 0 ? (string) $capacity : '',
];

// ── Collect ALL validation errors at once ─────────────────────────────────────
$errors = collect_validation_errors([
    'title'       => validate_required($title, 'Title'),
    'title_len'   => ($title !== '') ? validate_max_length($title, 150, 'Title') : true,
    'location'    => validate_required($location, 'Location'),
    'loc_len'     => ($location !== '') ? validate_max_length($location, 150, 'Location') : true,
    'category'    => validate_required($category, 'Category'),
    'description' => validate_required($description, 'Description'),
    'desc_len'    => ($description !== '') ? validate_max_length($description, 5000, 'Description') : true,
    'event_date'  => validate_event_datetime_format($eventDate, 'Start date'),
    'start_bound' => ($eventDate !== '') ? validate_event_datetime_bounds($eventDate, 300, 60 * 60 * 24 * 365 * 10, 'Start date') : true,
    'event_end'   => validate_event_datetime_format($eventEnd, 'End date'),
    'capacity'    => validate_positive_int($capacity, 'Capacity'),
]);

if (!empty($errors)) {
    foreach ($errors as $msg) {
        set_flash('error', $msg);
    }
    set_validation_errors($errors);
    set_old_input($oldInput);
    redirect('dashboard');
}

// ── Cross-field: start < end and minimum duration ─────────────────────────────
$dtStart = parse_event_datetime($eventDate);
$dtEnd   = parse_event_datetime($eventEnd);

if ($dtEnd <= $dtStart) {
    set_flash('error', 'End date/time must be after the start date/time.');
    set_validation_errors(['event_end' => 'End date/time must be after the start date/time.']);
    set_old_input($oldInput);
    redirect('dashboard');
}

$durationMinutes = ($dtEnd->getTimestamp() - $dtStart->getTimestamp()) / 60;
if ($durationMinutes < MIN_EVENT_DURATION_MINUTES) {
    $msg = 'Event must be at least ' . MIN_EVENT_DURATION_MINUTES . ' minutes long.';
    set_flash('error', $msg);
    set_validation_errors(['event_end' => $msg]);
    set_old_input($oldInput);
    redirect('dashboard');
}

// ── Organiser buffer check (non-blocking warning) ─────────────────────────────
$bufferStart = $dtStart->modify('-' . ORGANIZER_BUFFER_MINUTES . ' minutes')->format('Y-m-d H:i:s');
$bufferEnd   = $dtEnd->modify('+' . ORGANIZER_BUFFER_MINUTES . ' minutes')->format('Y-m-d H:i:s');
$overlaps = event_detect_organizer_overlap($orgId, $bufferStart, $bufferEnd);
if (!empty($overlaps)) {
    $titles = implode(', ', array_column($overlaps, 'title'));
    set_flash('warning', "Note: this event is close to or overlaps your other event(s): {$titles}. Create proceeded anyway.");
}

// ── Image upload (optional, centralized) ─────────────────────────────────────
$imagePath = handle_image_upload('image');
if ($imagePath === false) {
    // handle_image_upload already set a flash error.
    set_old_input($oldInput);
    redirect('dashboard');
}

// ── DB insert ─────────────────────────────────────────────────────────────────
try {
    $eventId = event_create(
        $orgId, $title, $description, $imagePath, $location, $category,
        $dtStart->format('Y-m-d H:i:s'), $dtEnd->format('Y-m-d H:i:s'), $capacity
    );
} catch (Throwable $e) {
    log_exception($e, 'Create event DB');
    set_flash('error', 'Something went wrong. Please try again.');
    set_old_input($oldInput);
    redirect('dashboard');
}

if ($eventId === null) {
    set_flash('error', 'Could not create event. Please try again.');
    set_old_input($oldInput);
} else {
    set_flash('success', 'Event submitted and pending admin approval.');
}
redirect('dashboard');
