<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Auth;

use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Maps the policies of one namespace onto a package's models.
 *
 * A package names its models and the policy class basename each looks for;
 * the host writes {namespace}\{Basename} — by hand, or through Shield's
 * generator — and it is registered when it exists. A model with a shipped
 * default in $fallbacks gets that when the host has none; a model without
 * one keeps whatever was registered before.
 *
 * Meant to run twice: from the package's service provider at boot with
 * namespaceOf() (the default panel's plugin option, or App\Policies), and
 * from the plugin's boot() with that panel's own option, which is what makes
 * a policyNamespace() setting per panel rather than default-panel-only. The
 * Gate map is a plain array keyed by model, so repeating a registration
 * costs nothing.
 *
 * The explicit registration is not optional: Gate::guessPolicyName() walks
 * the model's own namespace and never finds App\Policies from a vendor
 * model.
 */
final class PolicyRegistrar
{
    public const DEFAULT_NAMESPACE = 'App\\Policies';

    /**
     * @param  array<class-string, string>  $policies  model => policy class basename
     * @param  array<class-string, class-string>  $fallbacks  model => the policy to register when the host has none
     *
     * @return array<class-string, class-string> model => the policy now registered for it
     */
    public static function register(string $namespace, array $policies, array $fallbacks = []): array
    {
        $namespace = rtrim($namespace, '\\');
        $gate = Gate::getFacadeRoot();
        $registered = [];

        foreach ($policies as $model => $basename) {
            $policy = $namespace.'\\'.ltrim($basename, '\\');

            if (! class_exists($policy)) {
                $policy = $fallbacks[$model] ?? null;
            }

            if ($policy === null) {
                continue;
            }

            $gate->policy($model, $policy);
            $registered[$model] = $policy;
        }

        return $registered;
    }

    /**
     * The namespace a plugin was given, read from the current panel or the
     * default one: the plugin's getPolicyNamespace() when the panel carries
     * it, $default otherwise (no panel, no plugin there, or no such method).
     */
    public static function namespaceOf(string $pluginId, string $default = self::DEFAULT_NAMESPACE): string
    {
        try {
            $plugin = filament($pluginId);
        } catch (Throwable) {
            return $default;
        }

        if (! method_exists($plugin, 'getPolicyNamespace')) {
            return $default;
        }

        $namespace = $plugin->getPolicyNamespace();

        return is_string($namespace) && $namespace !== '' ? $namespace : $default;
    }
}
