<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\Addon;
use Illuminate\Support\Facades\DB;

class DeleteAddon
{
    public function execute(Addon $addon): void
    {
        DB::transaction(fn () => $addon->delete());
    }
}
