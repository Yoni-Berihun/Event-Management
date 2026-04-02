<?php
// Handle registration form submission and create a new user.

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../models/UserModel.php';

/**
 * Keep safe fields only (never store passwords) for post-redirect refill.
 */
function remember_register_input(string $name, string $email, string $role): void
{
    set_old_input([
        'register' => [
            'name'  => $name,
            'email' => $email,
            'role'  => $role,
        ],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('register');
}

$name            = request_string($_POST, 'name');
$email           = strtolower(request_string($_POST, 'email'));
$password        = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');
$role            = $_POST['role'] ?? 'attendee';

remember_register_input($name, $email, $role);

foreach ([
    validate_required($name, 'Name'),
    validate_email($email, 'Email'),
    validate_password_strength($password, 8, true, 'Password'),
    validate_password_confirmation($password, $confirmPassword, 'Password'),
] as $err) {
    if ($err !== true) {
        set_flash('error', $err);
        redirect('register');
    }
}

if (!in_array($role, ['attendee', 'organizer'], true)) {
    $role = 'attendee';
}

try {
    if (user_find_by_email($email)) {
        set_flash('error', 'An account with that email already exists. Please use another email or login.');
        redirect('register');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $userId       = user_create($name, $email, $passwordHash, $role);

    if ($userId === null) {
        set_flash('error', 'Could not create account. Please try again.');
        redirect('register');
    }

    $user = user_find_by_id($userId);

    if ($user) {
        login_user($user);
    }

    set_flash('success', 'Account created successfully. Welcome!');

    if ($role === 'organizer') {
        redirect('dashboard');
    }

    redirect('event_feed');
} catch (PDOException $e) {
    // 23000 is SQLSTATE integrity violation (e.g. duplicate unique email).
    if ($e->getCode() === '23000') {
        remember_register_input($name, $email, $role);
        set_flash('error', 'An account with that email already exists. Please use another email or login.');
        redirect('register');
    }

    log_exception($e, 'Registration DB error');
    remember_register_input($name, $email, $role);
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('register');
} catch (Throwable $e) {
    log_exception($e, 'Registration unexpected error');
    remember_register_input($name, $email, $role);
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('register');
}
