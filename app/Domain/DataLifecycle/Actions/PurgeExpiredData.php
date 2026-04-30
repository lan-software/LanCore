<?php

namespace App\Domain\DataLifecycle\Actions;

use App\Domain\DataLifecycle\Anonymizers\DomainAnonymizerRegistry;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\DataLifecycle\Models\AnonymizationLogEntry;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use App\Domain\DataLifecycle\RetentionEvaluators\RetentionEvaluatorRegistry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Walks every soft-deleted, anonymized user and, for each registered
 * anonymizer, asks the matching retention evaluator whether retention has
 * expired. When expired AND the matching RetentionPolicy permits force
 * deletion, runs the anonymizer in PurgeNow mode to hard-delete. When all
 * evaluators agree no obligations remain, the User row itself is hard-deleted.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-005
 * @see docs/mil-std-498/SRS.md DL-F-013, DL-F-017
 */
class PurgeExpiredData
{
    public function __construct(
        private DomainAnonymizerRegistry $anonymizers,
        private RetentionEvaluatorRegistry $evaluators,
    ) {}

    /**
     * @return array{users_purged: int, anonymizers_run: int, dry_run: bool}
     */
    public function execute(bool $dryRun = false): array
    {
        $stats = ['users_purged' => 0, 'anonymizers_run' => 0, 'dry_run' => $dryRun];

        User::onlyTrashed()
            ->whereNotNull('anonymized_at')
            ->orderBy('id')
            ->lazyById(100)
            ->each(function (User $user) use (&$stats, $dryRun): void {
                $allCleared = true;

                foreach ($this->anonymizers->all() as $anonymizer) {
                    $evaluator = $this->evaluators->for($anonymizer->dataClass());
                    $verdict = $evaluator?->evaluate($user);

                    $expired = $verdict === null || $verdict->isExpired();

                    if (! $expired) {
                        $allCleared = false;

                        continue;
                    }

                    $policy = RetentionPolicy::query()
                        ->where('data_class', $anonymizer->dataClass()->value)
                        ->first();

                    if ($policy !== null && ! $policy->can_be_force_deleted) {
                        $allCleared = false;

                        continue;
                    }

                    if ($dryRun) {
                        $stats['anonymizers_run']++;

                        continue;
                    }

                    $result = $anonymizer->anonymize($user, AnonymizationMode::PurgeNow);

                    AnonymizationLogEntry::create([
                        'user_id' => $user->getKey(),
                        'data_class' => $anonymizer->dataClass(),
                        'anonymizer_class' => $anonymizer::class.':purge',
                        'records_scrubbed_count' => $result->recordsScrubbed,
                        'records_kept_under_retention_count' => $result->recordsKeptUnderRetention,
                        'retention_until' => $result->retentionUntil?->toDateString(),
                        'completed_at' => now(),
                        'summary' => array_merge($result->summary, ['triggered_by' => 'PurgeExpiredData']),
                    ]);

                    $stats['anonymizers_run']++;
                }

                if ($allCleared && ! $dryRun) {
                    DB::table('users')->where('id', $user->getKey())->delete();
                    $stats['users_purged']++;
                }
            });

        return $stats;
    }
}
