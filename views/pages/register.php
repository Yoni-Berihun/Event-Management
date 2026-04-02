<?php
declare(strict_types=1);

$oldInput = get_old_input();
$registerOld = is_array($oldInput['register'] ?? null) ? $oldInput['register'] : [];
$oldName  = (string) ($registerOld['name'] ?? '');
$oldEmail = (string) ($registerOld['email'] ?? '');
$oldRole  = (string) ($registerOld['role'] ?? 'attendee');
?>
<section class="auth-page">
    <h1>Create an Account</h1>
    <div class="form-card">
        <form action="<?= e(BASE_URL . 'actions/auth/register.php') ?>" method="post" class="auth-form" id="regForm">
            <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
            <label>
                Full Name
                <input type="text" name="name" value="<?= e($oldName) ?>" placeholder="John Doe"
                       required class="form-control" autocomplete="name">
            </label>
            <label>
                Email
                <input type="email" name="email" value="<?= e($oldEmail) ?>" placeholder="you@example.com"
                       required class="form-control" autocomplete="email">
            </label>
            <label>
                Password
                <input type="password" name="password" minlength="8"
                       pattern="(?=.*[A-Za-z])(?=.*\d).{8,}"
                       title="At least 8 characters with at least one letter and one number."
                       autocomplete="new-password" required class="form-control"
                       placeholder="Min 8 chars, letters + numbers">
                <small class="input-hint">At least 8 characters with a letter and a number</small>
            </label>
            <label>
                Confirm Password
                <input type="password" name="confirm_password" minlength="8"
                       autocomplete="new-password" required class="form-control"
                       placeholder="Re-type your password">
            </label>
            <fieldset style="border:1.5px solid var(--color-border-dark);border-radius:var(--radius-sm);padding:var(--sp-4) var(--sp-5);">
                <legend style="font-size:.82rem;font-weight:600;color:var(--color-text-muted);padding:0 var(--sp-2);">I want to…</legend>
                <label style="flex-direction:row;align-items:center;gap:.5rem;cursor:pointer">
                    <input type="radio" name="role" value="attendee" <?= $oldRole !== 'organizer' ? 'checked' : '' ?>>
                    <span style="font-size:.88rem">Attend events (Attendee)</span>
                </label>
                <label style="flex-direction:row;align-items:center;gap:.5rem;cursor:pointer;margin-top:var(--sp-2)">
                    <input type="radio" name="role" value="organizer" <?= $oldRole === 'organizer' ? 'checked' : '' ?>>
                    <span style="font-size:.88rem">Create &amp; manage events (Organiser)</span>
                </label>
            </fieldset>
            <button type="submit" class="button primary" id="regBtn">Register</button>
            <p style="text-align:center;font-size:.85rem;color:var(--color-text-muted);margin:0">
                Already have an account? <a href="<?= e(url_for('login')) ?>" style="color:var(--color-primary);font-weight:600">Login</a>
            </p>
        </form>
    </div>
</section>
<script>
document.getElementById('regForm')?.addEventListener('submit',function(){
    var b=document.getElementById('regBtn');if(b){b.disabled=true;b.textContent='Creating account…';}
});
</script>
