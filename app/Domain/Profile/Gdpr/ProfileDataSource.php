<?php

namespace App\Domain\Profile\Gdpr;

use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;

class ProfileDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'profile';
    }

    public function label(): string
    {
        return 'Profile and account data';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        $user->refresh();

        $row = $user->attributesToArray();
        $row['roles'] = $user->roles()->pluck('name')->all();

        unset($row['password'], $row['remember_token'], $row['two_factor_secret'], $row['two_factor_recovery_codes']);

        return new GdprDataSourceResult([
            'user' => $row,
        ]);
    }
}
