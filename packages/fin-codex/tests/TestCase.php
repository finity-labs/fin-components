<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Tests;

use FinityLabs\FinCodex\FinCodexServiceProvider;
use FinityLabs\LinCodex\LinCodexServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelSettingsServiceProvider::class,
            LivewireServiceProvider::class,
            LinCodexServiceProvider::class,
            FinCodexServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
