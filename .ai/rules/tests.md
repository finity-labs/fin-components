---
paths:
  - 'packages/*/tests/**'
---

# Tests

## Clear Livewire's back-button-cache static between tests
Livewire keeps `SupportDisablingBackButtonCache::$disableBackButtonCache` as a public static and stamps `no-cache, no-store, max-age=0, private` from GLOBAL middleware (`$kernel->pushMiddleware()`), so it never shows up in a route's own middleware list.

A component that redirects sets the flag. A `Livewire::test()` call never sends its response through the HTTP kernel, so the flag survives into the next request the process makes and brands whatever response comes next. Under random test order the victim is arbitrary — in lin-codex it turned the stylesheet route's cache-header assertion red on CI while every local run passed.

Clear it as each test app boots (lin-codex does this in `tests/TestCase.php::defineEnvironment()`, guarded by `class_exists` so Livewire 3 and 4 both work).

Related trap: `phpunit.xml.dist` sets `executionOrder="random"` with a clock-derived seed, so a local gate run and a CI job never share an order. Reproduce a CI ordering failure with `vendor/bin/pest --order-by=random --random-order-seed=<seed>` using the seed printed at the bottom of the CI job.
