<?php
// Lightweight request validation helpers for action handlers.

declare(strict_types=1);

/**
 * Validation contract
 * - All validate_* functions below return:
 *   - true on success
 *   - a human-friendly error message string on failure
 *
 * This makes it easy to integrate with flash messaging:
 *   $err = validate_required($name, 'Name');
 *   if ($err !== true) { set_flash('error', $err); redirect('register'); }
 *
 * Examples are included at the bottom of this file.
 */

/**
 * Return a trimmed string from request data.
 */
function request_string(array $data, string $key): string
{
    return trim((string) ($data[$key] ?? ''));
}

/**
 * Return an integer from request data.
 */
function request_int(array $data, string $key): int
{
    return (int) ($data[$key] ?? 0);
}

/**
 * Validate non-empty input (after trimming if string).
 */
function validate_required(mixed $value, string $label = 'This field'): true|string
{
    if (is_string($value)) {
        $value = trim($value);
    }

    if ($value === null) {
        return "{$label} is required.";
    }

    if (is_string($value) && $value === '') {
        return "{$label} is required.";
    }

    return true;
}

/**
 * Validate max length for strings (after trimming).
 */
function validate_max_length(string $value, int $maxLen, string $label = 'This field'): true|string
{
    $value = trim($value);
    if ($maxLen <= 0) {
        return 'Validation error: max length must be positive.';
    }

    $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    if ($length > $maxLen) {
        return "{$label} must be at most {$maxLen} characters.";
    }

    return true;
}

/**
 * Validate an email address format.
 */
function validate_email(string $email, string $label = 'Email'): true|string
{
    $email = trim($email);
    if ($email === '') {
        return "{$label} is required.";
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return "Please provide a valid {$label}.";
    }

    return true;
}

/**
 * Validate password strength.
 * - Minimum length is always required.
 * - Optional complexity requires at least one letter and one digit.
 */
function validate_password_strength(
    string $password,
    int $minLength = 8,
    bool $requireLetterAndNumber = true,
    string $label = 'Password'
): true|string {
    if ($password === '') {
        return "{$label} is required.";
    }

    if (strlen($password) < $minLength) {
        return "{$label} must be at least {$minLength} characters.";
    }

    if (!$requireLetterAndNumber) {
        return true;
    }

    $hasLetter = preg_match('/[A-Za-z]/', $password) === 1;
    $hasDigit  = preg_match('/\d/', $password) === 1;

    if (!$hasLetter || !$hasDigit) {
        return "{$label} must include at least one letter and one number.";
    }

    return true;
}

/**
 * Validate password confirmation.
 */
function validate_password_confirmation(string $password, string $confirmPassword, string $label = 'Password'): true|string
{
    if ($confirmPassword === '') {
        return "{$label} confirmation is required.";
    }

    if (!hash_equals($password, $confirmPassword)) {
        return "{$label} confirmation does not match.";
    }

    return true;
}

/**
 * Validate a positive integer field.
 */
function validate_positive_int(int $value, string $label = 'This field'): true|string
{
    if ($value <= 0) {
        return "{$label} must be a positive number.";
    }

    return true;
}

/**
 * Parse a datetime string from common HTML inputs.
 * Returns DateTimeImmutable on success, null on failure.
 */
function parse_event_datetime(string $value): ?DateTimeImmutable
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $formats = ['Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i', 'Y-m-d'];
    foreach ($formats as $format) {
        $dt = DateTimeImmutable::createFromFormat($format, $value);
        if ($dt instanceof DateTimeImmutable && $dt->format($format) === $value) {
            return $dt;
        }
    }

    return null;
}

/**
 * Validate datetime format for event date/time.
 */
function validate_event_datetime_format(string $value, string $label = 'Event date'): true|string
{
    if (trim($value) === '') {
        return "{$label} is required.";
    }

    if (parse_event_datetime($value) === null) {
        return "{$label} is invalid. Please use a valid date/time.";
    }

    return true;
}

/**
 * Validate date/time bounds for event dates.
 *
 * Default policy (safe baseline):
 * - event date must be in the future (now + 5 minutes)
 * - and not too far in the future (10 years)
 */
function validate_event_datetime_bounds(
    string $value,
    int $minOffsetSeconds = 300,
    int $maxFutureSeconds = 60 * 60 * 24 * 365 * 10,
    string $label = 'Event date'
): true|string {
    $dt = parse_event_datetime($value);
    if ($dt === null) {
        return "{$label} is invalid. Please use a valid date/time.";
    }

    $now = new DateTimeImmutable('now');
    $min = $now->modify('+' . $minOffsetSeconds . ' seconds');
    $max = $now->modify('+' . $maxFutureSeconds . ' seconds');

    if ($dt < $min) {
        return "{$label} must be in the future.";
    }

    if ($dt > $max) {
        return "{$label} is too far in the future.";
    }

    return true;
}

/**
 * Validate a minimum string length (after trimming).
 */
function validate_min_length(string $value, int $minLen, string $label = 'This field'): true|string
{
    $value = trim($value);
    $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    if ($length < $minLen) {
        return "{$label} must be at least {$minLen} characters.";
    }

    return true;
}

/**
 * Validate an uploaded file against size and MIME-type constraints.
 *
 * @param array  $file     The $_FILES['field'] entry.
 * @param int    $maxBytes Maximum file size in bytes.
 * @param array  $allowedMimes Map of MIME type => extension, e.g. ['image/jpeg' => 'jpg'].
 * @param string $label    Human-friendly field label.
 *
 * @return true|string True on success, error message on failure.
 */
function validate_file_upload(array $file, int $maxBytes, array $allowedMimes, string $label = 'File'): true|string
{
    $error   = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    $tmpName = (string) ($file['tmp_name'] ?? '');
    $size    = (int) ($file['size'] ?? 0);

    if ($error !== UPLOAD_ERR_OK) {
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return "{$label} exceeds the maximum allowed size.";
        }
        return "{$label} upload failed. Please try again.";
    }

    if (!is_uploaded_file($tmpName)) {
        return "{$label} upload is invalid.";
    }

    if ($size <= 0 || $size > $maxBytes) {
        $maxMB = round($maxBytes / (1024 * 1024), 1);
        return "{$label} must be under {$maxMB} MB.";
    }

    $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmpName);
    if (!isset($allowedMimes[$mime])) {
        $typeNames = array_map(fn($m) => strtoupper($m), array_values($allowedMimes));
        return "{$label} must be a " . implode(', ', array_unique($typeNames)) . " file.";
    }

    return true;
}

/**
 * Collect all validation errors at once instead of failing on the first one.
 *
 * Usage:
 *   $errors = collect_validation_errors([
 *       'title'       => validate_required($title, 'Title'),
 *       'location'    => validate_required($location, 'Location'),
 *       'event_date'  => validate_event_datetime_format($eventDate, 'Start date'),
 *   ]);
 *   if (!empty($errors)) {
 *       foreach ($errors as $msg) { set_flash('error', $msg); }
 *       set_validation_errors($errors);
 *       set_old_input([...]);
 *       redirect('dashboard');
 *   }
 *
 * @param array<string, true|string> $checks Keyed by field name; value is true or error message.
 * @return array<string, string> Errors only (field name => error message).
 */
function collect_validation_errors(array $checks): array
{
    $errors = [];
    foreach ($checks as $field => $result) {
        if ($result !== true) {
            $errors[$field] = $result;
        }
    }
    return $errors;
}

/**
 * Backwards-compatible wrapper (old boolean API).
 */
function validate_required_string(string $value): bool
{
    return validate_required($value, 'This field') === true;
}

/**
 * Backwards-compatible wrapper (old boolean API).
 */
function validate_event_datetime(string $value): bool
{
    return validate_event_datetime_format($value) === true;
}

/**
 * ---------------------------------------------------------------------------
 * Example usage (server-side)
 * ---------------------------------------------------------------------------
 *
 * 1) Registration
 *   $name = request_string($_POST, 'name');
 *   if (($err = validate_required($name, 'Name')) !== true) { set_flash('error',$err); redirect('register'); }
 *   if (($err = validate_email($email)) !== true) { set_flash('error',$err); redirect('register'); }
 *   if (($err = validate_password_strength($password)) !== true) { set_flash('error',$err); redirect('register'); }
 *   if (($err = validate_password_confirmation($password,$confirm)) !== true) { set_flash('error',$err); redirect('register'); }
 *
 * 2) Event creation
 *   if (($err = validate_max_length($title, 150, 'Title')) !== true) { ... }
 *   if (($err = validate_event_datetime_bounds($eventDate)) !== true) { ... }
 *
 * 3) Comment submission
 *   if (($err = validate_required($body, 'Feedback')) !== true) { ... }
 *   if (($err = validate_max_length($body, 1000, 'Feedback')) !== true) { ... }
 */

