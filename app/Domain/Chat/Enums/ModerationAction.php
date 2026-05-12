<?php

namespace App\Domain\Chat\Enums;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-013
 */
enum ModerationAction: string
{
    case Mute = 'mute';
    case Unmute = 'unmute';
    case DeleteMessage = 'delete_message';
    case CloseRoom = 'close_room';
}
