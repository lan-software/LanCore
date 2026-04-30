<?php

namespace App\Domain\DataLifecycle\Anonymizers;

use App\Domain\DataLifecycle\Anonymizers\Contracts\DomainAnonymizer;
use App\Domain\DataLifecycle\DTOs\AnonymizationResult;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tickets keep their FK to the (now anonymized) user, so display
 * automatically renders "Deleted User #…". Under PurgeNow, tickets are
 * hard-deleted unless the retention policy is pinned.
 */
final class TicketingAnonymizer implements DomainAnonymizer
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::TicketingTicket;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        if (! Schema::hasTable('tickets')) {
            return AnonymizationResult::nothingToDo();
        }

        if ($mode === AnonymizationMode::PurgeNow) {
            $policy = $this->maybePolicy();
            if ($policy === null || $policy->can_be_force_deleted) {
                $owned = DB::table('tickets')->where('owner_id', $user->getKey())->delete();
                $managed = DB::table('tickets')->where('manager_id', $user->getKey())->update(['manager_id' => null]);
                $assigned = Schema::hasTable('ticket_user')
                    ? DB::table('ticket_user')->where('user_id', $user->getKey())->delete()
                    : 0;

                return new AnonymizationResult(
                    recordsScrubbed: $owned + $assigned,
                    recordsKeptUnderRetention: 0,
                    retentionUntil: null,
                    summary: ['owned_deleted' => $owned, 'manager_cleared' => $managed, 'assigned_deleted' => $assigned],
                );
            }
        }

        $kept = DB::table('tickets')->where('owner_id', $user->getKey())->count()
            + DB::table('tickets')->where('manager_id', $user->getKey())->count();

        return new AnonymizationResult(
            recordsScrubbed: 0,
            recordsKeptUnderRetention: $kept,
            retentionUntil: $this->retentionUntil(),
        );
    }

    private function maybePolicy(): ?RetentionPolicy
    {
        return RetentionPolicy::query()->where('data_class', $this->dataClass()->value)->first();
    }

    private function retentionUntil(): ?CarbonInterface
    {
        $policy = $this->maybePolicy();

        return $policy === null ? null : now()->addDays($policy->retention_days);
    }
}
