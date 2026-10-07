<?php

declare(strict_types=1);

namespace Rimba\Time;

use Rimba\Base\Services\BitesServiceProvider;

class TimeServiceProvider extends BitesServiceProvider
{
    protected function bootPackage(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        //
    }

    protected function registerPackage(): void
    {
        //
    }
}
