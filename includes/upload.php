<?php
// Centralized image upload helper — single place for file type, size, and storage logic.

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/validation.php';

/** Maximum image upload size in bytes (5 MB). */
const UPLOAD_MAX_IMAGE_BYTES = 5 * 1024 * 1024;

/** Allowed MIME types for event images. */
const UPLOAD_ALLOWED_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
];

/**
 * Handle an image upload from $_FILES.
 *
 * @param string $fieldName  The $_FILES key (default 'image').
 * @param string $uploadDir  Absolute path to the uploads directory.
 *
 * @return string|null|false
 *   - string: relative path to stored file (success)
 *   - null:   no file was uploaded (field empty/ not sent)
 *   - false:  validation or storage failed (flash error already set)
 */
function handle_image_upload(string $fieldName = 'image', ?string $uploadDir = null): string|null|false
{
    // No file uploaded — that's fine (image is usually optional).
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // Validate the file.
    $err = validate_file_upload($file, UPLOAD_MAX_IMAGE_BYTES, UPLOAD_ALLOWED_MIMES, 'Image');
    if ($err !== true) {
        set_flash('error', $err);
        return false;
    }

    // Resolve upload directory.
    if ($uploadDir === null) {
        $uploadDir = __DIR__ . '/../public/uploads';
    }
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        set_flash('error', 'Could not prepare upload directory.');
        return false;
    }

    // Determine extension from MIME.
    $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    $ext  = UPLOAD_ALLOWED_MIMES[$mime] ?? 'jpg';

    // Generate a random, collision-resistant filename.
    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newName;

    if (!move_uploaded_file((string) $file['tmp_name'], $destPath)) {
        set_flash('error', 'Could not store uploaded image.');
        return false;
    }

    return 'uploads/' . $newName;
}

/**
 * Delete an uploaded image file if it exists.
 */
function delete_uploaded_image(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }
    $absPath = __DIR__ . '/../public/' . $relativePath;
    if (is_file($absPath)) {
        @unlink($absPath);
    }
}
