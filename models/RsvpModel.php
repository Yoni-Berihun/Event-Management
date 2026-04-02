<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/** Count only *approved* RSVPs (used for capacity enforcement). */
function rsvp_count_approved(int $eventId): int
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM rsvps WHERE event_id=? AND status='approved'");
    $stmt->execute([$eventId]);
    $row = $stmt->fetch();
    return $row ? (int) $row['c'] : 0;
}

/** BC shim: total RSVPs regardless of status (used in a few view calls). */
function rsvp_count_for_event(int $eventId): int
{
    return rsvp_count_approved($eventId);
}

function rsvp_remaining_seats(int $eventId, int $capacity): int
{
    $remaining = $capacity - rsvp_count_approved($eventId);
    return $remaining > 0 ? $remaining : 0;
}

function rsvp_user_has_rsvped(int $eventId, int $userId): bool
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare("SELECT id FROM rsvps WHERE event_id=? AND user_id=? LIMIT 1");
    $stmt->execute([$eventId, $userId]);
    return $stmt->fetch() !== false;
}

function rsvp_user_status(int $eventId, int $userId): ?string
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare("SELECT status FROM rsvps WHERE event_id=? AND user_id=? LIMIT 1");
    $stmt->execute([$eventId, $userId]);
    $row = $stmt->fetch();
    return $row ? (string) $row['status'] : null;
}

/** Returns true only if user has an approved RSVP (for feedback gating). */
function rsvp_user_is_approved_attendee(int $eventId, int $userId): bool
{
    return rsvp_user_status($eventId, $userId) === 'approved';
}

function rsvp_can_rsvp(int $eventId, int $userId, int $capacity): bool
{
    if (rsvp_user_has_rsvped($eventId, $userId)) {
        return false;
    }
    return rsvp_remaining_seats($eventId, $capacity) > 0;
}

/**
 * Check for time overlaps between a target event and the user's pending/approved RSVPs.
 * Returns array of conflicting event rows (empty = no conflict).
 */
function rsvp_detect_conflicts(int $eventId, int $userId): array
{
    $pdo = get_pdo();
    // Fetch target event time span.
    $evStmt = $pdo->prepare('SELECT event_date, event_end FROM events WHERE id=? LIMIT 1');
    $evStmt->execute([$eventId]);
    $target = $evStmt->fetch();
    if (!$target || empty($target['event_end'])) {
        return [];
    }
    $start = $target['event_date'];
    $end   = $target['event_end'];

    // Find user's other RSVPs whose event overlaps [start, end).
    $stmt = $pdo->prepare(
        "SELECT e.id, e.title, e.event_date, e.event_end
         FROM rsvps r
         JOIN events e ON e.id = r.event_id
         WHERE r.user_id = :uid
           AND r.event_id != :eid
           AND r.status IN ('pending','approved')
           AND e.event_end IS NOT NULL
           AND e.event_date < :end
           AND e.event_end  > :start"
    );
    $stmt->execute([':uid' => $userId, ':eid' => $eventId, ':end' => $end, ':start' => $start]);
    return $stmt->fetchAll();
}

/**
 * Get all RSVPs for an event joined with user info; optionally filter by status.
 */
function rsvp_get_for_event(int $eventId, ?string $status = null): array
{
    $pdo   = get_pdo();
    $where = $status !== null ? " AND r.status = :status" : '';
    $stmt  = $pdo->prepare(
        "SELECT r.id, r.status, r.created_at, r.approved_at,
                u.id AS user_id, u.name, u.email
         FROM rsvps r
         JOIN users u ON u.id = r.user_id
         WHERE r.event_id = :event_id{$where}
         ORDER BY r.created_at ASC"
    );
    $params = [':event_id' => $eventId];
    if ($status !== null) {
        $params[':status'] = $status;
    }
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Insert a new RSVP in pending state.
 */
function rsvp_add(int $eventId, int $userId): bool
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare(
        "INSERT INTO rsvps (event_id, user_id, status, created_at)
         VALUES (:event_id, :user_id, 'pending', NOW())"
    );
    return $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
}

/**
 * Approve an RSVP inside a transaction with row lock to prevent overbooking.
 * Returns true on success, false if at capacity or already processed.
 */
function rsvp_approve(int $rsvpId, int $approverId): bool
{
    $pdo = get_pdo();
    $pdo->beginTransaction();
    try {
        // Lock the RSVP row.
        $rStmt = $pdo->prepare('SELECT r.*, e.capacity FROM rsvps r JOIN events e ON e.id=r.event_id WHERE r.id=? FOR UPDATE');
        $rStmt->execute([$rsvpId]);
        $rsvp = $rStmt->fetch();

        if (!$rsvp || $rsvp['status'] !== 'pending') {
            $pdo->rollBack();
            return false;
        }

        $approved = rsvp_count_approved((int) $rsvp['event_id']);
        if ($approved >= (int) $rsvp['capacity']) {
            $pdo->rollBack();
            return false; // full
        }

        $pdo->prepare(
            "UPDATE rsvps SET status='approved', approved_at=NOW(), approved_by=:by WHERE id=:id"
        )->execute([':by' => $approverId, ':id' => $rsvpId]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Reject a pending RSVP.
 */
function rsvp_reject(int $rsvpId, int $approverId): bool
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare(
        "UPDATE rsvps SET status='rejected', approved_at=NOW(), approved_by=:by WHERE id=:id AND status='pending'"
    );
    $stmt->execute([':by' => $approverId, ':id' => $rsvpId]);
    return $stmt->rowCount() > 0;
}

/** Pending RSVPs across all events owned by an organiser. */
function rsvp_get_pending_for_organizer(int $organizerId): array
{
    $pdo  = get_pdo();
    $stmt = $pdo->prepare(
        "SELECT r.id AS rsvp_id, r.created_at, r.status,
                u.name AS attendee_name, u.email AS attendee_email,
                e.id AS event_id, e.title AS event_title, e.capacity,
                (SELECT COUNT(*) FROM rsvps r2 WHERE r2.event_id=e.id AND r2.status='approved') AS approved_count
         FROM rsvps r
         JOIN users u  ON u.id  = r.user_id
         JOIN events e ON e.id  = r.event_id
         WHERE e.organizer_id = :org AND r.status = 'pending'
         ORDER BY r.created_at ASC"
    );
    $stmt->execute([':org' => $organizerId]);
    return $stmt->fetchAll();
}
