<?php

namespace FinityLabs\FinAvatar\Tests;

use FinityLabs\FinAvatar\FinAvatarServiceProvider;
use Filament\Support\SupportServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class, // Livewire Support
            SupportServiceProvider::class, // Filament Support
            FinAvatarServiceProvider::class, // Your Package
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('database.default', 'testing');
    }
}
