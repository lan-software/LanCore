<?php

namespace App\Domain\Policy\Gdpr;

/**
 * A binary file referenced by a GdprDataSource — copied verbatim into
 * the export ZIP at `{source-key}/{filename}`.
 */
final class GdprBinaryAttachment
{
    public function __construct(
        public readonly string $filename,
        public readonly string $absoluteSourcePath,
    ) {}
}
