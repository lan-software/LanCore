<?php

namespace App\Domain\Auth\Steam\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Steam\SteamExtendSocialite;

class SteamSocialiteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, [SteamExtendSocialite::class, 'handle']);
    }
}
