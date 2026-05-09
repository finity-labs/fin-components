# Finity Labs Filament Components

Monorepo for Finity Labs' Filament packages. Each package can be installed individually.

| Package | Install |
|---|---|
| [Avatar](packages/fin-avatar) | `composer require finity-labs/fin-avatar` |
| [Mail](packages/fin-mail) | `composer require finity-labs/fin-mail` |
| [ModalTableSelect](packages/fin-modal-table-select) | `composer require finity-labs/fin-modal-table-select` |
| [Sentinel](packages/fin-sentinel) | `composer require finity-labs/fin-sentinel` |

See each package's README for documentation.

## Development

All package sources live under `packages/`. On tag push, each package is split out and pushed to its individual mirror repository, where Packagist picks it up.

To run a package's tests:

```bash
cd packages/fin-avatar
composer install
vendor/bin/pest
```

## License

MIT
