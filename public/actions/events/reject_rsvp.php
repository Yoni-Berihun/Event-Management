<?php
// Reject a pending RSVP (organiser or admin only).
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/policy.php';
require_once __DIR__ . '/../../../models/RsvpModel.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('dashboard');
}

$rsvpId  = (int) ($_POST['rsvp_id'] ?? 0);
$actorId = current_user_id() ?? 0;
$isAdmin = policy_is_admin();

if ($rsvpId <= 0) {
    set_flash('error', 'Invalid RSVP.');
    redirect('dashboard');
}

try {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT r.*, e.organizer_id FROM rsvps r JOIN events e ON e.id=r.event_id WHERE r.id=?');
    $stmt->execute([$rsvpId]);
    $rsvp = $stmt->fetch();
} catch (Throwable $e) {
    log_exception($e, 'Reject RSVP fetch');
    set_flash('error', 'Something went wrong.');
    redirect('dashboard');
}

if (!$rsvp) {
    set_flash('error', 'RSVP not found.');
    redirect('dashboard');
}

if (!$isAdmin && (int) $rsvp['organizer_id'] !== $actorId) {
    set_flash('error', 'You do not have permission to reject this RSVP.');
    redirect('dashboard');
}

try {
    $ok = rsvp_reject($rsvpId, $actorId);
} catch (Throwable $e) {
    log_exception($e, 'Reject RSVP DB');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
}

set_flash($ok ? 'success' : 'error', $ok ? 'RSVP rejected.' : 'Could not reject RSVP (already processed?).');
$redirect = $isAdmin ? 'admin_events' : 'dashboard';
redirect($redirect);
