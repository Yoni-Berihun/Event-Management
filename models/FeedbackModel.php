<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function feedback_submit(int $eventId, int $userId, int $rating, string $comment): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO feedback (event_id, user_id, rating, comment)
         VALUES (:event_id, :user_id, :rating, :comment)'
    );
    return $stmt->execute([
        ':event_id' => $eventId,
        ':user_id'  => $userId,
        ':rating'   => $rating,
        ':comment'  => $comment !== '' ? $comment : null,
    ]);
}

function feedback_has_submitted(int $eventId, int $userId): bool
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT id FROM feedback WHERE event_id=? AND user_id=? LIMIT 1');
    $stmt->execute([$eventId, $userId]);
    return $stmt->fetch() !== false;
}

function feedback_get_for_event(int $eventId): array
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT f.rating, f.comment, f.created_at, u.name
         FROM feedback f
         JOIN users u ON u.id = f.user_id
         WHERE f.event_id = :event_id
         ORDER BY f.created_at DESC'
    );
    $stmt->execute([':event_id' => $eventId]);
    return $stmt->fetchAll();
}

/** Returns ['avg_rating' => float, 'count' => int] or null when no feedback yet. */
function feedback_summary(int $eventId): ?array
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS cnt
         FROM feedback WHERE event_id = :event_id'
    );
    $stmt->execute([':event_id' => $eventId]);
    $row = $stmt->fetch();
    if (!$row || (int) $row['cnt'] === 0) {
        return null;
    }
    return ['avg_rating' => (float) $row['avg_rating'], 'count' => (int) $row['cnt']];
}

/** Bulk summary for a list of event IDs (used on organiser dashboard). */
function feedback_summaries_for_events(array $eventIds): array
{
    if (empty($eventIds)) {
        return [];
    }
    $pdo         = get_pdo();
    $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
    $stmt         = $pdo->prepare(
        "SELECT event_id, ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS cnt
         FROM feedback WHERE event_id IN ({$placeholders})
         GROUP BY event_id"
    );
    $stmt->execute(array_values($eventIds));
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int) $row['event_id']] = [
            'avg_rating' => (float) $row['avg_rating'],
            'count'      => (int) $row['cnt'],
        ];
    }
    return $result;
}
