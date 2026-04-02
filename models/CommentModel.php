<?php
// Comment-related data access functions.

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Get all comments (and optional organizer replies) for a given event.
 *
 * This assumes a simple schema where comments have:
 * - id, event_id, user_id, body, parent_comment_id (nullable), created_at
 */
function comment_get_by_event(int $eventId): array
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'SELECT c.*, u.name
         FROM comments c
         JOIN users u ON u.id = c.user_id
         WHERE c.event_id = :event_id
         ORDER BY c.created_at ASC'
    );

    $stmt->execute([':event_id' => $eventId]);

    return $stmt->fetchAll();
}

/**
 * Get one comment by id.
 */
function comment_get_by_id(int $commentId): ?array
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'SELECT *
         FROM comments
         WHERE id = :id
         LIMIT 1'
    );

    $stmt->execute([':id' => $commentId]);
    $comment = $stmt->fetch();

    return $comment !== false ? $comment : null;
}

/**
 * Add a top-level attendee comment for an event.
 */
function comment_add(int $eventId, int $userId, string $body): bool
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'INSERT INTO comments (event_id, user_id, body, parent_comment_id, created_at)
         VALUES (:event_id, :user_id, :body, NULL, NOW())'
    );

    return $stmt->execute([
        ':event_id' => $eventId,
        ':user_id'  => $userId,
        ':body'     => $body,
    ]);
}

/**
 * Add a reply to an existing comment (typically by the organizer).
 */
function comment_add_reply(int $eventId, int $userId, int $parentCommentId, string $body): bool
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'INSERT INTO comments (event_id, user_id, body, parent_comment_id, created_at)
         VALUES (:event_id, :user_id, :body, :parent_comment_id, NOW())'
    );

    return $stmt->execute([
        ':event_id'         => $eventId,
        ':user_id'          => $userId,
        ':body'             => $body,
        ':parent_comment_id'=> $parentCommentId,
    ]);
}

