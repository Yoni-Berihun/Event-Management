<?php
// Organiser/admin event update with full validation, form preservation, image replacement, re-approval reset.
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../includes/upload.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/policy.php';
require_once __DIR__ . '/../../../models/EventModel.php';

require_login();

if (!policy_is_admin() && current_user_role() !== 'organizer') {
    set_flash('error', 'You do not have permission to edit events.');
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
$eventId     = request_int($_POST, 'event_id');
$title       = request_string($_POST, 'title');
$description = request_string($_POST, 'description');
$location    = request_string($_POST, 'location');
$category    = request_string($_POST, 'category');
$eventDate   = request_string($_POST, 'event_date');
$eventEnd    = request_string($_POST, 'event_end');
$capacity    = request_int($_POST, 'capacity');
$editReason  = request_string($_POST, 'edit_reason');
$actorId     = current_user_id() ?? 0;
$isAdmin     = policy_is_admin();

// ── Preserve old input immediately ────────────────────────────────────────────
$oldInput = [
    'event_id'    => (string) $eventId,
    'title'       => $title,
    'description' => $description,
    'location'    => $location,
    'category'    => $category,
    'event_date'  => $eventDate,
    'event_end'   => $eventEnd,
    'capacity'    => $capacity > 0 ? (string) $capacity : '',
    'edit_reason' => $editReason,
];

// ── Collect ALL validation errors at once ─────────────────────────────────────
$errors = collect_validation_errors([
    'event_id'    => validate_positive_int($eventId, 'Event'),
    'title'       => validate_required($title, 'Title'),
    'title_len'   => ($title !== '') ? validate_max_length($title, 150, 'Title') : true,
    'location'    => validate_required($location, 'Location'),
    'loc_len'     => ($location !== '') ? validate_max_length($location, 150, 'Location') : true,
    'category'    => validate_required($category, 'Category'),
    'description' => validate_required($description, 'Description'),
    'desc_len'    => ($description !== '') ? validate_max_length($description, 5000, 'Description') : true,
    'event_date'  => validate_event_datetime_format($eventDate, 'Start date'),
    'event_end'   => validate_event_datetime_format($eventEnd, 'End date'),
    'capacity'    => validate_positive_int($capacity, 'Capacity'),
    'reason_len'  => validate_max_length($editReason, 500, 'Edit reason'),
]);

if (!empty($errors)) {
    foreach ($errors as $msg) {
        set_flash('error', $msg);
    }
    set_validation_errors($errors);
    set_old_input($oldInput);
    redirect('dashboard');
}

// ── Cross-field checks ────────────────────────────────────────────────────────
$dtStart = parse_event_datetime($eventDate);
$dtEnd   = parse_event_datetime($eventEnd);

if ($dtEnd <= $dtStart) {
    set_flash('error', 'End date/time must be after the start date/time.');
    set_validation_errors(['event_end' => 'End date/time must be after the start date/time.']);
    set_old_input($oldInput);
    redirect('dashboard');
}

if (($dtEnd->getTimestamp() - $dtStart->getTimestamp()) / 60 < MIN_EVENT_DURATION_MINUTES) {
    $msg = 'Event must be at least ' . MIN_EVENT_DURATION_MINUTES . ' minutes long.';
    set_flash('error', $msg);
    set_validation_errors(['event_end' => $msg]);
    set_old_input($oldInput);
    redirect('dashboard');
}

// ── Lock event row and verify ownership ──────────────────────────────────────
try {
    $pdo = get_pdo();
    $pdo->beginTransaction();

    $eStmt = $pdo->prepare('SELECT * FROM events WHERE id=:id FOR UPDATE');
    $eStmt->execute([':id' => $eventId]);
    $event = $eStmt->fetch();

    if (!$event || !can_edit_event($actorId, $event)) {
        $pdo->rollBack();
        set_flash('error', 'Event not found or you do not have permission to edit it.');
        redirect('dashboard');
    }

    $oldImagePath   = $event['image_path'] ?? null;
    $newImagePath   = $oldImagePath;
    $resetToPending = !$isAdmin && (int) ($event['is_verified'] ?? 0) === 1;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    log_exception($e, 'Update event lock');
    set_flash('error', 'Something went wrong. Please try again.');
    set_old_input($oldInput);
    redirect('dashboard');
}

// ── Image replacement (centralized, 5MB) ─────────────────────────────────────
if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $uploaded = handle_image_upload('image');
    if ($uploaded === false) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        set_old_input($oldInput);
        redirect('dashboard');
    }
    if ($uploaded !== null) {
        // Delete old file only after successful upload.
        delete_uploaded_image($oldImagePath);
        $newImagePath = $uploaded;
    }
}

// ── DB update ─────────────────────────────────────────────────────────────────
try {
    $newVerified = $resetToPending ? 0 : (int) ($event['is_verified'] ?? 0);
    $pdo->prepare(
        'UPDATE events
         SET title=:title, description=:desc, image_path=:img,
             location=:loc, category=:cat, event_date=:date, event_end=:end, capacity=:cap,
             is_verified=:ver, edited_at=NOW(), edited_by=:by, edit_reason=:reason
         WHERE id=:id'
    )->execute([
        ':id'     => $eventId,
        ':title'  => $title,
        ':desc'   => $description,
        ':img'    => $newImagePath,
        ':loc'    => $location,
        ':cat'    => $category !== '' ? $category : 'General',
        ':date'   => $dtStart->format('Y-m-d H:i:s'),
        ':end'    => $dtEnd->format('Y-m-d H:i:s'),
        ':cap'    => $capacity,
        ':ver'    => $newVerified,
        ':by'     => $actorId,
        ':reason' => $editReason !== '' ? $editReason : null,
    ]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    log_exception($e, 'Update event write');
    set_flash('error', 'Something went wrong. Please try again.');
    set_old_input($oldInput);
    redirect('dashboard');
}

set_flash('success', $resetToPending
    ? 'Event changes submitted and pending re-approval.'
    : 'Event updated successfully.');
redirect('dashboard');
