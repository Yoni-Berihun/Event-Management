<?php
// Organiser dashboard: pending RSVPs, event list with feedback summary, create/edit events.
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/policy.php';
require_once __DIR__ . '/../../models/EventModel.php';
require_once __DIR__ . '/../../models/RsvpModel.php';
require_once __DIR__ . '/../../models/FeedbackModel.php';

require_organizer();

$orgId         = current_user_id() ?? 0;
$events        = $orgId > 0 ? event_get_by_organizer($orgId) : [];
$pendingRsvps  = rsvp_get_pending_for_organizer($orgId);
$eventIds      = array_map(fn($e) => (int) $e['id'], $events);
$feedbackStats = feedback_summaries_for_events($eventIds);
$categories    = event_get_categories();

// ── Form state: pull old input & field errors (then immediately free from session) ─
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
$fieldErrors = $_SESSION['validation_errors'] ?? [];
unset($_SESSION['validation_errors']);

/**
 * Return the old value for a create-form field.
 */
function ov(string $key, mixed $fallback = ''): string
{
    global $oldInput;
    $v = $oldInput[$key] ?? $fallback;
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/**
 * Return the old value for an edit-form field (keyed by event id + field).
 */
function ov_edit(int $eventId, string $key, mixed $fallback = ''): string
{
    global $oldInput;
    if (($oldInput['event_id'] ?? null) == $eventId) {
        $v = $oldInput[$key] ?? $fallback;
    } else {
        $v = $fallback;
    }
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/**
 * Return CSS class string if field has an error AND was in a create (no event_id) context.
 */
function err_class(string $field): string
{
    global $fieldErrors, $oldInput;
    if (isset($oldInput['event_id'])) return ''; // edit context — skip create highlighting
    return isset($fieldErrors[$field]) ? ' input-error' : '';
}

/**
 * Render inline error message for a field (create context only).
 */
function err_msg(string $field): string
{
    global $fieldErrors, $oldInput;
    if (isset($oldInput['event_id'])) return '';
    if (!isset($fieldErrors[$field])) return '';
    $msg = htmlspecialchars($fieldErrors[$field], ENT_QUOTES, 'UTF-8');
    return "<span class=\"field-error\">{$msg}</span>";
}

/**
 * Return CSS class string if field has an error AND was in an edit context for this event.
 */
function err_class_edit(int $eventId, string $field): string
{
    global $fieldErrors, $oldInput;
    if (($oldInput['event_id'] ?? null) != $eventId) return '';
    return isset($fieldErrors[$field]) ? ' input-error' : '';
}

/**
 * Render inline error for edit context.
 */
function err_msg_edit(int $eventId, string $field): string
{
    global $fieldErrors, $oldInput;
    if (($oldInput['event_id'] ?? null) != $eventId) return '';
    if (!isset($fieldErrors[$field])) return '';
    $msg = htmlspecialchars($fieldErrors[$field], ENT_QUOTES, 'UTF-8');
    return "<span class=\"field-error\">{$msg}</span>";
}
?>

<div class="dashboard-header">
    <div>
        <h1 style="font-family:var(--font-display);font-size:1.8rem;">Dashboard</h1>
        <p style="color:var(--color-text-muted);margin-top:var(--sp-1);">Manage your events and RSVP requests.</p>
    </div>
</div>

<!-- ── Stat row ─────────────────────────────────────────────────────────── -->
<div class="stat-row">
    <div class="stat-box">
        <div class="stat-label">Your Events</div>
        <div class="stat-value"><?= count($events) ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Pending RSVPs</div>
        <div class="stat-value" style="color:var(--color-warning)"><?= count($pendingRsvps) ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Verified Events</div>
        <div class="stat-value" style="color:var(--color-success)"><?= count(array_filter($events, fn($e) => (int)$e['is_verified'])) ?></div>
    </div>
</div>

<!-- ── Pending RSVPs ────────────────────────────────────────────────────── -->
<section class="dashboard-section">
    <h2>⏳ Pending RSVP Requests
        <?php if (count($pendingRsvps) > 0): ?>
            <span class="badge badge-pending" style="vertical-align:middle;margin-left:var(--sp-2)"><?= count($pendingRsvps) ?></span>
        <?php endif; ?>
    </h2>

    <?php if (empty($pendingRsvps)): ?>
        <div class="empty-state" style="padding:var(--sp-8) 0">
            <div class="empty-icon">✅</div>
            <h3>All caught up!</h3>
            <p>No pending RSVPs at the moment.</p>
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
                                <?php if ($isFull): ?><br><span class="badge badge-full" style="margin-top:2px">Full</span><?php endif; ?>
                            </td>
                            <td style="font-size:.85rem"><?= (int)$r['approved_count'] ?> / <?= (int)$r['capacity'] ?></td>
                            <td style="font-size:.78rem;color:var(--color-text-muted)"><?= e(date('d M, H:i', strtotime($r['created_at']))) ?></td>
                            <td>
                                <div class="rsvp-actions">
                                    <?php if (!$isFull): ?>
                                    <form action="<?= e(BASE_URL . 'actions/events/approve_rsvp.php') ?>" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                                        <input type="hidden" name="rsvp_id" value="<?= (int)$r['rsvp_id'] ?>">
                                        <button type="submit" class="button success sm" aria-label="Approve RSVP for <?= e($r['attendee_name']) ?>">✓ Approve</button>
                                    </form>
                                    <?php endif; ?>
                                    <form action="<?= e(BASE_URL . 'actions/events/reject_rsvp.php') ?>" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                                        <input type="hidden" name="rsvp_id" value="<?= (int)$r['rsvp_id'] ?>">
                                        <button type="submit" class="button danger sm" aria-label="Reject RSVP for <?= e($r['attendee_name']) ?>">✕ Reject</button>
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

<!-- ── Create Event ─────────────────────────────────────────────────────── -->
<section class="dashboard-section" id="create-event">
    <h2>➕ Create New Event</h2>
    <div class="card">
        <form action="<?= e(BASE_URL . 'actions/events/create_event.php') ?>" method="post"
              enctype="multipart/form-data" class="auth-form validated-form" id="createEventForm"
              novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5);" class="form-two-col">
                <!-- Title -->
                <label>
                    Title <span style="color:var(--color-danger)">*</span>
                    <input type="text" name="title" id="create_title" maxlength="150" required
                           placeholder="Event title"
                           value="<?= ov('title') ?>"
                           class="<?= err_class('title') ?: err_class('title_len') ?>">
                    <?= err_msg('title') ?: err_msg('title_len') ?>
                </label>

                <!-- Location -->
                <label>
                    Location <span style="color:var(--color-danger)">*</span>
                    <input type="text" name="location" id="create_location" maxlength="150" required
                           placeholder="Venue or address"
                           value="<?= ov('location') ?>"
                           class="<?= err_class('location') ?: err_class('loc_len') ?>">
                    <?= err_msg('location') ?: err_msg('loc_len') ?>
                </label>

                <!-- Category -->
                <label>
                    Category <span style="color:var(--color-danger)">*</span>
                    <select name="category" id="create_category" required
                            class="<?= err_class('category') ?>">
                        <option value="">— Select a category —</option>
                        <?php
                        $predefinedCategories = ['General','Conference','Workshop','Networking','Cultural','Sports','Health & Wellness','Technology','Arts','Community','Other'];
                        foreach ($predefinedCategories as $cat):
                            $selected = ov('category') === $cat ? 'selected' : '';
                        ?>
                            <option value="<?= e($cat) ?>" <?= $selected ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= err_msg('category') ?>
                </label>

                <!-- Capacity -->
                <label>
                    Capacity <span style="color:var(--color-danger)">*</span>
                    <input type="number" name="capacity" id="create_capacity" min="1" required
                           placeholder="Max attendees"
                           value="<?= ov('capacity') ?>"
                           class="<?= err_class('capacity') ?>">
                    <?= err_msg('capacity') ?>
                </label>

                <!-- Start Date -->
                <label>
                    Start Date &amp; Time <span style="color:var(--color-danger)">*</span>
                    <input type="datetime-local" name="event_date" id="create_event_date" required
                           value="<?= ov('event_date') ?>"
                           class="<?= err_class('event_date') ?: err_class('start_bound') ?>">
                    <small class="input-hint">Future dates only</small>
                    <?= err_msg('event_date') ?: err_msg('start_bound') ?>
                </label>

                <!-- End Date -->
                <label>
                    End Date &amp; Time <span style="color:var(--color-danger)">*</span>
                    <input type="datetime-local" name="event_end" id="create_event_end" required
                           value="<?= ov('event_end') ?>"
                           class="<?= err_class('event_end') ?>">
                    <small class="input-hint">Min <?= MIN_EVENT_DURATION_MINUTES ?> minutes after start</small>
                    <?= err_msg('event_end') ?>
                </label>

                <!-- Image -->
                <label>
                    Image <span style="color:var(--color-text-muted);font-size:.82rem">(optional, max 5 MB — JPEG/PNG/GIF)</span>
                    <input type="file" name="image" id="create_image"
                           accept="image/jpeg,image/png,image/gif"
                           data-max-bytes="5242880">
                    <span class="field-error" id="create_image_error" style="display:none"></span>
                </label>
            </div>

            <!-- Description -->
            <label>
                Description <span style="color:var(--color-danger)">*</span>
                <textarea name="description" id="create_description" rows="3" maxlength="5000" required
                          placeholder="Describe your event…"
                          class="<?= err_class('description') ?: err_class('desc_len') ?>"
                          oninput="document.getElementById('createDescCount').textContent=this.value.length"><?= ov('description') ?></textarea>
                <small class="char-count" style="text-align:right"><span id="createDescCount"><?= strlen(ov('description')) ?></span> / 5000</small>
                <?= err_msg('description') ?: err_msg('desc_len') ?>
            </label>

            <button type="submit" class="button primary" id="createBtn" style="align-self:flex-start">
                Submit Event
            </button>
        </form>
    </div>
</section>

<!-- ── Your Events ──────────────────────────────────────────────────────── -->
<section class="dashboard-section">
    <h2>📋 Your Events</h2>
    <?php if (empty($events)): ?>
        <div class="empty-state" style="padding:var(--sp-8) 0">
            <div class="empty-icon">🎉</div>
            <h3>No events yet</h3>
            <p>Create your first event using the form above.</p>
        </div>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <?php
            $eId       = (int) $event['id'];
            $rsvps     = rsvp_get_for_event($eId);
            $approved  = array_filter($rsvps, fn($r) => $r['status'] === 'approved');
            $pending   = array_filter($rsvps, fn($r) => $r['status'] === 'pending');
            $fb        = $feedbackStats[$eId] ?? null;
            $eventEnd  = $event['event_end'] ?? $event['event_date'];
            $hasEnded  = has_datetime_passed((string) $eventEnd);
            // Detect if this event's edit form had an error.
            $editHasErr = (($oldInput['event_id'] ?? null) == $eId);
            ?>
            <div class="card" style="margin-bottom:var(--sp-6);">
                <!-- Event summary row -->
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--sp-4);flex-wrap:wrap;margin-bottom:var(--sp-4);">
                    <div>
                        <div style="display:flex;align-items:center;gap:var(--sp-3);flex-wrap:wrap;">
                            <h2 style="font-size:1.1rem;margin:0"><?= e($event['title']) ?></h2>
                            <?php if (!empty($event['is_verified'])): ?>
                                <span class="badge badge-approved">Verified</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Pending Approval</span>
                            <?php endif; ?>
                            <?php if ($hasEnded): ?><span class="badge badge-ended">Ended</span><?php endif; ?>
                            <?php if (!empty($event['category'])): ?>
                                <span class="badge" style="background:var(--color-surface-2);color:var(--color-text-muted)"><?= e($event['category']) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="event-meta" style="margin-top:var(--sp-2)">
                            📅 <?= e(date('d M Y, H:i', strtotime((string) $event['event_date']))) ?>
                            <?php if (!empty($event['event_end'])): ?> → <?= e(date('H:i', strtotime((string) $event['event_end']))) ?><?php endif; ?>
                            &nbsp;·&nbsp; 📍 <?= e($event['location']) ?>
                        </p>
                        <p style="font-size:.82rem;color:var(--color-text-muted)">
                            Approved: <strong><?= count($approved) ?></strong> / <?= (int)$event['capacity'] ?> &nbsp;·&nbsp;
                            Pending: <strong style="color:var(--color-warning)"><?= count($pending) ?></strong>
                            <?php if ($fb): ?>
                                &nbsp;·&nbsp; Rating: <span class="stars"><?= str_repeat('★', (int) round($fb['avg_rating'])) ?></span>
                                <?= number_format($fb['avg_rating'], 1) ?> (<?= $fb['count'] ?> reviews)
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="<?= e(url_for('event_detail', ['id' => $eId])) ?>" class="button subtle sm">View →</a>
                </div>

                <!-- Attendee list -->
                <?php if (!empty($rsvps)): ?>
                <details style="margin-bottom:var(--sp-4);" <?= $editHasErr ? '' : '' ?>>
                    <summary style="cursor:pointer;font-size:.85rem;font-weight:600;color:var(--color-text-muted)">
                        Attendees (<?= count($approved) ?> approved, <?= count($pending) ?> pending)
                    </summary>
                    <div style="padding:var(--sp-3) 0 0;display:flex;flex-direction:column;gap:var(--sp-1)">
                        <?php foreach ($rsvps as $r): ?>
                            <?php $initials = strtoupper(mb_substr($r['name'], 0, 2)); ?>
                            <div class="attendee-row">
                                <div class="avatar-circle sm"><?= e($initials) ?></div>
                                <div class="attendee-info">
                                    <div class="name"><?= e($r['name']) ?></div>
                                    <div class="email"><?= e($r['email']) ?></div>
                                </div>
                                <div class="attendee-time"><?= e(date('d M Y, g:i A', strtotime($r['created_at']))) ?></div>
                                <?php if ($r['status'] === 'approved'): ?>
                                    <span class="badge badge-approved">Approved</span>
                                <?php elseif ($r['status'] === 'pending'): ?>
                                    <span class="badge badge-pending">Pending</span>
                                <?php else: ?>
                                    <span class="badge badge-rejected">Rejected</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php endif; ?>

                <!-- Edit form (auto-open if this event had a validation error) -->
                <details <?= $editHasErr ? 'open' : '' ?>>
                    <summary style="cursor:pointer;font-size:.85rem;font-weight:600;color:var(--color-primary);margin-bottom:var(--sp-3)">
                        ✏️ Edit Event
                    </summary>
                    <form action="<?= e(BASE_URL . 'actions/events/update_event.php') ?>" method="post"
                          enctype="multipart/form-data" class="auth-form validated-form">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                        <input type="hidden" name="event_id" value="<?= $eId ?>">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5);" class="form-two-col">
                            <label>
                                Title
                                <input type="text" name="title" maxlength="150" required
                                       value="<?= $editHasErr ? ov_edit($eId,'title') : e($event['title']) ?>"
                                       class="<?= err_class_edit($eId,'title') ?: err_class_edit($eId,'title_len') ?>">
                                <?= err_msg_edit($eId,'title') ?: err_msg_edit($eId,'title_len') ?>
                            </label>
                            <label>
                                Location
                                <input type="text" name="location" maxlength="150" required
                                       value="<?= $editHasErr ? ov_edit($eId,'location') : e($event['location']) ?>"
                                       class="<?= err_class_edit($eId,'location') ?>">
                                <?= err_msg_edit($eId,'location') ?>
                            </label>
                            <label>
                                Category
                                <select name="category" required class="<?= err_class_edit($eId,'category') ?>">
                                    <option value="">— Select —</option>
                                    <?php
                                    $currentCat = $editHasErr ? ov_edit($eId,'category') : e($event['category'] ?? 'General');
                                    $predefinedCategories = ['General','Conference','Workshop','Networking','Cultural','Sports','Health & Wellness','Technology','Arts','Community','Other'];
                                    foreach ($predefinedCategories as $cat):
                                        $sel = ($currentCat === $cat) ? 'selected' : '';
                                    ?>
                                        <option value="<?= e($cat) ?>" <?= $sel ?>><?= e($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= err_msg_edit($eId,'category') ?>
                            </label>
                            <label>
                                Capacity
                                <input type="number" name="capacity" min="1" required
                                       value="<?= $editHasErr ? ov_edit($eId,'capacity') : (int)$event['capacity'] ?>"
                                       class="<?= err_class_edit($eId,'capacity') ?>">
                                <?= err_msg_edit($eId,'capacity') ?>
                            </label>
                            <label>
                                Start Date &amp; Time
                                <input type="datetime-local" name="event_date" required
                                       value="<?= $editHasErr ? ov_edit($eId,'event_date') : e(str_replace(' ', 'T', (string)$event['event_date'])) ?>"
                                       class="<?= err_class_edit($eId,'event_date') ?>">
                                <?= err_msg_edit($eId,'event_date') ?>
                            </label>
                            <label>
                                End Date &amp; Time
                                <input type="datetime-local" name="event_end" required
                                       value="<?= $editHasErr ? ov_edit($eId,'event_end') : e(str_replace(' ', 'T', (string)($event['event_end'] ?? ''))) ?>"
                                       class="<?= err_class_edit($eId,'event_end') ?>">
                                <small class="input-hint">Min <?= MIN_EVENT_DURATION_MINUTES ?> min</small>
                                <?= err_msg_edit($eId,'event_end') ?>
                            </label>
                            <label>
                                Change Image <span style="font-size:.8rem;color:var(--color-text-muted)">(optional, max 5 MB)</span>
                                <input type="file" name="image" accept="image/jpeg,image/png,image/gif"
                                       data-max-bytes="5242880">
                            </label>
                        </div>
                        <label>
                            Description
                            <textarea name="description" rows="2" maxlength="5000" required
                                      class="<?= err_class_edit($eId,'description') ?>"><?= $editHasErr ? ov_edit($eId,'description') : e($event['description']) ?></textarea>
                            <?= err_msg_edit($eId,'description') ?>
                        </label>
                        <label>
                            Edit Reason <span style="color:var(--color-text-muted);font-size:.82rem">(optional)</span>
                            <input type="text" name="edit_reason" maxlength="500"
                                   placeholder="Why are you updating this event?"
                                   value="<?= $editHasErr ? ov_edit($eId,'edit_reason') : '' ?>">
                        </label>
                        <button type="submit" class="button primary" style="align-self:flex-start">Save Changes</button>
                    </form>
                </details>

                <!-- Delete -->
                <form action="<?= e(BASE_URL . 'actions/events/delete_event.php') ?>" method="post"
                      onsubmit="return confirm('Permanently delete this event? This cannot be undone.');"
                      style="margin-top:var(--sp-3)">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <input type="hidden" name="event_id" value="<?= $eId ?>">
                    <button type="submit" class="button danger sm">🗑 Delete Event</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<style>
@media (max-width: 640px) {
    .form-two-col { grid-template-columns: 1fr !important; }
}
</style>
