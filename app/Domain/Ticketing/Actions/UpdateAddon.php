<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\Addon;
use Illuminate\Support\Facades\DB;

class UpdateAddon
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Addon $addon, array $attributes): Addon
    {
        return DB::transaction(function () use ($addon, $attributes): Addon {
            $addon->fill($attributes)->save();

            return $addon;
        });
    }
}
