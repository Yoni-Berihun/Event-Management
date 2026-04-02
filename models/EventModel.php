<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function event_get_approved_events(int $page = 1, int $perPage = 12): array
{
    $pdo    = get_pdo();
    $offset = ($page - 1) * $perPage;
    $stmt   = $pdo->prepare(
        'SELECT * FROM events WHERE is_verified=1
         ORDER BY event_date ASC, created_at DESC LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit',  $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function event_count_approved(): int
{
    $pdo  = get_pdo();
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM events WHERE is_verified=1");
    $row  = $stmt->fetch();
    return $row ? (int) $row['c'] : 0;
}

function event_get_pending_events(): array
{
    $pdo  = get_pdo();
    $stmt = $pdo->query('SELECT * FROM events WHERE is_verified=0 ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function event_get_by_id(int $id): ?array
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row !== false ? $row : null;
}

function event_get_by_organizer(int $organizerId): array
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare(
        "SELECT e.*,
                (SELECT COUNT(*) FROM rsvps r WHERE r.event_id=e.id AND r.status='approved') AS rsvp_count
         FROM events e WHERE e.organizer_id=:org ORDER BY e.created_at DESC"
    );
    $stmt->execute([':org' => $organizerId]);
    return $stmt->fetchAll();
}

/**
 * Detect if a new event [start, end] overlaps any existing event of the same organiser.
 * Excludes $excludeId (for edit scenarios).
 */
function event_detect_organizer_overlap(
    int $organizerId,
    string $start,
    string $end,
    ?int $excludeId = null
): array {
    $pdo   = get_pdo();
    $excl  = $excludeId !== null ? ' AND e.id != :excl' : '';
    $stmt  = $pdo->prepare(
        "SELECT e.id, e.title, e.event_date, e.event_end
         FROM events e
         WHERE e.organizer_id = :org
           AND e.event_end IS NOT NULL
           AND e.event_date < :end
           AND e.event_end  > :start
           {$excl}"
    );
    $params = [':org' => $organizerId, ':start' => $start, ':end' => $end];
    if ($excludeId !== null) {
        $params[':excl'] = $excludeId;
    }
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function event_create(
    int $organizerId,
    string $title,
    string $description,
    ?string $imagePath,
    string $location,
    string $category,
    string $eventDate,
    string $eventEnd,
    int $capacity
): ?int {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO events (organizer_id,title,description,image_path,location,category,event_date,event_end,capacity,is_verified,created_at)
         VALUES (:org,:title,:desc,:img,:loc,:cat,:date,:end,:cap,0,NOW())'
    );
    $ok = $stmt->execute([
        ':org'   => $organizerId,
        ':title' => $title,
        ':desc'  => $description,
        ':img'   => $imagePath,
        ':loc'   => $location,
        ':cat'   => $category !== '' ? $category : 'General',
        ':date'  => $eventDate,
        ':end'   => $eventEnd,
        ':cap'   => $capacity,
    ]);
    return $ok ? (int) $pdo->lastInsertId() : null;
}

function event_update_basic(
    int $eventId,
    string $title,
    string $description,
    ?string $imagePath,
    string $location,
    string $category,
    string $eventDate,
    string $eventEnd,
    int $capacity
): bool {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare(
        'UPDATE events
         SET title=:title, description=:desc, image_path=:img,
             location=:loc, category=:cat, event_date=:date, event_end=:end, capacity=:cap
         WHERE id=:id'
    );
    return $stmt->execute([
        ':id'    => $eventId,
        ':title' => $title,
        ':desc'  => $description,
        ':img'   => $imagePath,
        ':loc'   => $location,
        ':cat'   => $category !== '' ? $category : 'General',
        ':date'  => $eventDate,
        ':end'   => $eventEnd,
        ':cap'   => $capacity,
    ]);
}

/**
 * Get all distinct categories used across events (for filter dropdowns).
 */
function event_get_categories(): array
{
    $pdo  = get_pdo();
    $stmt = $pdo->query(
        "SELECT DISTINCT category FROM events
         WHERE category IS NOT NULL AND category != ''
         ORDER BY category ASC"
    );
    return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: ['General'];
}

function event_delete(int $eventId, int $organizerId): bool
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM events WHERE id=:id AND organizer_id=:org');
    $stmt->execute([':id' => $eventId, ':org' => $organizerId]);
    return $stmt->rowCount() > 0;
}

function event_set_verified(int $eventId, bool $isVerified): bool
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('UPDATE events SET is_verified=:v WHERE id=:id');
    return $stmt->execute([':id' => $eventId, ':v' => $isVerified ? 1 : 0]);
}
