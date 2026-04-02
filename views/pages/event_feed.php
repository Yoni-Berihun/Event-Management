<?php
// Event feed with pagination, capacity badges, and verified filter.
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../models/EventModel.php';
require_once __DIR__ . '/../../models/RsvpModel.php';

require_login();

$perPage = 12;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$events  = event_get_approved_events($page, $perPage);
$total   = event_count_approved();
$pages   = (int) ceil($total / $perPage);
?>

<section class="page-header">
    <h1>Discover Events</h1>
    <p>Browse approved events and RSVP to reserve your spot.</p>
</section>

<?php if (empty($events)): ?>
    <div class="empty-state">
        <div class="empty-icon">🗓️</div>
        <h3>No events yet</h3>
        <p>Check back soon — new events are added regularly.</p>
    </div>
<?php else: ?>
    <section class="event-grid" aria-label="Event list">
        <?php foreach ($events as $event): ?>
            <?php
            $approved   = rsvp_count_approved((int) $event['id']);
            $capacity   = (int) $event['capacity'];
            $remaining  = max(0, $capacity - $approved);
            $isFull     = $remaining === 0;
            $hasEnded   = has_datetime_passed((string) ($event['event_end'] ?? $event['event_date'] ?? ''));
            $startFmt   = date('D j M, g:i A', strtotime((string) $event['event_date']));
            ?>
            <article class="event-card">
                <?php if (!empty($event['image_path'])): ?>
                    <img class="event-card-img"
                         src="<?= e(BASE_URL . $event['image_path']) ?>"
                         alt="<?= e($event['title']) ?> image"
                         loading="lazy">
                <?php else: ?>
                    <div class="event-card-img-placeholder" aria-hidden="true">📅</div>
                <?php endif; ?>

                <div class="event-card-body">
                    <div class="event-card-header">
                        <h2><?= e($event['title']) ?></h2>
                        <?php if ($hasEnded): ?>
                            <span class="badge badge-ended">Ended</span>
                        <?php elseif ($isFull): ?>
                            <span class="badge badge-full">Full</span>
                        <?php else: ?>
                            <span class="badge badge-verified">Open</span>
                        <?php endif; ?>
                    </div>

                    <div class="event-meta">
                        <span>📅 <?= e($startFmt) ?></span>
                        <span>📍 <?= e($event['location']) ?></span>
                        <?php if (!$isFull && !$hasEnded): ?>
                            <span>🎟️ <?= $remaining ?> seat<?= $remaining === 1 ? '' : 's' ?> left</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!$hasEnded && !$isFull): ?>
                        <?php $pct = min(100, (int) round($approved / max(1, $capacity) * 100)); ?>
                        <div class="seat-bar-wrap" title="<?= $pct ?>% filled" aria-label="<?= $pct ?>% capacity filled">
                            <div class="seat-bar <?= $pct >= 100 ? 'full' : '' ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                    <?php endif; ?>

                    <div class="event-card-footer">
                        <a class="button subtle" href="<?= e(url_for('event_detail', ['id' => (int) $event['id']])) ?>">
                            View details →
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Page navigation">
            <?php if ($page > 1): ?>
                <a href="<?= e(url_for('event_feed', ['page' => $page - 1])) ?>" aria-label="Previous page">‹ Prev</a>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="current" aria-current="page"><?= $p ?></span>
                <?php elseif (abs($p - $page) <= 2 || $p === 1 || $p === $pages): ?>
                    <a href="<?= e(url_for('event_feed', ['page' => $p])) ?>"><?= $p ?></a>
                <?php elseif (abs($p - $page) === 3): ?>
                    <span>…</span>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $pages): ?>
                <a href="<?= e(url_for('event_feed', ['page' => $page + 1])) ?>" aria-label="Next page">Next ›</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
