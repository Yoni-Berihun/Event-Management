<?php
// Persistent remember-me token data access functions.

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Insert a remember-me token record.
 */
function remember_token_create(int $userId, string $selector, string $validatorHash, string $expiresAt): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at, created_at)
         VALUES (:user_id, :selector, :validator_hash, :expires_at, NOW())'
    );

    return $stmt->execute([
        ':user_id' => $userId,
        ':selector' => $selector,
        ':validator_hash' => $validatorHash,
        ':expires_at' => $expiresAt,
    ]);
}

/**
 * Find one remember-me token by selector.
 */
function remember_token_find_by_selector(string $selector): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT *
         FROM remember_tokens
         WHERE selector = :selector
         LIMIT 1'
    );
    $stmt->execute([':selector' => $selector]);
    $row = $stmt->fetch();

    return $row !== false ? $row : null;
}

/**
 * Delete a remember-me token by selector.
 */
function remember_token_delete_by_selector(string $selector): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE selector = :selector');
    $stmt->execute([':selector' => $selector]);

    return $stmt->rowCount() > 0;
}

/**
 * Delete all remember-me tokens for a user.
 */
function remember_token_delete_for_user(int $userId): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $userId]);

    return true;
}

/**
 * Remove expired remember-me tokens.
 */
function remember_token_delete_expired(): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE expires_at <= NOW()');
    $stmt->execute();

    return true;
}

