<?php

namespace App\Domain\Event\Enums;

/**
 * Lifecycle status of an event as published to the LAN Party Publishing
 * Standard v2 `eventStatus` field. Distinct from {@see EventStatus}, which
 * governs the internal draft/published workflow.
 *
 * @see docs/mil-std-498/SSS.md CAP-PUB-001
 * @see docs/mil-std-498/SRS.md PUB-F-002
 */
enum EventSyndicationStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
    case Postponed = 'postponed';
    case Rescheduled = 'rescheduled';
    case MovedOnline = 'moved_online';

    /**
     * The schema.org EventStatusType URL required by the standard.
     */
    public function schemaOrgUrl(): string
    {
        return match ($this) {
            self::Scheduled => 'https://schema.org/EventScheduled',
            self::Cancelled => 'https://schema.org/EventCancelled',
            self::Postponed => 'https://schema.org/EventPostponed',
            self::Rescheduled => 'https://schema.org/EventRescheduled',
            self::MovedOnline => 'https://schema.org/EventMovedOnline',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Cancelled => 'Cancelled',
            self::Postponed => 'Postponed',
            self::Rescheduled => 'Rescheduled',
            self::MovedOnline => 'Moved online',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
