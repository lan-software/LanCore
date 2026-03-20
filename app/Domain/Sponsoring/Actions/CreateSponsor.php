<?php

namespace App\Domain\Sponsoring\Actions;

use App\Domain\Sponsoring\Models\Sponsor;
use Illuminate\Support\Facades\DB;

class CreateSponsor
{
    /**
     * @param  array{name: string, description?: string|null, link?: string|null, logo?: string|null, sponsor_level_id?: int|null}  $attributes
     * @param  array<int>  $eventIds
     */
    public function execute(array $attributes, array $eventIds = []): Sponsor
    {
        return DB::transaction(function () use ($attributes, $eventIds): Sponsor {
            $sponsor = Sponsor::create($attributes);

            if (! empty($eventIds)) {
                $sponsor->events()->attach($eventIds);
            }

            return $sponsor;
        });
    }
}
