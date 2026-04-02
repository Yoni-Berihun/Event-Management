<?php
// Admin: add a new user account with full validation and duplicate-email handling.
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../models/UserModel.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin_users');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('admin_users');
}

// ── Read inputs ───────────────────────────────────────────────────────────────
$name     = request_string($_POST, 'name');
$email    = strtolower(request_string($_POST, 'email'));
$role     = request_string($_POST, 'role');
$password = (string) ($_POST['password'] ?? '');

// ── Preserve safe fields on failure (never store raw password) ─────────────
$oldInput = [
    'add_user' => [
        'name'  => $name,
        'email' => $email,
        'role'  => $role,
    ],
];

// ── Collect ALL validation errors ─────────────────────────────────────────────
$errors = collect_validation_errors([
    'name'     => validate_required($name, 'Name'),
    'name_len' => ($name !== '') ? validate_max_length($name, 100, 'Name') : true,
    'email'    => validate_email($email, 'Email'),
    'role'     => in_array($role, ['attendee', 'organizer', 'admin'], true)
                    ? true
                    : 'Role must be attendee, organizer, or admin.',
    'password' => validate_password_strength($password, 8, true, 'Password'),
]);

if (!empty($errors)) {
    foreach ($errors as $msg) {
        set_flash('error', $msg);
    }
    set_validation_errors($errors);
    set_old_input($oldInput);
    redirect('admin_users');
}

// ── Duplicate e-mail check ────────────────────────────────────────────────────
try {
    if (user_find_by_email($email)) {
        set_flash('error', 'An account with the email "' . $email . '" already exists. Please use a different address.');
        set_validation_errors(['email' => 'Email already in use.']);
        set_old_input($oldInput);
        redirect('admin_users');
    }
} catch (Throwable $e) {
    log_exception($e, 'Add user: email check');
    set_flash('error', 'Something went wrong. Please try again.');
    set_old_input($oldInput);
    redirect('admin_users');
}

// ── Create user ───────────────────────────────────────────────────────────────
try {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $userId       = user_create($name, $email, $passwordHash, $role);

    if ($userId === null) {
        set_flash('error', 'Could not create user account. Please try again.');
        set_old_input($oldInput);
        redirect('admin_users');
    }
} catch (Throwable $e) {
    // 23000 = SQLSTATE integrity violation (unique email duplicate race).
    if ($e->getCode() === '23000') {
        set_flash('error', 'An account with the email "' . $email . '" already exists.');
        set_validation_errors(['email' => 'Email already in use.']);
        set_old_input($oldInput);
        redirect('admin_users');
    }

    log_exception($e, 'Add user: create');
    set_flash('error', 'Something went wrong. Please try again.');
    set_old_input($oldInput);
    redirect('admin_users');
}

set_flash('success', 'User account for "' . $name . '" created successfully. They can now log in.');
redirect('admin_users');
