<?php

namespace App\Domain\DataLifecycle\Anonymizers;

use App\Domain\DataLifecycle\Anonymizers\Contracts\DomainAnonymizer;
use App\Domain\DataLifecycle\DTOs\AnonymizationResult;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Scrubs PII out of recorded outgoing emails belonging to an anonymized user.
 *
 * The email log persists the rendered subject + body of every outbound mail.
 * For an anonymized user, those records would still expose name, address,
 * order details, etc. unless we redact them. We match by recipient address
 * (the most reliable lookup at MessageSent capture time, since the
 * notifiable morph was not populated in PR 3).
 *
 * Identifying fields scrubbed: to/cc/bcc addresses, subject, html and text
 * bodies, headers (which may contain custom token URLs). The row is kept
 * for forensic reference (timestamp + status) but no longer contains PII.
 */
final class EmailLogAnonymizer implements DomainAnonymizer
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::EmailLogMessage;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        if (! Schema::hasTable('email_messages')) {
            return AnonymizationResult::nothingToDo();
        }

        $email = $user->email;

        if ($email === null || $email === '') {
            return AnonymizationResult::nothingToDo();
        }

        $matchingIds = DB::table('email_messages')
            ->whereJsonContains('to_addresses', [['address' => $email]])
            ->pluck('id')
            ->all();

        if ($matchingIds === []) {
            return AnonymizationResult::nothingToDo();
        }

        if ($mode === AnonymizationMode::PurgeNow) {
            $deleted = DB::table('email_messages')
                ->whereIn('id', $matchingIds)
                ->delete();

            return new AnonymizationResult(
                recordsScrubbed: $deleted,
                recordsKeptUnderRetention: 0,
                retentionUntil: null,
                summary: ['email_messages_deleted' => $deleted],
            );
        }

        $scrubbed = DB::table('email_messages')
            ->whereIn('id', $matchingIds)
            ->update([
                'to_addresses' => json_encode([
                    ['address' => 'anonymized@deleted.local', 'name' => null],
                ]),
                'cc_addresses' => null,
                'bcc_addresses' => null,
                'subject' => '[anonymized]',
                'html_body' => null,
                'text_body' => null,
                'headers' => null,
                'updated_at' => now(),
            ]);

        return new AnonymizationResult(
            recordsScrubbed: $scrubbed,
            recordsKeptUnderRetention: 0,
            retentionUntil: null,
            summary: ['email_messages_scrubbed' => $scrubbed],
        );
    }
}
