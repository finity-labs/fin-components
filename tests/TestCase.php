<?php

declare(strict_types=1);

namespace FinityLabs\FinComponents\Tests;

use FinityLabs\FinComponents\FinComponentsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FinComponentsServiceProvider::class,
        ];
    }
}
