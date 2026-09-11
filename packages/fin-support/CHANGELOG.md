# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.1] - 2026-09-11

### Fixed

- `Panel\Concerns\ResolvesPanelUser::panelUserId()` answered null on a host whose user model uses `HasUuids` or `HasUlids`, because it narrowed `Filament::auth()->id()` to `?int` and threw a string key away. Every package that stores the author of a row recorded nobody. It returns `int|string|null` now and hands the key over as the model gives it; anything that is neither an int nor a non-empty string is still null. Widen your own `?int` declarations if you assign the result

### Added

- `Panel\PanelUser::id()`: the same answer as a static call, for the static closures of a schema or an action where there is no `$this` to take the trait method from. `ResolvesPanelUser` delegates to it, so there is one implementation

## [0.1.0] - 2026-09-09

First release: the pieces every Finity Labs Filament plugin repeated.

### Added

- `Pages\Concerns\HasPageShieldSupport`: page access through Filament Shield's permission when Shield is installed, a `page_{ClassBasename}` Gate ability when it is not, and an overridable `canAccessFallback()` otherwise. Lifted from fin-mail, fin-codex and fin-sentinel, with fin-sentinel's plugin-option fallback as the seam.
- `Auth\PolicyRegistrar`: maps `{namespace}\{Basename}` policies onto a package's models, with optional shipped fallbacks, and reads the namespace off a panel plugin's `policyNamespace()` option.
- `Console\Concerns\DiscoversPanelProviders`, `EditsPanelProviders` and `EditsShieldConfig`: the installer edits — find the host's panel providers, register or deregister a plugin in one, write a package's resources into the Shield config and take them out again.
- `Panel\Concerns\ResolvesPanelUser`: the panel user's id narrowed to `?int`.
