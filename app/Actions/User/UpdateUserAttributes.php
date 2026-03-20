<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserAttributes
{
    /**
     * Update the given user's profile attributes.
     *
     * @param  array{name?: string, email?: string, password?: string}  $attributes
     */
    public function execute(User $user, array $attributes): void
    {
        DB::transaction(function () use ($user, $attributes) {
            $user->fill($attributes)->save();
        });
    }
}
