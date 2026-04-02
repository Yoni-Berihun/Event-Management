<?php
// Admin user management: add, list, role change, delete.
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/UserModel.php';

require_admin();

$roleFilter = isset($_GET['role']) && in_array($_GET['role'], ['admin', 'organizer', 'attendee'], true)
    ? $_GET['role'] : null;
$users   = user_get_all($roleFilter);
$counts  = user_count_by_role();
$actorId = current_user_id() ?? 0;

$roleLabels = ['admin' => '🛡️ Admin', 'organizer' => '📋 Organiser', 'attendee' => '👤 Attendee'];

// ── Pull old input & validation errors for Add User form ─────────────────────
$addOld    = $_SESSION['old_input']['add_user'] ?? [];
unset($_SESSION['old_input']);
$addErrors = $_SESSION['validation_errors'] ?? [];
unset($_SESSION['validation_errors']);

function add_err_class(string $field, array $errors): string
{
    return isset($errors[$field]) ? ' input-error' : '';
}

function add_err_msg(string $field, array $errors): string
{
    if (!isset($errors[$field])) return '';
    $msg = htmlspecialchars($errors[$field], ENT_QUOTES, 'UTF-8');
    return "<span class=\"field-error\">{$msg}</span>";
}
?>

<div class="dashboard-header">
    <div>
        <h1 style="font-family:var(--font-display);font-size:1.8rem;">User Management</h1>
        <p style="color:var(--color-text-muted);margin-top:var(--sp-1);">View, edit roles, add and delete platform accounts.</p>
    </div>
    <div style="display:flex;gap:var(--sp-3);">
        <a href="<?= e(url_for('admin_events')) ?>" class="button subtle sm">← Events</a>
    </div>
</div>

<!-- Stat boxes -->
<div class="stat-row">
    <div class="stat-box">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= $counts['total'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Organisers</div>
        <div class="stat-value" style="color:var(--color-success)"><?= $counts['organizer'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Attendees</div>
        <div class="stat-value" style="color:var(--color-primary)"><?= $counts['attendee'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Admins</div>
        <div class="stat-value" style="color:var(--color-warning)"><?= $counts['admin'] ?></div>
    </div>
</div>

<!-- ── Add User Form ─────────────────────────────────────────────────────── -->
<section class="dashboard-section" id="add-user">
    <h2>➕ Add New User</h2>
    <div class="card">
        <form action="<?= e(BASE_URL . 'actions/admin/add_user.php') ?>" method="post"
              class="auth-form validated-form" id="addUserForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5);" class="form-two-col">

                <!-- Name -->
                <label>
                    Full Name <span style="color:var(--color-danger)">*</span>
                    <input type="text" name="name" id="adduser_name" maxlength="100" required
                           placeholder="Full name"
                           value="<?= htmlspecialchars($addOld['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="<?= add_err_class('name', $addErrors) ?: add_err_class('name_len', $addErrors) ?>">
                    <?= add_err_msg('name', $addErrors) ?: add_err_msg('name_len', $addErrors) ?>
                </label>

                <!-- Email -->
                <label>
                    Email Address <span style="color:var(--color-danger)">*</span>
                    <input type="email" name="email" id="adduser_email" maxlength="150" required
                           placeholder="user@example.com"
                           value="<?= htmlspecialchars($addOld['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="<?= add_err_class('email', $addErrors) ?>">
                    <?= add_err_msg('email', $addErrors) ?>
                </label>

                <!-- Password -->
                <label>
                    Password <span style="color:var(--color-danger)">*</span>
                    <input type="password" name="password" id="adduser_password" required
                           placeholder="Min 8 chars, 1 letter + 1 number"
                           autocomplete="new-password"
                           class="<?= add_err_class('password', $addErrors) ?>">
                    <small class="input-hint">Minimum 8 characters with at least one letter and one number</small>
                    <?= add_err_msg('password', $addErrors) ?>
                </label>

                <!-- Role -->
                <label>
                    Role <span style="color:var(--color-danger)">*</span>
                    <select name="role" id="adduser_role" required
                            class="<?= add_err_class('role', $addErrors) ?>">
                        <option value="">— Select role —</option>
                        <?php foreach (['attendee' => '👤 Attendee', 'organizer' => '📋 Organiser', 'admin' => '🛡️ Admin'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($addOld['role'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= add_err_msg('role', $addErrors) ?>
                </label>

            </div>

            <div style="display:flex;gap:var(--sp-3);align-items:center;flex-wrap:wrap;margin-top:var(--sp-2);">
                <button type="submit" class="button primary" id="addUserBtn">
                    ➕ Create User Account
                </button>
                <span style="font-size:.8rem;color:var(--color-text-muted)">
                    The new user will log in with their email and password. They will not be auto-logged-in.
                </span>
            </div>
        </form>
    </div>
</section>

<!-- Role filter tabs -->
<div style="display:flex;gap:var(--sp-2);margin-bottom:var(--sp-6);flex-wrap:wrap;">
    <a href="<?= e(url_for('admin_users')) ?>"
       class="button sm <?= $roleFilter === null ? 'primary' : 'subtle' ?>">All (<?= $counts['total'] ?>)</a>
    <?php foreach (['organizer' => 'Organisers', 'attendee' => 'Attendees', 'admin' => 'Admins'] as $r => $label): ?>
        <a href="<?= e(url_for('admin_users', ['role' => $r])) ?>"
           class="button sm <?= $roleFilter === $r ? 'primary' : 'subtle' ?>"><?= $label ?> (<?= $counts[$r] ?>)</a>
    <?php endforeach; ?>
</div>

<!-- Users table -->
<?php if (empty($users)): ?>
    <div class="empty-state" style="padding:var(--sp-8) 0">
        <div class="empty-icon">👥</div>
        <h3>No users found</h3>
    </div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>Events</th>
                <th>RSVPs</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <?php $isSelf = (int)$u['id'] === $actorId; ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:var(--sp-3);">
                            <div class="avatar-circle" data-initials="<?= e(strtoupper(mb_substr($u['name'], 0, 2))) ?>">
                                <?= e(strtoupper(mb_substr($u['name'], 0, 2))) ?>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:.88rem;"><?= e($u['name']) ?></div>
                                <div style="font-size:.78rem;color:var(--color-text-muted);"><?= e($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($isSelf): ?>
                            <span class="badge badge-approved"><?= $roleLabels[$u['role']] ?? $u['role'] ?></span>
                            <div style="font-size:.7rem;color:var(--color-text-light);margin-top:2px;">You</div>
                        <?php else: ?>
                            <form action="<?= e(BASE_URL . 'actions/admin/update_user_role.php') ?>" method="post"
                                  style="display:flex;align-items:center;gap:var(--sp-2);">
                                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <select name="role" class="form-control"
                                        style="padding:.3rem .5rem;font-size:.82rem;width:auto;min-width:110px;"
                                        onchange="this.form.submit()">
                                    <option value="attendee"  <?= $u['role'] === 'attendee'  ? 'selected' : '' ?>>👤 Attendee</option>
                                    <option value="organizer" <?= $u['role'] === 'organizer' ? 'selected' : '' ?>>📋 Organiser</option>
                                    <option value="admin"     <?= $u['role'] === 'admin'     ? 'selected' : '' ?>>🛡️ Admin</option>
                                </select>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.88rem;text-align:center;"><?= (int)$u['event_count'] ?></td>
                    <td style="font-size:.88rem;text-align:center;"><?= (int)$u['rsvp_count'] ?></td>
                    <td style="font-size:.78rem;color:var(--color-text-muted);"><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                    <td>
                        <?php if (!$isSelf): ?>
                            <form action="<?= e(BASE_URL . 'actions/admin/delete_user.php') ?>" method="post"
                                  onsubmit="return confirm('Delete <?= e($u['name']) ?>? This removes all their events, RSVPs, and comments.');">
                                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="button danger sm">🗑 Delete</button>
                            </form>
                        <?php else: ?>
                            <span style="font-size:.78rem;color:var(--color-text-light);">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
