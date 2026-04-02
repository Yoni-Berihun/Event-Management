<?php
// Reusable event card partial for the event feed.

declare(strict_types=1);

// Expect an $event array when this partial is included.
?>
<article class="event-card">
    <header>
        <h2><?= e($event['title'] ?? 'Event Title') ?></h2>
        <?php if (!empty($event['is_verified'])): ?>
            <span class="badge-verified">Verified</span>
        <?php endif; ?>
    </header>
    <p class="event-meta">
        <?= e($event['event_date'] ?? 'Date') ?> &middot;
        <?= e($event['location'] ?? 'Location') ?>
    </p>
    <a class="button subtle" href="<?= e(url_for('event_detail', ['id' => $event['id'] ?? 0])) ?>">View details</a>
</article>

