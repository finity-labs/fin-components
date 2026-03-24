<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Traits;

use Filament\Facades\Filament;

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
            return parent::canAccess();
        }

        $permission = static::getPagePermission();
        $user = Filament::auth()->user();

        return $permission && $user
            ? $user->can($permission)
            : parent::canAccess();
    }

    protected static function isShieldAvailable(): bool
    {
        return class_exists(\BezhanSalleh\FilamentShield\FilamentShieldPlugin::class);
    }

    protected static function getPagePermission(): ?string
    {
        if (static::$pagePermissionKey === null) {
            $page = \BezhanSalleh\FilamentShield\Facades\FilamentShield::getPages()[static::class] ?? null;
            static::$pagePermissionKey = $page ? array_key_first($page['permissions']) : null;
        }

        return static::$pagePermissionKey;
    }
}
