<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\PrometheusServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

return array_values(array_filter([
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    class_exists(HorizonApplicationServiceProvider::class)
        ? HorizonServiceProvider::class
        : null,
    PrometheusServiceProvider::class,
    class_exists(TelescopeApplicationServiceProvider::class)
        ? TelescopeServiceProvider::class
        : null,
]));
