<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures;

use FinityLabs\FinSupport\Console\Concerns\DiscoversPanelProviders;
use FinityLabs\FinSupport\Console\Concerns\EditsPanelProviders;
use FinityLabs\FinSupport\Console\Concerns\EditsShieldConfig;
use Illuminate\Console\Command;

/**
 * A command that exposes the three installer traits one step at a time, so
 * a test drives exactly the edit it wants: `fixture:installer register admin`,
 * `deregister admin`, `shield-register`, `shield-unregister`, `discover`.
 */
final class InstallerCommand extends Command
{
    use DiscoversPanelProviders;
    use EditsPanelProviders;
    use EditsShieldConfig;

    protected $signature = 'fixture:installer {step} {panel=admin}';

    protected $description = 'Fixture for the installer traits.';

    public function handle(): int
    {
        $providers = $this->discoverPanelProviders();
        $panel = (string) $this->argument('panel');

        $done = match ((string) $this->argument('step')) {
            'discover' => (bool) $this->line(implode(',', array_keys($providers))) || true,
            'register' => isset($providers[$panel]) && $this->registerPlugin($providers[$panel], FixturePlugin::class),
            'deregister' => isset($providers[$panel]) && $this->deregisterPlugin($providers[$panel], FixturePlugin::class),
            'shield-register' => $this->hasShieldConfig() && $this->registerShieldResources([
                'FinityLabs\\FinSupport\\Tests\\Fixtures\\ThingResource' => ['viewAny', 'view', 'restore'],
            ], 'FinityLabs\\FinSupport'),
            'shield-unregister' => $this->hasShieldConfig() && $this->unregisterShieldResources('FinityLabs\\FinSupport'),
            default => false,
        };

        return $done ? self::SUCCESS : self::FAILURE;
    }
}
