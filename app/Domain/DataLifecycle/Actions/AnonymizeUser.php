<?php

namespace App\Domain\DataLifecycle\Actions;

use App\Domain\DataLifecycle\Anonymizers\Contracts\DomainAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\DomainAnonymizerRegistry;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Domain\DataLifecycle\Events\UserAnonymized;
use App\Domain\DataLifecycle\Models\AnonymizationLogEntry;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Orchestrator that walks every registered {@see DomainAnonymizer} for the
 * subject of a deletion request and writes one append-only
 * {@see AnonymizationLogEntry} per domain. Marks the request "anonymized" on
 * success.
 *
 * Anonymizers are run in registration order so the User row scrub is
 * applied last; per-domain anonymizers can still resolve User fields they
 * may need to inspect (e.g. ShopAnonymizer reading metadata) before the row
 * is scrubbed.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-004
 * @see docs/mil-std-498/SRS.md DL-F-009, DL-F-010
 */
class AnonymizeUser
{
    public function __construct(private DomainAnonymizerRegistry $registry) {}

    public function execute(DeletionRequest $request, AnonymizationMode $mode = AnonymizationMode::Anonymize): DeletionRequest
    {
        $subject = $request->user;

        if ($subject === null) {
            throw new \RuntimeException("Deletion request #{$request->getKey()} has no subject user.");
        }

        DB::transaction(function () use ($request, $subject, $mode): void {
            foreach ($this->registry->all() as $anonymizer) {
                try {
                    $result = $anonymizer->anonymize($subject->refresh(), $mode);
                } catch (Throwable $e) {
                    AnonymizationLogEntry::create([
                        'user_id' => $subject->getKey(),
                        'data_class' => $anonymizer->dataClass(),
                        'anonymizer_class' => $anonymizer::class,
                        'records_scrubbed_count' => 0,
                        'records_kept_under_retention_count' => 0,
                        'completed_at' => now(),
                        'summary' => ['error' => $e->getMessage()],
                    ]);

                    throw $e;
                }

                AnonymizationLogEntry::create([
                    'user_id' => $subject->getKey(),
                    'data_class' => $anonymizer->dataClass(),
                    'anonymizer_class' => $anonymizer::class,
                    'records_scrubbed_count' => $result->recordsScrubbed,
                    'records_kept_under_retention_count' => $result->recordsKeptUnderRetention,
                    'retention_until' => $result->retentionUntil?->toDateString(),
                    'completed_at' => now(),
                    'summary' => $result->summary,
                ]);
            }

            $request->update([
                'status' => DeletionRequestStatus::Anonymized,
                'anonymized_at' => now(),
            ]);
        });

        UserAnonymized::dispatch($request->refresh());

        return $request->refresh();
    }
}
