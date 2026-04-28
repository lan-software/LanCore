<?php

namespace App\Domain\Policy\Gdpr\Contracts;

use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;

/**
 * One implementation per domain. Each source produces JSON records and
 * optional binary files for the requested user, with other users'
 * identifiers obfuscated via the GdprExportContext.
 *
 * @see docs/mil-std-498/SSS.md CAP-GDPR-001
 * @see docs/mil-std-498/SRS.md GDPR-F-001..008
 */
interface GdprDataSource
{
    public function key(): string;

    public function label(): string;

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult;
}
