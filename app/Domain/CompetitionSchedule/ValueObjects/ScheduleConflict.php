<?php

namespace App\Domain\CompetitionSchedule\ValueObjects;

/**
 * @see docs/mil-std-498/SRS.md COMP-SCH-004
 */
final readonly class ScheduleConflict
{
    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_ERROR = 'error';

    /**
     * @param  array<string, mixed>  $params
     * @param  array<int, string>  $scheduleIds
     */
    public function __construct(
        public string $severity,
        public string $messageKey,
        public array $params,
        public array $scheduleIds,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'message_key' => $this->messageKey,
            'params' => $this->params,
            'schedule_ids' => $this->scheduleIds,
        ];
    }
}
