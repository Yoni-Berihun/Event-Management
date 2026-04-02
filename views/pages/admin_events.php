<?php
// Admin panel: approve/reject events, view pending RSVPs across all events, feedback stats.
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/EventModel.php';
require_once __DIR__ . '/../../models/RsvpModel.php';
require_once __DIR__ . '/../../models/FeedbackModel.php';

require_admin();

$pendingEvents = event_get_pending_events();
$allEventIds   = array_map(fn($e) => (int) $e['id'], $pendingEvents);

// Pending RSVPs across ALL events.
$pdo           = get_pdo();
$pendingRsvps  = [];
try {
    $stmt = $pdo->query(
        "SELECT r.id AS rsvp_id, r.created_at, u.name AS attendee_name, u.email AS attendee_email,
                e.id AS event_id, e.title AS event_title, e.capacity,
                (SELECT COUNT(*) FROM rsvps r2 WHERE r2.event_id=e.id AND r2.status='approved') AS approved_count
         FROM rsvps r
         JOIN users u  ON u.id  = r.user_id
         JOIN events e ON e.id  = r.event_id
         WHERE r.status = 'pending'
         ORDER BY r.created_at ASC"
    );
    $pendingRsvps = $stmt->fetchAll();
} catch (Throwable) {}
?>

<div class="dashboard-header">
    <div>
        <h1 style="font-family:var(--font-display);font-size:1.8rem;">Admin Panel</h1>
        <p style="color:var(--color-text-muted);margin-top:var(--sp-1);">Review pending events and RSVP requests across all organizers.</p>
    </div>
</div>

<!-- Stat row -->
<div class="stat-row">
    <div class="stat-box">
        <div class="stat-label">Pending Events</div>
        <div class="stat-value" style="color:var(--color-warning)"><?= count($pendingEvents) ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Pending RSVPs</div>
        <div class="stat-value" style="color:var(--color-warning)"><?= count($pendingRsvps) ?></div>
    </div>
</div>

<!-- ── Pending RSVPs ────────────────────────────────────────────────────────── -->
<section class="dashboard-section">
    <h2>⏳ All Pending RSVPs</h2>
    <?php if (empty($pendingRsvps)): ?>
        <div class="empty-state" style="padding:var(--sp-8) 0">
            <div class="empty-icon">✅</div>
            <h3>No pending RSVPs</h3>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Attendee</th>
                        <th>Event</th>
                        <th>Seats</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRsvps as $r): ?>
                        <?php $isFull = (int)$r['approved_count'] >= (int)$r['capacity']; ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;font-size:.88rem"><?= e($r['attendee_name']) ?></div>
                                <div style="font-size:.78rem;color:var(--color-text-muted)"><?= e($r['attendee_email']) ?></div>
                            </td>
                            <td>
                                <a href="<?= e(url_for('event_detail', ['id' => (int)$r['event_id']])) ?>" style="color:var(--color-primary);font-weight:500;font-size:.88rem">
                                    <?= e($r['event_title']) ?>
                                </a>
                                <?php if ($isFull): ?><br><span class="badge badge-full">Full</span><?php endif; ?>
                            </td>
                            <td style="font-size:.85rem"><?= (int)$r['approved_count'] ?> / <?= (int)$r['capacity'] ?></td>
                            <td style="font-size:.78rem;color:var(--color-text-muted)"><?= e(date('d M, H:i', strtotime($r['created_at']))) ?></td>
                            <td>
                                <div class="rsvp-actions">
                                    <?php if (!$isFull): ?>
                                    <form action="<?= e(BASE_URL . 'actions/events/approve_rsvp.php') ?>" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                                        <input type="hidden" name="rsvp_id" value="<?= (int)$r['rsvp_id'] ?>">
                                        <button type="submit" class="button success sm">✓ Approve</button>
                                    </form>
                                    <?php endif; ?>
                                    <form action="<?= e(BASE_URL . 'actions/events/reject_rsvp.php') ?>" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                                        <input type="hidden" name="rsvp_id" value="<?= (int)$r['rsvp_id'] ?>">
                                        <button type="submit" class="button danger sm">✕ Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- ── Pending Events ───────────────────────────────────────────────────────── -->
<section class="dashboard-section">
    <h2>📋 Events Pending Approval</h2>
    <?php if (empty($pendingEvents)): ?>
        <div class="empty-state" style="padding:var(--sp-8) 0">
            <div class="empty-icon">🎉</div>
            <h3>No pending events</h3>
            <p>All submitted events have been reviewed.</p>
        </div>
    <?php else: ?>
        <div class="event-grid">
            <?php foreach ($pendingEvents as $event): ?>
                <?php
                $fb       = feedback_summary((int) $event['id']);
                $approved = rsvp_count_approved((int) $event['id']);
                ?>
                <article class="event-card">
                    <?php if (!empty($event['image_path'])): ?>
                        <img class="event-card-img" src="<?= e(BASE_URL . $event['image_path']) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <div class="event-card-img-placeholder" aria-hidden="true">📅</div>
                    <?php endif; ?>
                    <div class="event-card-body">
                        <div class="event-card-header">
                            <h2><?= e($event['title']) ?></h2>
                            <span class="badge badge-pending">Pending</span>
                        </div>
                        <div class="event-meta">
                            <span>📅 <?= e(date('d M Y, H:i', strtotime((string) $event['event_date']))) ?></span>
                            <span>📍 <?= e($event['location']) ?></span>
                            <?php if (!empty($event['category'])): ?>
                                <span>🏷️ <?= e($event['category']) ?></span>
                            <?php endif; ?>
                            <span>👥 <?= $approved ?> RSVPs</span>
                            <?php if ($fb): ?>
                                <span>⭐ <?= number_format($fb['avg_rating'], 1) ?> (<?= $fb['count'] ?>)</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($event['edit_reason'])): ?>
                            <p style="font-size:.8rem;color:var(--color-text-muted);margin-bottom:var(--sp-3);font-style:italic">
                                Edit note: <?= e($event['edit_reason']) ?>
                            </p>
                        <?php endif; ?>
                        <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;margin-top:auto;padding-top:var(--sp-4)">
                            <form action="<?= e(BASE_URL . 'actions/events/toggle_approval.php') ?>" method="post" style="flex:1">
                                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                                <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                <input type="hidden" name="is_verified" value="1">
                                <button type="submit" class="button success" style="width:100%">✓ Approve</button>
                            </form>
                            <a href="<?= e(url_for('event_detail', ['id' => (int) $event['id']])) ?>" class="button subtle" style="flex:1;text-align:center">View</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
