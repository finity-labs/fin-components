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

## Livewire >= 4.4.6 turns ModelNotFoundException into a 404 in component tests
Since Livewire 4.4.6 (PR livewire/livewire#10720), the test request broker hands `ModelNotFoundException` to Laravel's exception handler, so `Livewire::test()` of a mount or action that fails a `findOrFail`/scoped `resolveRecord` returns a 404 response instead of letting the exception bubble. A `->toThrow(ModelNotFoundException::class)` expectation then fails with "no exception thrown" while the same file passes on Livewire <= 4.4.5 and on Livewire 3.

Either assert `->assertNotFound()` (4.4.6+ only) or call `$this->withoutExceptionHandling()` first, which restores the rethrow on every Livewire version — fin-codex's `ResourceOverrideTest` does the latter.

Related debugging trap: a `vendor/bin/pest ... | tail` pipeline reports tail's exit code, not pest's. Use the raw exit code or grep the output for `FAILED` when scripting pass/fail checks.
