<?php
// Small helper functions shared across the application.

declare(strict_types=1);

/**
 * Escape a string for safe HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Build an absolute URL to a given page with optional query parameters.
 */
function url_for(string $page, array $params = []): string
{
    $params = array_merge(['page' => $page], array_filter($params, static fn($v) => $v !== null));
    $query = http_build_query($params);

    return BASE_URL . 'index.php' . ($query ? '?' . $query : '');
}

/**
 * Redirect to a given page and stop further script execution.
 */
function redirect(string $page, array $params = []): void
{
    header('Location: ' . url_for($page, $params));
    exit;
}

/**
 * Store a flash message in the session to show on the next page load.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

/**
 * Retrieve and clear all flash messages from the session.
 */
function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $flashes;
}

/**
 * Store form input data in session for one-time reuse after redirect.
 */
function set_old_input(array $input): void
{
    $_SESSION['old_input'] = $input;
}

/**
 * Retrieve and clear old form input from session.
 */
function get_old_input(): array
{
    $oldInput = $_SESSION['old_input'] ?? [];
    unset($_SESSION['old_input']);

    return is_array($oldInput) ? $oldInput : [];
}

/**
 * Retrieve a single old input value by dot-notation key (e.g. 'event.title').
 */
function old(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = get_old_input();
        // Re-store so subsequent calls within the same request still work.
        if (!empty($cache)) {
            $_SESSION['old_input'] = $cache;
        }
    }

    $segments = explode('.', $key);
    $value = $cache;
    foreach ($segments as $seg) {
        if (!is_array($value) || !array_key_exists($seg, $value)) {
            return $default;
        }
        $value = $value[$seg];
    }
    return is_string($value) ? $value : $default;
}

/**
 * Store field-specific validation errors in session.
 */
function set_validation_errors(array $errors): void
{
    $_SESSION['validation_errors'] = $errors;
}

/**
 * Retrieve and clear field-specific validation errors.
 */
function get_validation_errors(): array
{
    $errors = $_SESSION['validation_errors'] ?? [];
    unset($_SESSION['validation_errors']);
    return is_array($errors) ? $errors : [];
}

/**
 * Peek at validation errors without clearing (for views that render partials).
 */
function peek_validation_errors(): array
{
    return $_SESSION['validation_errors'] ?? [];
}

/**
 * Check whether a datetime string is in the past (event has ended).
 */
function has_datetime_passed(string $dateTime): bool
{
    try {
        $eventTime = new DateTimeImmutable($dateTime);
        $now = new DateTimeImmutable('now');
        return $eventTime <= $now;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Log exception details (message + stack trace) server-side.
 */
function log_exception(Throwable $e, string $context = 'Application error'): void
{
    error_log(
        $context
        . ': ' . $e->getMessage()
        . PHP_EOL
        . $e->getTraceAsString()
    );
}

