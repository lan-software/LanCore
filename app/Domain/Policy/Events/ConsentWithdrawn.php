<?php

namespace App\Domain\Policy\Events;

use App\Domain\Policy\Models\PolicyAcceptance;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a user withdraws consent for a Policy via the settings UI.
 *
 * The PolicyAcceptance carries withdrawn_at + reason + IP + UA at the
 * time of dispatch.
 */
class ConsentWithdrawn
{
    use Dispatchable;

    public function __construct(public readonly PolicyAcceptance $acceptance) {}
}
