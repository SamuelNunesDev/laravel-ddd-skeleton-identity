<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\ModulesServiceProvider;
use App\Shared\Infrastructure\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    ModulesServiceProvider::class,
];
