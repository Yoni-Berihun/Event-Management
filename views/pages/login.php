<?php
declare(strict_types=1);

$oldInput = get_old_input();
$loginOld = is_array($oldInput['login'] ?? null) ? $oldInput['login'] : [];
$oldEmail = (string) ($loginOld['email'] ?? '');

$accounts = [
    [
        'icon'  => '👤',
        'title' => 'Attendee',
        'desc'  => 'Browse events, RSVP, and leave feedback after attending.',
        'email' => 'meron@hawassa.et',
        'color' => 'var(--color-primary)',
        'bg'    => 'var(--color-primary-soft)',
    ],
    [
        'icon'  => '📋',
        'title' => 'Organiser',
        'desc'  => 'Create events, manage RSVPs, and view attendee feedback.',
        'email' => 'dawit@hawassa.et',
        'color' => 'var(--color-success)',
        'bg'    => 'var(--color-success-soft)',
    ],
    [
        'icon'  => '🛡️',
        'title' => 'Site Admin',
        'desc'  => 'Approve events, manage all RSVPs, and oversee the platform.',
        'email' => 'admin@cityevents.local',
        'color' => 'var(--color-warning)',
        'bg'    => 'var(--color-warning-soft)',
    ],
];
?>

<section style="max-width:900px;margin:0 auto;">
    <div style="text-align:center;margin-bottom:var(--sp-8);">
        <h1 style="font-family:var(--font-display);font-size:2rem;">Welcome Back</h1>
        <p style="color:var(--color-text-muted);">Choose an account to sign in or use your own credentials below.</p>
    </div>

    <!-- ── Quick-Login Cards ──────────────────────────────────────────────── -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:var(--sp-5);margin-bottom:var(--sp-10);">
        <?php foreach ($accounts as $acct): ?>
        <div class="card" style="text-align:center;border-top:3px solid <?= $acct['color'] ?>;padding:var(--sp-6) var(--sp-5);">
            <div style="font-size:2.2rem;margin-bottom:var(--sp-3);"><?= $acct['icon'] ?></div>
            <h2 style="font-size:1.05rem;margin-bottom:var(--sp-2);"><?= e($acct['title']) ?></h2>
            <p style="font-size:.82rem;color:var(--color-text-muted);margin-bottom:var(--sp-5);line-height:1.5;"><?= e($acct['desc']) ?></p>
            <div style="font-size:.78rem;color:var(--color-text-light);margin-bottom:var(--sp-4);background:var(--color-surface-2);padding:var(--sp-3);border-radius:var(--radius-sm);">
                <strong style="display:block;margin-bottom:2px;"><?= e($acct['email']) ?></strong>
                Password: <code>password</code>
            </div>
            <form action="<?= e(BASE_URL . 'actions/auth/login.php') ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                <input type="hidden" name="redirect" value="<?= e(isset($_GET['redirect']) ? (string) $_GET['redirect'] : '') ?>">
                <input type="hidden" name="email" value="<?= e($acct['email']) ?>">
                <input type="hidden" name="password" value="password">
                <button type="submit" class="button primary" style="width:100%;background:<?= $acct['color'] ?>;border-color:<?= $acct['color'] ?>;">
                    Login as <?= e($acct['title']) ?>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Manual Login Form ──────────────────────────────────────────────── -->
    <div style="max-width:440px;margin:0 auto;">
        <h2 style="text-align:center;font-size:1.1rem;margin-bottom:var(--sp-5);color:var(--color-text-muted);">Or sign in with your own account</h2>
        <div class="form-card">
            <form action="<?= e(BASE_URL . 'actions/auth/login.php') ?>" method="post" class="auth-form" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                <input type="hidden" name="redirect" value="<?= e(isset($_GET['redirect']) ? (string) $_GET['redirect'] : '') ?>">
                <label>
                    Email
                    <input type="email" name="email" value="<?= e($oldEmail) ?>" autocomplete="email"
                           placeholder="you@example.com" required class="form-control">
                </label>
                <label>
                    Password
                    <input type="password" name="password" autocomplete="current-password"
                           placeholder="Enter your password" required class="form-control">
                </label>
                <label style="flex-direction:row;align-items:center;gap:.5rem;cursor:pointer">
                    <input type="checkbox" name="remember_me" value="1">
                    <span style="font-size:.85rem">Remember me for 30 days</span>
                </label>
                <button type="submit" class="button primary" id="loginBtn">Login</button>
                <p style="text-align:center;font-size:.85rem;color:var(--color-text-muted);margin:0">
                    No account yet? <a href="<?= e(url_for('register')) ?>" style="color:var(--color-primary);font-weight:600">Register</a>
                </p>
            </form>
        </div>
    </div>
</section>

<script>
document.getElementById('loginForm')?.addEventListener('submit',function(){
    var b=document.getElementById('loginBtn');if(b){b.disabled=true;b.textContent='Logging in...';}
});
</script>
