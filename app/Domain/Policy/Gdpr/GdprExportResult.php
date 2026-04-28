<?php

namespace App\Domain\Policy\Gdpr;

final class GdprExportResult
{
    /**
     * @param  array<string, mixed>  $manifest
     */
    public function __construct(
        public readonly string $absoluteZipPath,
        public readonly int $byteSize,
        public readonly array $manifest,
    ) {}
}
