<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Models\Competition;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-004
 */
class DeleteCompetition
{
    public function __construct(private readonly LanBracketsClient $client) {}

    public function execute(Competition $competition): void
    {
        if (config('lanbrackets.enabled') && $competition->isSyncedToLanBrackets()) {
            $this->client->deleteCompetition($competition->lanbrackets_id);
        }

        $competition->delete();
    }
}
