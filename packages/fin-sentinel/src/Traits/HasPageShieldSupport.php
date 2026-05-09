<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Traits;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Facades\Filament;
use FinityLabs\FinSentinel\FinSentinelPlugin;

trait HasPageShieldSupport
{
    protected static ?string $pagePermissionKey = null;

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && parent::shouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        if (static::isShieldAvailable()) {
            $permission = static::getPagePermission();
            $user = Filament::auth()->user();

            if ($permission && $user) {
                return $user->can($permission);
            }
        }

        return FinSentinelPlugin::get()->userCanAccess();
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
