<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../models/UserModel.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin_users'); }
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('admin_users');
}

$targetId = (int) ($_POST['user_id'] ?? 0);
$newRole  = (string) ($_POST['role'] ?? '');
$actorId  = current_user_id() ?? 0;

if ($targetId <= 0) {
    set_flash('error', 'Invalid user.');
    redirect('admin_users');
}

if ($targetId === $actorId) {
    set_flash('error', 'You cannot change your own role.');
    redirect('admin_users');
}

try {
    $ok = user_update_role($targetId, $newRole);
} catch (Throwable $e) {
    log_exception($e, 'Update user role');
    set_flash('error', 'Something went wrong.');
    redirect('admin_users');
}

set_flash($ok ? 'success' : 'error', $ok ? 'User role updated.' : 'Could not update role.');
redirect('admin_users');
