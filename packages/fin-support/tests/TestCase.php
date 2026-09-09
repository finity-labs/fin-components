<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use FinityLabs\FinSupport\Tests\Fixtures\AdminPanelProvider;
use FinityLabs\FinSupport\Tests\Fixtures\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Package test harness: one real Filament panel (admin, default, web guard)
 * carrying the fixture plugin, over an in-memory SQLite users table. Filament
 * before Livewire, as every fin-* harness does (Filament rebinds Livewire's
 * DataStore; Livewire's own registration must come last).
 */
class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            SchemasServiceProvider::class,
            InfolistsServiceProvider::class,
            TablesServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            NotificationsServiceProvider::class,
            WidgetsServiceProvider::class,
            LivewireServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['view']->addNamespace('fin-support-tests', __DIR__.'/Fixtures/views');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Make $id the current, serving panel and sign $user in on its guard.
     */
    protected function usesPanel(string $id, ?Authenticatable $user = null): Panel
    {
        $panel = Filament::getPanel($id);

        Filament::setCurrentPanel($panel);
        Filament::setServingStatus();
        Filament::bootCurrentPanel();

        if ($user !== null) {
            $this->actingAs($user, $panel->getAuthGuard());
        }

        return $panel;
    }
}
