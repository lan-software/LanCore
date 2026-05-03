<?php

namespace App\Domain\EmailLog\Enums;

enum EmailMessageStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Bounced = 'bounced';
    case Complained = 'complained';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::Bounced => 'Bounced',
            self::Complained => 'Complained',
        };
    }
}
