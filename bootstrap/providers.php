<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Providers\DynamicConfigurationServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
