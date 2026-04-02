<?php
// User-related data access functions.

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Find a user by email address.
 */
function user_find_by_email(string $email): ?array
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    return $user !== false ? $user : null;
}

/**
 * Find a user by primary key ID.
 */
function user_find_by_id(int $id): ?array
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    return $user !== false ? $user : null;
}

/**
 * Create a new user (attendee or organizer).
 *
 * Returns the new user ID on success, or null on failure.
 */
function user_create(string $name, string $email, string $passwordHash, string $role = 'organizer'): ?int
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role, created_at)
         VALUES (:name, :email, :password_hash, :role, NOW())'
    );

    $ok = $stmt->execute([
        ':name'          => $name,
        ':email'         => $email,
        ':password_hash' => $passwordHash,
        ':role'          => $role,
    ]);

    if (!$ok) {
        return null;
    }

    return (int) $pdo->lastInsertId();
}

/**
 * Verify user credentials. Returns user row on success, null on failure.
 */
function user_authenticate(string $email, string $plainPassword): ?array
{
    $user = user_find_by_email($email);

    if (!$user) {
        return null;
    }

    if (!isset($user['password_hash']) || !password_verify($plainPassword, $user['password_hash'])) {
        return null;
    }

    return $user;
}

/** List all users with aggregate counts for admin panel. */
function user_get_all(?string $roleFilter = null): array
{
    $pdo = get_pdo();
    $where = $roleFilter ? " WHERE u.role = :role" : '';
    $stmt = $pdo->prepare(
        "SELECT u.id, u.name, u.email, u.role, u.created_at,
                (SELECT COUNT(*) FROM events e WHERE e.organizer_id = u.id) AS event_count,
                (SELECT COUNT(*) FROM rsvps r WHERE r.user_id = u.id) AS rsvp_count
         FROM users u{$where}
         ORDER BY u.created_at DESC"
    );
    $params = $roleFilter ? [':role' => $roleFilter] : [];
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Admin changes a user's role. */
function user_update_role(int $userId, string $newRole): bool
{
    $allowed = ['attendee', 'organizer', 'admin'];
    if (!in_array($newRole, $allowed, true)) {
        return false;
    }
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
    $stmt->execute([':role' => $newRole, ':id' => $userId]);
    return $stmt->rowCount() > 0;
}

/** Admin deletes a user and cascade-deletes their RSVPs, comments, feedback. */
function user_delete(int $userId): bool
{
    $pdo = get_pdo();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM feedback WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM comments WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM rsvps WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM events WHERE organizer_id = ?')->execute([$userId]);
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $pdo->commit();
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
}

/** Counts by role for admin stats. */
function user_count_by_role(): array
{
    $pdo = get_pdo();
    $stmt = $pdo->query("SELECT role, COUNT(*) AS c FROM users GROUP BY role");
    $result = ['admin' => 0, 'organizer' => 0, 'attendee' => 0, 'total' => 0];
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['role']] = (int) $row['c'];
        $result['total'] += (int) $row['c'];
    }
    return $result;
}

