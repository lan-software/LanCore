<?php

namespace App\Domain\DataLifecycle\Enums;

use App\Domain\DataLifecycle\Anonymizers\Contracts\DomainAnonymizer;
use App\Domain\DataLifecycle\Models\RetentionPolicy;

/**
 * Stable identifiers for the per-domain data classes whose retention is managed
 * via {@see RetentionPolicy}. Each anonymizer
 * declares its data class via {@see DomainAnonymizer::dataClass()}.
 */
enum RetentionDataClass: string
{
    case UsersProfile = 'users.profile';
    case ShopOrder = 'shop.order';
    case AuditAudit = 'audit.audit';
    case PolicyAcceptance = 'policy.acceptance';
    case SessionsSession = 'sessions.session';
    case NotificationsPreference = 'notifications.preference';
    case NewsComment = 'news.comment';
    case TicketingTicket = 'ticketing.ticket';
    case CompetitionParticipation = 'competition.participation';
    case AchievementEarned = 'achievement.earned';
    case OrgaTeamMembership = 'orgateam.membership';
    case SponsoringRelation = 'sponsoring.relation';

    public function defaultRetentionDays(): int
    {
        return match ($this) {
            self::ShopOrder, self::AuditAudit, self::PolicyAcceptance, self::TicketingTicket => 3650,
            self::SessionsSession => 30,
            default => 0,
        };
    }

    public function defaultLegalBasis(): string
    {
        return match ($this) {
            self::ShopOrder, self::TicketingTicket => 'German HGB §257 / AO §147 — 10-year accounting record retention.',
            self::AuditAudit => 'Internal security and dispute resolution audit trail (10-year retention aligned with accounting).',
            self::PolicyAcceptance => 'Proof of consent under Art. 7(1) GDPR — retained for the lifetime of related accounting records.',
            self::SessionsSession => 'Active session record, no retention requirement beyond active use; 30 days for incident response.',
            default => 'Anonymize on user deletion; no retention obligation.',
        };
    }

    public function defaultDescription(): string
    {
        return match ($this) {
            self::UsersProfile => 'Personally identifiable data on the users row (name, email, address, profile fields).',
            self::ShopOrder => 'Orders, order lines, and invoice references.',
            self::AuditAudit => 'owen-it/laravel-auditing rows tied to the user as actor or auditable.',
            self::PolicyAcceptance => 'Versioned consent acceptance and withdrawal records.',
            self::SessionsSession => 'Active web sessions in the sessions table.',
            self::NotificationsPreference => 'Notification preferences and program subscriptions.',
            self::NewsComment => 'Comments and content authored by the user.',
            self::TicketingTicket => 'Tickets owned, managed or held by the user.',
            self::CompetitionParticipation => 'Competition team membership and participation records.',
            self::AchievementEarned => 'Earned achievements pivot rows.',
            self::OrgaTeamMembership => 'Organizer team memberships and roles.',
            self::SponsoringRelation => 'Sponsor management and relation records.',
        };
    }
}
