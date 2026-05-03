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
 * News comments and articles keep the user FK (anonymized author renders as
 * "Deleted User #…"). Comments containing replies stay in thread context.
 */
final class NewsAnonymizer implements DomainAnonymizer
{
    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::NewsComment;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        $kept = 0;

        $authorshipColumns = [
            'news_comments' => 'user_id',
            'news_articles' => 'author_id',
        ];

        foreach ($authorshipColumns as $table => $column) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $kept += DB::table($table)->where($column, $user->getKey())->count();
        }

        return new AnonymizationResult(
            recordsScrubbed: 0,
            recordsKeptUnderRetention: $kept,
            retentionUntil: null,
            summary: ['authorship_reassigned_to_anonymized_user' => true],
        );
    }
}
