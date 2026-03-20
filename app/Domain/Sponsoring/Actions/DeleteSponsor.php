<?php

namespace App\Domain\Sponsoring\Actions;

use App\Domain\Sponsoring\Models\Sponsor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
