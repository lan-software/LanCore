<?php

namespace App\Domain\Sponsoring\Actions;

use App\Domain\Sponsoring\Models\Sponsor;
use App\Support\StorageRole;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md SPO-F-001
 */
class DeleteSponsor
{
    public function execute(Sponsor $sponsor): void
    {
        DB::transaction(function () use ($sponsor): void {
            if ($sponsor->logo) {
                StorageRole::public()->delete($sponsor->logo);
            }

            $sponsor->delete();
        });
    }
}
