<?php
/**
 * Create or update the primary organizer account (CLI only).
 *
 * Security: never commit passwords. Use environment variable or stdin.
 *
 * Usage (PowerShell):
 *   $env:ORGANIZER_PASSWORD = "your-secret"
 *   php scripts/setup_organizer_user.php
 *   Remove-Item Env:ORGANIZER_PASSWORD
 *
 * Or interactive (password prompted, not echoed):
 *   php scripts/setup_organizer_user.php --prompt
 *
 * Optional overrides:
 *   php scripts/setup_organizer_user.php --email=you@example.com --name="Your Name"
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/UserModel.php';

// Default organizer for this project (safe to commit; password is never stored here).
const DEFAULT_ORGANIZER_EMAIL = 'bengaminfish@gmail.com';
const DEFAULT_ORGANIZER_NAME  = 'Benjamin Fish';

$options = getopt('', ['email::', 'name::', 'prompt']);

$email = isset($options['email']) && trim((string) $options['email']) !== ''
    ? trim((string) $options['email'])
    : DEFAULT_ORGANIZER_EMAIL;

$name = isset($options['name']) && trim((string) $options['name']) !== ''
    ? trim((string) $options['name'])
    : DEFAULT_ORGANIZER_NAME;

$usePrompt = array_key_exists('prompt', $options);

$password = null;
if ($usePrompt) {
    echo 'Enter password (input hidden on Unix; on Windows it may echo): ';
    $password = rtrim((string) fgets(STDIN), "\r\n");
} else {
    $password = getenv('ORGANIZER_PASSWORD');
    if ($password === false || $password === '') {
        fwrite(STDERR, "Set ORGANIZER_PASSWORD in the environment, or run with --prompt\n");
        exit(1);
    }
}

if ($password === '') {
    fwrite(STDERR, "Password cannot be empty.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "Could not hash password.\n");
    exit(1);
}

$pdo = get_pdo();
$existing = user_find_by_email($email);

if ($existing) {
    $stmt = $pdo->prepare(
        'UPDATE users SET name = :name, password_hash = :password_hash, role = :role WHERE id = :id'
    );
    $stmt->execute([
        ':name'          => $name,
        ':password_hash' => $hash,
        ':role'          => 'organizer',
        ':id'            => (int) $existing['id'],
    ]);
    echo "Updated existing user: {$email} (role=organizer)\n";
} else {
    $id = user_create($name, $email, $hash, 'organizer');
    if ($id === null) {
        fwrite(STDERR, "Could not create user.\n");
        exit(1);
    }
    echo "Created organizer: {$email} (id={$id})\n";
}

echo "Done. You can log in via the web app.\n";
