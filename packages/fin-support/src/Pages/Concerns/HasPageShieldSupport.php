<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Pages\Concerns;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * Page access for a Filament page: Shield's permission when Shield is
 * installed, an opt-in Gate ability when it is not, and canAccessFallback()
 * otherwise — parent::canAccess() by default, which is open.
 *
 * The Shield branch asks Shield for the permission key rather than building
 * it: Shield 4 made both the separator and the case configurable, so any
 * hand-built name is wrong for somebody. Shield is never a hard dependency;
 * its classes are reached behind class_exists().
 *
 * Without Shield, a host gates the page by defining a Gate ability named
 * page_{ClassBasename} — Gate::define('page_HelpSettings', ...). That is
 * this trait's own convention, not Shield 3's naming that it resembles.
 *
 * Every method is static and overridable. A page that wants its own last
 * word (a plugin option, say) overrides canAccessFallback(); tests override
 * isShieldAvailable() and getPagePermission() to prove the Shield branch
 * without Shield in the class map.
 */
trait HasPageShieldSupport
{
    protected static ?string $pagePermissionKey = null;

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && parent::shouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        if (! static::isShieldAvailable()) {
            $ability = 'page_'.class_basename(static::class);

            if (Gate::has($ability)) {
                return (bool) static::getAuthUser()?->can($ability);
            }

            return static::canAccessFallback();
        }

        $permission = static::getPagePermission();
        $user = Filament::auth()->user();

        return $permission && $user
            ? $user->can($permission)
            : static::canAccessFallback();
    }

    /**
     * The answer when neither Shield nor a Gate ability has one: open.
     */
    protected static function canAccessFallback(): bool
    {
        return parent::canAccess();
    }

    protected static function getAuthUser(): ?Authenticatable
    {
        try {
            return Filament::auth()->user();
        } catch (\Throwable) {
            return auth()->user();
        }
    }

    protected static function isShieldAvailable(): bool
    {
        return class_exists(FilamentShieldPlugin::class);
    }

    protected static function getPagePermission(): ?string
    {
        if (static::$pagePermissionKey === null) {
            $page = FilamentShield::getPages()[static::class] ?? null;
            static::$pagePermissionKey = $page ? array_key_first($page['permissions']) : null;
        }

        return static::$pagePermissionKey;
    }
}
