<?php

namespace App\Domain\DataLifecycle\Actions;

use App\Domain\DataLifecycle\Models\RetentionPolicy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md DL-F-012
 */
class UpdateRetentionPolicy
{
    /**
     * @param  array{retention_days?: int, legal_basis?: string, can_be_force_deleted?: bool, description?: ?string}  $attributes
     */
    public function execute(RetentionPolicy $policy, array $attributes, User $editor): RetentionPolicy
    {
        return DB::transaction(function () use ($policy, $attributes, $editor) {
            $policy->fill($attributes);
            $policy->updated_by_user_id = $editor->getKey();
            $policy->save();

            return $policy->refresh();
        });
    }
}
