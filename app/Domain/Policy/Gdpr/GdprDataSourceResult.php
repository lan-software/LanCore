<?php

namespace App\Domain\Policy\Gdpr;

/**
 * Return value of a GdprDataSource. `records` is serialised to
 * `{key}.json`; `files` are copied into `{key}/{filename}`.
 */
final class GdprDataSourceResult
{
    /**
     * @param  array<string, mixed>  $records
     * @param  list<GdprBinaryAttachment>  $files
     */
    public function __construct(
        public readonly array $records,
        public readonly array $files = [],
    ) {}
}
