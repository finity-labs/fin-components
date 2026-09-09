<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * The smallest plugin with a policyNamespace() option, the shape every fin-*
 * plugin gives PolicyRegistrar::namespaceOf().
 */
final class FixturePlugin implements Plugin
{
    private string $policyNamespace = 'App\\Policies';

    public static function make(): static
    {
        return new self;
    }

    public function getId(): string
    {
        return 'fixture';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}

    public function policyNamespace(string $namespace): static
    {
        $this->policyNamespace = $namespace;

        return $this;
    }

    public function getPolicyNamespace(): string
    {
        return $this->policyNamespace;
    }
}
