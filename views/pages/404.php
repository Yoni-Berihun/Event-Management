<?php
declare(strict_types=1);
?>
<section class="auth-page" style="text-align:center;padding:var(--sp-12) 0;">
    <div class="empty-icon" style="font-size:4rem;margin-bottom:var(--sp-4);">🔍</div>
    <h1>Page Not Found</h1>
    <p style="color:var(--color-text-muted);margin-bottom:var(--sp-6);">The page you are looking for doesn't exist or has been moved.</p>
    <a class="button primary" href="<?= e(url_for('event_feed')) ?>">Browse Events</a>
</section>
