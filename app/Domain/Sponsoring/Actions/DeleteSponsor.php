<?php

namespace App\Domain\Sponsoring\Actions;

use App\Domain\Sponsoring\Models\Sponsor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @see docs/mil-std-498/SRS.md SPO-F-001
 */
class DeleteSponsor
{
    public function execute(Sponsor $sponsor): void
    {
        DB::transaction(function () use ($sponsor): void {
            if ($sponsor->logo) {
                Storage::delete($sponsor->logo);
            }

            $sponsor->delete();
        });
    }
}
