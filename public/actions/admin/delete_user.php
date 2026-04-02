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
$actorId  = current_user_id() ?? 0;

if ($targetId <= 0) {
    set_flash('error', 'Invalid user.');
    redirect('admin_users');
}

if ($targetId === $actorId) {
    set_flash('error', 'You cannot delete your own account.');
    redirect('admin_users');
}

try {
    $ok = user_delete($targetId);
} catch (Throwable $e) {
    log_exception($e, 'Delete user');
    set_flash('error', 'Something went wrong.');
    redirect('admin_users');
}

set_flash($ok ? 'success' : 'error', $ok ? 'User account deleted.' : 'Could not delete user.');
redirect('admin_users');
