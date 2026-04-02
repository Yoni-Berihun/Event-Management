<?php
// Session helpers: start session and expose simple auth utilities.

declare(strict_types=1);

const REMEMBER_ME_COOKIE_NAME = 'remember_me';
const REMEMBER_ME_TTL_SECONDS = 60 * 60 * 24 * 30; // 30 days

/**
 * Start the PHP session if it has not started yet.
 */
function start_session_if_needed(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

start_session_if_needed();

/**
 * Log in a user by storing their id and role in the session.
 */
function login_user(array $user): void
{
    // Prevent session fixation on successful authentication.
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'] ?? null;
    update_session_role((string) ($user['role'] ?? 'attendee'));
}

/**
 * Update role in session and regenerate ID if privilege changed.
 */
function update_session_role(string $newRole): void
{
    $safeRole = in_array($newRole, ['attendee', 'organizer', 'admin'], true) ? $newRole : 'attendee';
    $oldRole = $_SESSION['user_role'] ?? null;

    if ($oldRole !== null && $oldRole !== $safeRole) {
        // Optional hardening: rotate session when role/privilege changes.
        session_regenerate_id(true);
    }

    $_SESSION['user_role'] = $safeRole;
}

/**
 * Build cookie params consistently for session/remember-me cookies.
 *
 * @return array<string, mixed>
 */
function auth_cookie_params(int $expires = 0): array
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    return [
        'expires' => $expires,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ];
}

/**
 * Remove remember-me cookie from browser.
 */
function clear_remember_me_cookie(): void
{
    setcookie(REMEMBER_ME_COOKIE_NAME, '', auth_cookie_params(time() - 42000));
}

/**
 * Issue a remember-me token and set secure cookie.
 */
function issue_remember_me_token(int $userId): void
{
    try {
        require_once __DIR__ . '/../models/RememberTokenModel.php';

        $selector = bin2hex(random_bytes(8));
        $validator = bin2hex(random_bytes(32));
        $validatorHash = hash('sha256', $validator);
        $expiresAt = date('Y-m-d H:i:s', time() + REMEMBER_ME_TTL_SECONDS);

        remember_token_create($userId, $selector, $validatorHash, $expiresAt);
        setcookie(
            REMEMBER_ME_COOKIE_NAME,
            $selector . ':' . $validator,
            auth_cookie_params(time() + REMEMBER_ME_TTL_SECONDS)
        );
    } catch (Throwable $e) {
        error_log('Issue remember-me token error: ' . $e->getMessage());
        clear_remember_me_cookie();
    }
}

/**
 * Delete remember-me token referenced by current cookie (if present).
 */
function delete_current_remember_me_token(): void
{
    $cookie = $_COOKIE[REMEMBER_ME_COOKIE_NAME] ?? '';
    if (!is_string($cookie) || $cookie === '' || !str_contains($cookie, ':')) {
        clear_remember_me_cookie();
        return;
    }

    try {
        require_once __DIR__ . '/../models/RememberTokenModel.php';

        [$selector] = explode(':', $cookie, 2);
        if ($selector !== '') {
            remember_token_delete_by_selector($selector);
        }
    } catch (Throwable $e) {
        error_log('Delete remember-me token error: ' . $e->getMessage());
    }

    clear_remember_me_cookie();
}

/**
 * Auto-login using remember-me cookie when session is not authenticated.
 */
function login_from_remember_me_if_possible(): void
{
    if (current_user_id() !== null) {
        return;
    }

    $cookie = $_COOKIE[REMEMBER_ME_COOKIE_NAME] ?? '';
    if (!is_string($cookie) || $cookie === '' || !str_contains($cookie, ':')) {
        return;
    }

    [$selector, $validator] = explode(':', $cookie, 2);
    if ($selector === '' || $validator === '') {
        clear_remember_me_cookie();
        return;
    }

    try {
        require_once __DIR__ . '/../models/RememberTokenModel.php';
        require_once __DIR__ . '/../models/UserModel.php';

        remember_token_delete_expired();

        $tokenRow = remember_token_find_by_selector($selector);
        if (!$tokenRow) {
            clear_remember_me_cookie();
            return;
        }

        $expectedHash = (string) ($tokenRow['validator_hash'] ?? '');
        $providedHash = hash('sha256', $validator);
        $expiresAt = strtotime((string) ($tokenRow['expires_at'] ?? ''));

        if ($expectedHash === '' || !hash_equals($expectedHash, $providedHash) || $expiresAt === false || $expiresAt <= time()) {
            remember_token_delete_by_selector($selector);
            clear_remember_me_cookie();
            return;
        }

        $user = user_find_by_id((int) ($tokenRow['user_id'] ?? 0));
        if (!$user) {
            remember_token_delete_by_selector($selector);
            clear_remember_me_cookie();
            return;
        }

        login_user($user);

        // Token rotation on every remembered login.
        remember_token_delete_by_selector($selector);
        issue_remember_me_token((int) $user['id']);
    } catch (Throwable $e) {
        error_log('Remember-me login error: ' . $e->getMessage());
        clear_remember_me_cookie();
    }
}

/**
 * Log out the current user and clear their session data.
 */
function logout_user(): void
{
    delete_current_remember_me_token();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => 'Strict',
        ]);
    }
    session_destroy();
}

/**
 * Get the current logged-in user id or null if not logged in.
 */
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Get the current logged-in user role or null if not logged in.
 */
function current_user_role(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if the current user has the given role.
 */
function user_has_role(string $role): bool
{
    return current_user_role() === $role;
}

/**
 * Generate and return a CSRF token for this session.
 */
function get_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify that the given CSRF token matches the session token.
 */
function verify_csrf_token(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || $token === null) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

login_from_remember_me_if_possible();

