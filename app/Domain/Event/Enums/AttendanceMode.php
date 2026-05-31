<?php

namespace App\Domain\Event\Enums;

/**
 * How an event is attended, per the LAN Party Publishing Standard v2
 * `eventAttendanceMode` field.
 *
 * @see docs/mil-std-498/SSS.md CAP-PUB-001
 * @see docs/mil-std-498/SRS.md PUB-F-002
 */
enum AttendanceMode: int
{
    case Offline = 1;
    case Online = 2;
    case Mixed = 4;

    public function label(): string
    {
        return match ($this) {
            self::Offline => 'In person',
            self::Online => 'Online',
            self::Mixed => 'Hybrid',
        };
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
