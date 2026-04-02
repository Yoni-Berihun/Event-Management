<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';

$flashes = get_flashes();
if (!empty($flashes)): ?>
<section class="flash-messages" role="alert" aria-live="polite">
    <?php foreach ($flashes as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
            <div class="flash flash-<?= e($type) ?>" data-auto-dismiss="6000">
                <span class="flash-icon">
                    <?php if ($type === 'success'): ?>✓
                    <?php elseif ($type === 'error'): ?>✕
                    <?php elseif ($type === 'warning'): ?>⚠
                    <?php else: ?>ℹ<?php endif; ?>
                </span>
                <span class="flash-text"><?= e($message) ?></span>
                <button type="button" class="flash-close" aria-label="Dismiss" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</section>
<script>
(function () {
    document.querySelectorAll('.flash[data-auto-dismiss]').forEach(function (el) {
        var delay = parseInt(el.getAttribute('data-auto-dismiss'), 10) || 6000;
        setTimeout(function () {
            el.style.transition = 'opacity .4s ease, transform .4s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-6px)';
            setTimeout(function () { el.remove(); }, 420);
        }, delay);
    });
})();
</script>
<?php endif; ?>
