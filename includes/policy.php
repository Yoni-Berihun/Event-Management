<?php
// Authorization policy helpers for event actions.

declare(strict_types=1);

require_once __DIR__ . '/session.php';

/**
 * Resolve effective role for policy checks.
 */
function policy_effective_role(?string $userRole = null): ?string
{
    if ($userRole !== null) {
        return $userRole;
    }

    return current_user_role();
}

/**
 * Check whether user is admin.
 */
function policy_is_admin(?string $userRole = null): bool
{
    return policy_effective_role($userRole) === 'admin';
}

/**
 * Check whether user owns the event.
 */
function policy_is_event_owner(int $userId, array $event): bool
{
    return $userId > 0 && $userId === (int) ($event['organizer_id'] ?? 0);
}

/**
 * Can user view event?
 * Rule: verified OR admin OR organizer-owner (unless verification not required).
 */
function can_view_event(
    int $userId,
    array $event,
    ?string $userRole = null,
    bool $requireVerification = true
): bool {
    if (policy_is_admin($userRole)) {
        return true;
    }

    if (policy_is_event_owner($userId, $event)) {
        return true;
    }

    if (!$requireVerification) {
        return true;
    }

    return (int) ($event['is_verified'] ?? 0) === 1;
}

/**
 * Can user RSVP to event?
 * Rule: event MUST be verified (approved by admin).  No RSVP on unapproved events.
 * Additionally, user must be able to view the event.
 */
function can_rsvp_event(
    int $userId,
    array $event,
    ?string $userRole = null,
    bool $requireVerification = true
): bool {
    // RSVP always requires the event to be approved — no exceptions.
    if ((int) ($event['is_verified'] ?? 0) !== 1) {
        return false;
    }

    return can_view_event($userId, $event, $userRole, $requireVerification);
}

/**
 * Can user edit event?
 * Rule: admin OR organizer-owner.
 */
function can_edit_event(int $userId, array $event, ?string $userRole = null): bool
{
    return policy_is_admin($userRole) || policy_is_event_owner($userId, $event);
}

/**
 * Can user comment on event?
 * Rule: if event is viewable.
 * (Additional domain rules, e.g. "after event end", should be enforced separately.)
 */
function can_comment_event(
    int $userId,
    array $event,
    ?string $userRole = null,
    bool $requireVerification = true
): bool {
    return can_view_event($userId, $event, $userRole, $requireVerification);
}

/**
 * Can user create events?
 * Rule: organizer or admin.
 */
function can_create_event(int $userId, ?string $userRole = null): bool
{
    if ($userId <= 0) {
        return false;
    }

    $role = policy_effective_role($userRole);
    return $role === 'organizer' || $role === 'admin';
}

/**
 * Can user manage RSVPs (approve/reject) for an event?
 * Rule: admin OR organizer-owner.
 */
function can_manage_rsvps(int $userId, array $event, ?string $userRole = null): bool
{
    return policy_is_admin($userRole) || policy_is_event_owner($userId, $event);
}

/**
 * Can user submit feedback for an event?
 * Rule: must be logged in AND approved attendee AND event ended AND not already submitted.
 * (Actual DB checks are handled in action; this only checks role-level access.)
 */
function can_submit_feedback(int $userId, ?string $userRole = null): bool
{
    return $userId > 0;
}


