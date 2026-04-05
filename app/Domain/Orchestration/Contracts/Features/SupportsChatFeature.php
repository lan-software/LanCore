<?php

namespace App\Domain\Orchestration\Contracts\Features;

use App\Domain\Orchestration\Models\OrchestrationJob;

/**
 * Marker interface for match handlers that support in-game chat capture.
 */
interface SupportsChatFeature
{
    /**
     * Process an incoming chat message from the match handler.
     *
     * @param  array<string, mixed>  $chatData
     */
    public function handleChatMessage(OrchestrationJob $job, array $chatData): void;
}
