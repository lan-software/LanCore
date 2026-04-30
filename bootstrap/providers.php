<?php

use App\Domain\Auth\Steam\Providers\SteamSocialiteServiceProvider;
use App\Domain\DataLifecycle\Providers\DataLifecycleServiceProvider;
use App\Domain\Policy\Providers\GdprServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\IntegrationServiceProvider;
use App\Providers\PrometheusServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

return array_values(array_filter([
    AppServiceProvider::class,
    DataLifecycleServiceProvider::class,
    FortifyServiceProvider::class,
    GdprServiceProvider::class,
    class_exists(HorizonApplicationServiceProvider::class)
        ? HorizonServiceProvider::class
        : null,
    IntegrationServiceProvider::class,
    PrometheusServiceProvider::class,
    SteamSocialiteServiceProvider::class,
    class_exists(TelescopeApplicationServiceProvider::class)
        ? TelescopeServiceProvider::class
        : null,
]));
