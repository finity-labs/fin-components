---
paths:
  - 'packages/*/src/**'
---

# Src

## Docblocks are src to the standing greps
The standing greps that guard these packages (`grep -rn "authorize('" src/`, `grep -rn "InstalledVersions" src/`, `grep -rn 'Laravel\Ai' src/`) run over the raw file text and do not know what a comment is. A docblock that spells out the banned form to explain why the code avoids it will match and fail the gate.

Reword the sentence instead of quoting the banned form: say "the closure form, never the string form" rather than writing `authorize('...')`, and name the SDK in prose rather than by class. Four separate Phase 10 executors tripped this independently and each had to reword after the fact.

## Never put PHP objects in the cache; store toArray() and rehydrate
Laravel 13's skeleton ships `config/cache.php` with `'serializable_classes' => false`, and every serializing store (redis, file, database, memcached, dynamodb) then reads with `unserialize($value, ['allowed_classes' => false])`, so a cached object comes back as `__PHP_Incomplete_Class`. Laravel 12.x honours the key too when an app sets it. Our dev apps were bootstrapped on older skeletons and lack the key (null = unrestricted), so the breakage only shows in consumers. lin-codex 0.4.3 hit this three times at once: the render cache threw a `TypeError` on every hit, the file source never got a hit, and the search index came back empty.

Anything handed to `Cache::put`/`forever`/`remember` must be arrays and scalars only. Give the value object `toArray()`/`fromArray()` (enums as backing values; `FinityLabs\LinCodex\Data\Shape` is the typed reader), validate the shape on read, and treat anything else as a miss to rebuild and overwrite under the same key, never throw. Do not swap in `json_encode` strings: that only nests JSON inside the store's own serialization. Regression tests run the cache through `['driver' => 'array', 'serialize' => true]` with `cache.serializable_classes` false and walk the raw entry with `linCodexAssertPlainData()` (lin-codex `tests/Pest.php`).

## A secret in a spatie settings class is declared encrypted twice and proven by a test
Only two secrets are stored anywhere in these packages: `lin-codex-ai.api_key` and `fin-sentinel.ai_api_key`, both spatie/laravel-settings properties. Declare such a property encrypted through the `#[ShouldBeEncrypted]` attribute **and** the `public static function encrypted(): array` method: spatie merges both lists, the method works on every release, the attribute only from 3.7.2, and a host on an older release silently writes plain text otherwise (that happened; see the `encrypt_*_api_key` settings migrations, which repair such rows in place by trying `Crypto::decrypt` first). Seed the row with `addEncrypted()`. Every package that owns a secret keeps a test that saves through the class, reads the raw `settings.payload` and asserts the plain text is absent and a fresh instance decrypts it. Prefer no stored secret at all: both AI features treat a null key as "use the SDK's key from `config/ai.php`", and the docs say so.
