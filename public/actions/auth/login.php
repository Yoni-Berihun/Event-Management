<?php
// Handle login form submission and start a user session.

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../models/UserModel.php';

const LOGIN_ATTEMPT_WINDOW_SECONDS = 15 * 60; // 15 minutes
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_SECONDS = 15 * 60; // 15 minutes

/**
 * Build a stable session key for login throttling.
 */
function login_throttle_key(string $email): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash('sha256', strtolower($email) . '|' . $ip);
}

/**
 * Check if login attempts are currently locked.
 */
function login_is_locked(string $key): bool
{
    $entry = $_SESSION['login_attempts'][$key] ?? null;
    if (!is_array($entry)) {
        return false;
    }

    $lockUntil = (int) ($entry['lock_until'] ?? 0);
    if ($lockUntil > time()) {
        return true;
    }

    if ($lockUntil > 0 && $lockUntil <= time()) {
        unset($_SESSION['login_attempts'][$key]);
    }

    return false;
}

/**
 * Register a failed login attempt in current session.
 */
function register_login_failure(string $key): void
{
    $now = time();
    $entry = $_SESSION['login_attempts'][$key] ?? null;

    if (!is_array($entry) || $now - (int) ($entry['first_attempt_at'] ?? 0) > LOGIN_ATTEMPT_WINDOW_SECONDS) {
        $entry = [
            'first_attempt_at' => $now,
            'count' => 0,
            'lock_until' => 0,
        ];
    }

    $entry['count'] = (int) ($entry['count'] ?? 0) + 1;
    if ($entry['count'] >= LOGIN_MAX_ATTEMPTS) {
        $entry['lock_until'] = $now + LOGIN_LOCKOUT_SECONDS;
    }

    $_SESSION['login_attempts'][$key] = $entry;
}

/**
 * Clear login throttling entry after successful auth.
 */
function clear_login_failures(string $key): void
{
    unset($_SESSION['login_attempts'][$key]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('login');
}

$email    = request_string($_POST, 'email');
$password = $_POST['password'] ?? '';
$rememberMe = ($_POST['remember_me'] ?? '0') === '1';

if (($err = validate_required($email, 'Email')) !== true) {
    set_old_input([
        'login' => [
            'email' => $email,
        ],
    ]);
    set_flash('error', $err);
    redirect('login');
}

if (($err = validate_required((string) $password, 'Password')) !== true) {
    set_old_input([
        'login' => [
            'email' => $email,
        ],
    ]);
    set_flash('error', $err);
    redirect('login');
}

if (($err = validate_email($email, 'Email')) !== true) {
    set_old_input([
        'login' => [
            'email' => $email,
        ],
    ]);
    set_flash('error', $err);
    redirect('login');
}

$throttleKey = login_throttle_key($email);
if (login_is_locked($throttleKey)) {
    set_old_input([
        'login' => [
            'email' => $email,
        ],
    ]);
    set_flash('error', 'Too many failed attempts. Please wait 15 minutes before trying again.');
    redirect('login');
}

try {
    $user = user_find_by_email($email);

    if (!$user || !isset($user['password_hash']) || !password_verify((string) $password, (string) $user['password_hash'])) {
        register_login_failure($throttleKey);
        set_old_input([
            'login' => [
                'email' => $email,
            ],
        ]);
        set_flash('error', 'Invalid credentials.');
        redirect('login');
    }
} catch (PDOException $e) {
    log_exception($e, 'Login DB error');
    set_old_input([
        'login' => [
            'email' => $email,
        ],
    ]);
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('login');
} catch (Throwable $e) {
    log_exception($e, 'Login unexpected error');
    set_old_input([
        'login' => [
            'email' => $email,
        ],
    ]);
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('login');
}

login_user($user);
clear_login_failures($throttleKey);
delete_current_remember_me_token();
if ($rememberMe) {
    issue_remember_me_token((int) $user['id']);
}

$redirectTo = $_POST['redirect'] ?? null;

if (is_string($redirectTo) && $redirectTo !== '') {
    $parts = parse_url($redirectTo);

    // Only allow in-app relative paths to avoid open redirects.
    $isSafeRelativePath = $parts !== false
        && !isset($parts['scheme'], $parts['host'])
        && str_starts_with($redirectTo, '/')
        && !str_starts_with($redirectTo, '//');

    if ($isSafeRelativePath) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

if ($user['role'] === 'admin') {
    redirect('admin_events');
} elseif ($user['role'] === 'organizer') {
    redirect('dashboard');
} else {
    redirect('event_feed');
}
