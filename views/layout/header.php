<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Discover and track city-wide events — RSVP, give feedback, and stay connected.">
    <title><?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= e(BASE_URL . 'assets/css/style.css') ?>">
</head>
<body>
<header class="site-header" role="banner">
    <div class="container header-inner">
        <a href="<?= e(url_for('event_feed')) ?>" class="logo" aria-label="<?= e(APP_NAME) ?> home">
            📍 <?= e(APP_NAME) ?>
        </a>

        <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="mainNav" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" id="mainNav" role="navigation" aria-label="Main navigation">
            <a href="<?= e(url_for('event_feed')) ?>">Events</a>
            <?php if (current_user_id() !== null): ?>
                <?php if (user_has_role('organizer')): ?>
                    <a href="<?= e(url_for('dashboard')) ?>">Dashboard</a>
                <?php endif; ?>
                <?php if (user_has_role('admin')): ?>
                    <a href="<?= e(url_for('admin_events')) ?>">Admin Events</a>
                    <a href="<?= e(url_for('admin_users')) ?>">Users</a>
                <?php endif; ?>
                <form action="<?= e(BASE_URL . 'actions/auth/logout.php') ?>" method="post" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <button type="submit" aria-label="Log out">Logout</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url_for('login')) ?>">Login</a>
                <a href="<?= e(url_for('register')) ?>">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="site-main container" id="main-content">
<script>
(function() {
    const btn = document.getElementById('navToggle');
    const nav = document.getElementById('mainNav');
    if (btn && nav) {
        btn.addEventListener('click', function() {
            const open = nav.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }
})();
</script>
