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

## Testbench logs deprecations through the Log facade; a Log or Context spy then crashes on dependency noise
Orchestra Testbench's `HandleExceptions` overrides Laravel's and logs every deprecation to the `deprecations` channel while testing (its `shouldIgnoreDeprecationErrors()` only honours `LOG_DEPRECATIONS_WHILE_TESTING`, default on), so Laravel's own "ignore deprecations in unit tests" rule does not apply. Every Livewire 3.x release raises `Creation of dynamic property TestResponse::$original` from its test support on PHP 8.2+. With `Log::spy()` the spy answers `channel()` with null and the handler dies on `->warning()`; with `Context::spy()` the real channel's Context processor spreads a null `all()`. The error surfaces as `Call to a member function warning() on null` in `helpers.php` or `Only arrays and Traversables can be unpacked` in `LogManager`, far from the test. Set `<env name="LOG_DEPRECATIONS_WHILE_TESTING" value="false"/>` in the package's `phpunit.xml` (fin-codex does). Found by the lowest-deps leg at Testbench 9.5 + Livewire 3.5.

## Filament below 4.12.6 cannot narrow a list in an action test
`fillForm()`, which `callAction(..., data: [...])`, `callTableAction`, `callTableBulkAction` and `setActionData` all go through, pruned stale numeric keys with a path accumulator that grew inside its loop (fixed in 4.12.6, filamentphp/filament#20318). A `CheckboxList` with `->default([...])` therefore kept its default entries whatever `data:` said, and the action saw every option ticked. Direct `->set('mountedActions.0.data.key', ...)` was unaffected. fin-codex and fin-sentinel declare `filament/filament ^4.12.6|^5.0`; do not lower it.

## Laravel 11.28 (the illuminate ^11 floor) differs from 11.3x in three test APIs
`MailFake::assertSentTimes()` is protected there (use `Mail::assertSent($mailable, $n)`), `Illuminate\Http\Client\StrayRequestException` does not exist (it throws a bare `RuntimeException`; assert `RuntimeException::class` and the `without a matching fake` message, which newer versions also satisfy), and `laravel/prompts` is 0.1.24. The lowest-deps leg runs Laravel 11.28, so a test that only exists on 12 must gate on the runtime the way the code under test does (fin-sentinel's install tests use `finSentinelAiRuntimeSupported()`), not on `PHP_VERSION_ID` alone.
