<?php
// Event detail: full info, RSVP (with conflict modal), feedback form/list.
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/policy.php';
require_once __DIR__ . '/../../models/EventModel.php';
require_once __DIR__ . '/../../models/RsvpModel.php';
require_once __DIR__ . '/../../models/CommentModel.php';
require_once __DIR__ . '/../../models/FeedbackModel.php';

require_login();

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event   = $eventId > 0 ? event_get_by_id($eventId) : null;

if (!$event) {
    http_response_code(404); ?>
    <section class="not-found">
        <h1>Event Not Found</h1>
        <p>The event you are looking for does not exist.</p>
        <a class="button" href="<?= e(url_for('event_feed')) ?>">Back to events</a>
    </section>
    <?php return;
}

$userId           = current_user_id() ?? 0;
$isAdmin          = policy_is_admin();
$isOrganizerOwner = policy_is_event_owner($userId, $event);

if (!can_view_event($userId, $event, null, true)) {
    http_response_code(403); ?>
    <section class="not-found">
        <h1>Event Not Available</h1>
        <p>This event is pending approval and is only visible to the organiser or admin.</p>
        <a class="button" href="<?= e(url_for('event_feed')) ?>">Back to events</a>
    </section>
    <?php return;
}

$endDateTime      = $event['event_end'] ?? $event['event_date'] ?? '';
$hasEnded         = has_datetime_passed((string) $endDateTime);
$capacity         = (int) $event['capacity'];
$approvedCount    = rsvp_count_approved($eventId);
$remainingSeats   = max(0, $capacity - $approvedCount);
$isFull           = $remainingSeats === 0;
$userRsvpStatus   = rsvp_user_status($eventId, $userId);
$isApprovedAttend = $userRsvpStatus === 'approved';
$comments         = comment_get_by_event($eventId);
$feedback         = feedback_get_for_event($eventId);
$feedbackSummary  = feedback_summary($eventId);
$canSubmitFeedback = $hasEnded && $isApprovedAttend && !feedback_has_submitted($eventId, $userId);

// Pull conflict data if redirect came from conflict detection.
$conflictData     = $_SESSION['rsvp_conflicts'] ?? null;
$showConflict     = isset($_GET['show_conflict']) && $conflictData && (int)($conflictData['event_id'] ?? 0) === $eventId;
if ($showConflict) {
    unset($_SESSION['rsvp_conflicts']);
}

$startFmt = date('D, d M Y \a\t g:i A', strtotime((string) $event['event_date']));
$endFmt   = !empty($event['event_end']) ? date('g:i A', strtotime((string) $event['event_end'])) : '';
$pct      = min(100, $capacity > 0 ? (int) round($approvedCount / $capacity * 100) : 0);
?>

<!-- ── Conflict Modal ──────────────────────────────────────────────────────── -->
<?php if ($showConflict && !empty($conflictData['conflicts'])): ?>
<div class="modal-backdrop" id="conflictModal" role="dialog" aria-modal="true" aria-labelledby="conflictTitle">
    <div class="modal">
        <h2 id="conflictTitle">⚠️ Schedule Conflict Detected</h2>
        <p style="margin-bottom:var(--sp-4);font-size:.9rem;color:var(--color-text-muted);">
            This event overlaps with your existing RSVPs. You can still proceed, but you may not be able to attend all events.
        </p>
        <ul class="modal-conflict-list" role="list">
            <?php foreach ($conflictData['conflicts'] as $c): ?>
                <li>
                    <strong><?= e($c['title']) ?></strong><br>
                    <small><?= e(date('D d M, g:i A', strtotime($c['event_date']))) ?>
                        <?php if (!empty($c['event_end'])): ?> – <?= e(date('g:i A', strtotime($c['event_end']))) ?><?php endif; ?>
                    </small>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="modal-actions">
            <a class="button subtle" href="<?= e(url_for('event_detail', ['id' => $eventId])) ?>">Cancel RSVP</a>
            <form action="<?= e(BASE_URL . 'actions/events/rsvp.php') ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                <input type="hidden" name="event_id" value="<?= $eventId ?>">
                <input type="hidden" name="conflict_confirmed" value="1">
                <button type="submit" class="button primary">Continue Anyway</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Event Header ────────────────────────────────────────────────────────── -->
<div class="event-detail-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--sp-4);flex-wrap:wrap;">
        <div>
            <h1><?= e($event['title']) ?></h1>
            <p class="event-meta" style="margin-top:var(--sp-3);">
            <span>📅 <?= e($startFmt) ?><?= $endFmt ? ' – ' . e($endFmt) : '' ?></span>
            <span>📍 <?= e($event['location']) ?></span>
            <?php if (!empty($event['category'])): ?>
                <span>🏷️ <?= e($event['category']) ?></span>
            <?php endif; ?>
        </p>
        </div>
        <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;align-items:center;">
            <?php if ($hasEnded): ?>
                <span class="badge badge-ended">Event Ended</span>
            <?php elseif ($isFull): ?>
                <span class="badge badge-full">Full</span>
            <?php else: ?>
                <span class="badge badge-verified">Open</span>
            <?php endif; ?>
            <?php if (!empty($event['is_verified'])): ?>
                <span class="badge badge-approved">Verified</span>
            <?php else: ?>
                <span class="badge badge-pending">Pending Approval</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Body Grid ──────────────────────────────────────────────────────────── -->
<div class="event-detail-body">

    <!-- Main column -->
    <div class="event-detail-main">
        <?php if (!empty($event['image_path'])): ?>
            <img class="event-detail-img"
                 src="<?= e(BASE_URL . $event['image_path']) ?>"
                 alt="<?= e($event['title']) ?> banner">
        <?php endif; ?>

        <div class="card">
            <h2 style="font-size:1.05rem;margin-bottom:var(--sp-4);">About this event</h2>
            <p style="color:var(--color-text-muted);line-height:1.8;"><?= nl2br(e($event['description'])) ?></p>
        </div>

        <!-- Comments section -->
        <?php if (!empty($comments)): ?>
        <div class="card" style="margin-top:var(--sp-4);">
            <h2 style="font-size:1.05rem;margin-bottom:var(--sp-4);">Comments (<?= count($comments) ?>)</h2>
            <?php foreach ($comments as $c): ?>
                <div style="border-bottom:1px solid var(--color-border);padding:var(--sp-3) 0;font-size:.88rem;">
                    <strong><?= e($c['name'] ?? 'Anonymous') ?></strong>
                    <span style="color:var(--color-text-muted);font-size:.78rem;margin-left:var(--sp-2);"><?= e(date('d M Y', strtotime($c['created_at']))) ?></span>
                    <p style="margin-top:var(--sp-2);"><?= nl2br(e($c['body'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Feedback list -->
        <?php if (!empty($feedback)): ?>
        <div class="card" style="margin-top:var(--sp-4);">
            <h2 style="font-size:1.05rem;margin-bottom:var(--sp-2);">
                Attendee Feedback
                <?php if ($feedbackSummary): ?>
                    <span style="font-size:.85rem;font-weight:400;color:var(--color-text-muted);margin-left:var(--sp-3);">
                        Avg: <span class="stars"><?= str_repeat('★', (int) round($feedbackSummary['avg_rating'])) ?></span>
                        <?= number_format($feedbackSummary['avg_rating'], 1) ?> / 5
                        (<?= $feedbackSummary['count'] ?> review<?= $feedbackSummary['count'] !== 1 ? 's' : '' ?>)
                    </span>
                <?php endif; ?>
            </h2>
            <?php foreach ($feedback as $fb): ?>
                <div class="feedback-item">
                    <div class="feedback-item-header">
                        <span class="reviewer"><?= e($fb['name']) ?></span>
                        <span class="stars"><?= str_repeat('★', (int) $fb['rating']) ?><span class="stars-empty"><?= str_repeat('★', 5 - (int) $fb['rating']) ?></span></span>
                        <span class="review-date"><?= e(date('d M Y', strtotime($fb['created_at']))) ?></span>
                    </div>
                    <?php if (!empty($fb['comment'])): ?>
                        <p style="font-size:.88rem;color:var(--color-text-muted);"><?= nl2br(e($fb['comment'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <aside class="event-sidebar">

        <!-- RSVP Box -->
        <div class="card rsvp-box">
            <h3>Reserve Your Spot</h3>
            <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:var(--sp-2);">
                <?= $approvedCount ?> / <?= $capacity ?> seats filled
            </div>
            <div class="seat-bar-wrap" aria-label="<?= $pct ?>% capacity filled">
                <div class="seat-bar <?= $pct >= 100 ? 'full' : '' ?>" style="width:<?= $pct ?>%"></div>
            </div>

            <?php if ($hasEnded): ?>
                <p style="color:var(--color-text-muted);font-size:.88rem;">This event has ended.</p>

            <?php elseif (!(int)($event['is_verified'] ?? 0)): ?>
                <!-- Event not yet approved by admin — no RSVP allowed -->
                <div class="flash flash-warning" style="margin:0;font-size:.88rem;">
                    🔒 RSVP will be available once this event is approved by an admin.
                </div>

            <?php elseif ($userRsvpStatus === 'approved'): ?>
                <div class="flash flash-success" style="margin:0;">✓ Your RSVP is confirmed!</div>

            <?php elseif ($userRsvpStatus === 'pending'): ?>
                <div class="flash flash-warning" style="margin:0;">⏳ Your RSVP is pending organiser approval.</div>

            <?php elseif ($userRsvpStatus === 'rejected'): ?>
                <div class="flash flash-error" style="margin:0;">✕ Your RSVP was not approved for this event.</div>

            <?php elseif ($isFull): ?>
                <div class="flash flash-warning" style="margin:0;">This event is full.</div>

            <?php else: ?>
                <form action="<?= e(BASE_URL . 'actions/events/rsvp.php') ?>" method="post" id="rsvpForm">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <button type="submit" class="button primary" style="width:100%;margin-top:var(--sp-3);" id="rsvpBtn">
                        RSVP Now
                    </button>
                    <p style="font-size:.75rem;color:var(--color-text-light);margin-top:var(--sp-2);text-align:center;">
                        Confirmation is subject to organiser approval.
                    </p>
                </form>
            <?php endif; ?>
        </div>

        <!-- Feedback submission box -->
        <?php if ($hasEnded && $isApprovedAttend): ?>
        <div class="card feedback-box">
            <h3>Leave Feedback</h3>
            <?php if (feedback_has_submitted($eventId, $userId)): ?>
                <div class="flash flash-info" style="margin:0;font-size:.85rem;">You already submitted feedback. Thank you!</div>
            <?php else: ?>
                <form action="<?= e(BASE_URL . 'actions/events/submit_feedback.php') ?>" method="post" id="feedbackForm">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <div style="margin-bottom:var(--sp-4);">
                        <label class="form-label">Your Rating <span style="color:var(--color-danger)">*</span></label>
                        <div class="star-rating" role="group" aria-label="Star rating">
                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                <input type="radio" id="star<?= $s ?>" name="rating" value="<?= $s ?>" required>
                                <label for="star<?= $s ?>" aria-label="<?= $s ?> star<?= $s > 1 ? 's' : '' ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="feedbackComment">Comment <span style="font-weight:400;color:var(--color-text-light)">(optional)</span></label>
                        <textarea id="feedbackComment" name="comment" class="form-control" rows="3"
                                  maxlength="1000" placeholder="Share your experience…"
                                  oninput="document.getElementById('fbCount').textContent=this.value.length"></textarea>
                        <div class="char-count"><span id="fbCount">0</span> / 1000</div>
                    </div>
                    <button type="submit" class="button primary" style="width:100%;" id="feedbackBtn">
                        Submit Feedback
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php elseif ($hasEnded && !$isApprovedAttend && !$isAdmin && !$isOrganizerOwner): ?>
        <div class="card" style="font-size:.88rem;color:var(--color-text-muted);">
            <h3 style="font-size:1rem;margin-bottom:var(--sp-2);">Feedback</h3>
            <p>Only confirmed attendees can submit feedback after the event ends.</p>
        </div>
        <?php elseif (!$hasEnded): ?>
        <div class="card" style="font-size:.88rem;color:var(--color-text-muted);">
            <h3 style="font-size:1rem;margin-bottom:var(--sp-2);">Feedback</h3>
            <p>Feedback opens after the event ends.</p>
        </div>
        <?php endif; ?>

        <!-- Edit/Admin controls -->
        <?php if ($isAdmin || $isOrganizerOwner): ?>
        <div class="card" style="display:flex;flex-direction:column;gap:var(--sp-3);">
            <h3 style="font-size:.9rem;color:var(--color-text-muted);">Organiser Actions</h3>
            <?php if ($isAdmin): ?>
                <form action="<?= e(BASE_URL . 'actions/events/toggle_approval.php') ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <input type="hidden" name="is_verified" value="<?= empty($event['is_verified']) ? '1' : '0' ?>">
                    <button type="submit" class="button sm <?= empty($event['is_verified']) ? 'success' : 'subtle' ?>" style="width:100%;">
                        <?= empty($event['is_verified']) ? '✓ Approve Event' : 'Revoke Approval' ?>
                    </button>
                </form>
            <?php endif; ?>
            <a href="<?= e(url_for('dashboard')) ?>" class="button subtle sm" style="text-align:center;">← Back to Dashboard</a>
        </div>
        <?php endif; ?>

    </aside>
</div>

<script>
// Disable submit button after click to prevent double-submit.
['rsvpForm','feedbackForm'].forEach(function(id) {
    var f = document.getElementById(id);
    if (!f) return;
    f.addEventListener('submit', function() {
        var btn = f.querySelector('[type="submit"]');
        if (btn) { btn.disabled = true; btn.textContent = 'Submitting…'; }
    });
});
</script>
