<?php

namespace App\Domain\Theme\Actions;

use App\Domain\Theme\Models\Theme;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-THM-001
 * @see docs/mil-std-498/SRS.md THM-F-001
 */
class DeleteTheme
{
    public function execute(Theme $theme): void
    {
        DB::transaction(fn () => $theme->delete());
    }
}
