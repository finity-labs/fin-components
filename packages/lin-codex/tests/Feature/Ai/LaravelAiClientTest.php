<?php

declare(strict_types=1);

use FinityLabs\LinCodex\Ai\AiCallFailed;
use FinityLabs\LinCodex\Ai\LaravelAiClient;
use FinityLabs\LinCodex\Ai\StructuredRequest;
use FinityLabs\LinCodex\Ai\TranslationAgent;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Laravel\Ai\Ai;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Step;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StructuredTextResponse;

/** The three fields every translation call asks for. */
function linCodexSeamFields(): array
{
    return ['title' => 'The title', 'excerpt' => 'The excerpt', 'body' => 'The body'];
}

function linCodexSeamRequest(
    string $provider = 'anthropic',
    ?string $model = null,
    int $timeout = 120,
    ?string $apiKey = null,
): StructuredRequest {
    return new StructuredRequest(
        instructions: 'Be formal.',
        prompt: "Translate.\n<source_body>x</source_body>",
        fields: linCodexSeamFields(),
        provider: $provider,
        model: $model,
        timeout: $timeout,
        apiKey: $apiKey,
    );
}

function linCodexSeamHttpException(int $status): RequestException
{
    return new RequestException(new Response(new PsrResponse($status)));
}

it('answers unavailable everywhere without the SDK', function (): void {
    $client = app(LaravelAiClient::class);

    expect($client->installed())->toBeFalse()
        ->and($client->providers())->toBe([])
        ->and($client->testConnection('anthropic', null, 'sk'))->toBe('unavailable');

    expect(fn () => $client->tierModels('anthropic'))
        ->toThrow(AiCallFailed::class, 'AI call failed: unavailable');

    expect(fn () => $client->structured(new StructuredRequest('i', 'p', ['title' => 'd'], 'anthropic', null, 30, null)))
        ->toThrow(AiCallFailed::class, 'AI call failed: unavailable');
})->skip(fn (): bool => class_exists('Laravel\\Ai\\AnonymousAgent'), 'laravel/ai is installed');

it('maps :dataset to a reason key', function (Closure $throwable, string $reason): void {
    expect(app(LaravelAiClient::class)->reason($throwable()))->toBe($reason);
})->with([
    'a bare connection exception' => [fn (): Throwable => new ConnectionException('cURL error 28: timed out'), 'timeout'],
    'HTTP 401' => [fn (): Throwable => linCodexSeamHttpException(401), 'authentication_failed'],
    'HTTP 403' => [fn (): Throwable => linCodexSeamHttpException(403), 'authentication_failed'],
    'HTTP 429' => [fn (): Throwable => linCodexSeamHttpException(429), 'rate_limited'],
    'HTTP 402' => [fn (): Throwable => linCodexSeamHttpException(402), 'quota_exceeded'],
    'HTTP 503' => [fn (): Throwable => linCodexSeamHttpException(503), 'unavailable'],
    'HTTP 400' => [fn (): Throwable => linCodexSeamHttpException(400), 'unknown'],
    'no provider configured' => [fn (): Throwable => new RuntimeException('No AI providers were configured.'), 'unavailable'],
    'an invalid argument' => [fn (): Throwable => new InvalidArgumentException('x'), 'unavailable'],
    'a logic error' => [fn (): Throwable => new LogicException('x'), 'unavailable'],
    'an unrelated runtime error' => [fn (): Throwable => new RuntimeException('boom'), 'unknown'],
    'a failure that already carries a reason' => [fn (): Throwable => new AiCallFailed('rate_limited'), 'rate_limited'],
]);

describe('with the SDK', function (): void {
    beforeEach(function (): void {
        if (! class_exists('Laravel\\Ai\\AnonymousAgent')) {
            $this->markTestSkipped('laravel/ai is not installed');
        }

        config(['ai.providers.anthropic.key' => null]);
    });

    it('lists the offered providers with the Lab case names', function (): void {
        expect(app(LaravelAiClient::class)->providers())->toBe([
            'anthropic' => 'Anthropic',
            'openai' => 'OpenAI',
            'gemini' => 'Gemini',
            'mistral' => 'Mistral',
            'groq' => 'Groq',
            'deepseek' => 'DeepSeek',
            'xai' => 'xAI',
            'openrouter' => 'OpenRouter',
            'ollama' => 'Ollama',
        ]);
    });

    it('reads the tier models from the provider', function (): void {
        $provider = Ai::textProvider('openai');

        expect(app(LaravelAiClient::class)->tierModels('openai'))->toBe([
            'default' => $provider->defaultTextModel(),
            'cheapest' => $provider->cheapestTextModel(),
            'smartest' => $provider->smartestTextModel(),
        ]);

        expect(fn () => app(LaravelAiClient::class)->tierModels('azure'))
            ->toThrow(AiCallFailed::class, 'AI call failed: unavailable');
    });

    it('injects the stored key for one call and restores the config', function (): void {
        $seen = [];

        TranslationAgent::fake(function (string $prompt, $attachments, $provider, string $model) use (&$seen): array {
            $seen = [
                'key' => $provider->providerCredentials()['key'],
                'provider' => $provider->name(),
                'model' => $model,
            ];

            return ['title' => 'Titel', 'excerpt' => '', 'body' => 'Text'];
        })->preventStrayPrompts();

        $completion = app(LaravelAiClient::class)->structured(
            linCodexSeamRequest(model: 'claude-haiku-4-5-20251001', apiKey: 'sk-stored'),
        );

        expect($seen)->toBe([
            'key' => 'sk-stored',
            'provider' => 'anthropic',
            'model' => 'claude-haiku-4-5-20251001',
        ])
            ->and(config('ai.providers.anthropic.key'))->toBeNull()
            ->and($completion->fields)->toBe(['title' => 'Titel', 'excerpt' => '', 'body' => 'Text'])
            ->and($completion->truncated)->toBeFalse();

        TranslationAgent::assertPrompted(fn (AgentPrompt $p): bool => $p->timeout === 120
            && str_contains($p->prompt, '<source_body>')
            && $p->agent->instructions() === 'Be formal.'
            && $p->agent->maxTokens() === 16000);
        TranslationAgent::assertPromptedTimes(1);
    });

    it('uses the SDK env key when no key is stored', function (): void {
        config(['ai.providers.anthropic.key' => 'sk-env']);

        $seen = null;

        TranslationAgent::fake(function (string $prompt, $attachments, $provider, string $model) use (&$seen): array {
            $seen = $provider->providerCredentials()['key'];

            return ['title' => 'Titel', 'excerpt' => '', 'body' => 'Text'];
        })->preventStrayPrompts();

        app(LaravelAiClient::class)->structured(linCodexSeamRequest(model: 'claude-haiku-4-5-20251001'));

        expect($seen)->toBe('sk-env')
            ->and(config('ai.providers.anthropic.key'))->toBe('sk-env');
    });

    it('reads the usage and the truncation flag', function (): void {
        TranslationAgent::fake(function (string $prompt, $attachments, $provider, string $model): StructuredTextResponse {
            $fields = ['title' => 'Titel', 'excerpt' => '', 'body' => 'Text'];

            return new StructuredTextResponse(
                $fields,
                (string) json_encode($fields),
                new Usage(promptTokens: 120, completionTokens: 80),
                new Meta($provider->name(), $model),
            );
        })->preventStrayPrompts();

        $completion = app(LaravelAiClient::class)->structured(linCodexSeamRequest(model: 'claude-haiku-4-5-20251001'));

        expect($completion->promptTokens)->toBe(120)
            ->and($completion->completionTokens)->toBe(80)
            ->and($completion->truncated)->toBeFalse();

        TranslationAgent::fake(function (string $prompt, $attachments, $provider, string $model): StructuredTextResponse {
            $fields = ['title' => 'Titel', 'excerpt' => '', 'body' => 'Halb'];

            $response = new StructuredTextResponse(
                $fields,
                (string) json_encode($fields),
                new Usage(promptTokens: 10, completionTokens: 20),
                new Meta($provider->name(), $model),
            );

            $response->steps = new Collection([
                new Step('', [], [], FinishReason::Length, new Usage, new Meta($provider->name(), $model)),
            ]);

            return $response;
        })->preventStrayPrompts();

        expect(app(LaravelAiClient::class)->structured(linCodexSeamRequest(model: 'claude-haiku-4-5-20251001'))->truncated)
            ->toBeTrue();
    });

    it('honours lin-codex.ai.max_tokens', function (): void {
        config(['lin-codex.ai.max_tokens' => 4000]);

        TranslationAgent::fake(fn (): array => ['title' => 'Titel', 'excerpt' => '', 'body' => 'Text'])
            ->preventStrayPrompts();

        app(LaravelAiClient::class)->structured(linCodexSeamRequest(model: 'claude-haiku-4-5-20251001'));

        TranslationAgent::assertPrompted(fn (AgentPrompt $p): bool => $p->agent->maxTokens() === 4000);
    });

    it('maps :dataset thrown by the SDK to a reason key', function (Closure $throw, string $reason): void {
        config(['ai.providers.anthropic.key' => null]);

        TranslationAgent::fake($throw)->preventStrayPrompts();

        try {
            app(LaravelAiClient::class)->structured(
                linCodexSeamRequest(model: 'claude-haiku-4-5-20251001', apiKey: 'sk-stored'),
            );
            $this->fail('the SDK exception did not surface');
        } catch (AiCallFailed $e) {
            expect($e->reason)->toBe($reason)
                ->and($e->getPrevious())->not->toBeNull()
                ->and(config('ai.providers.anthropic.key'))->toBeNull();
        }
    })->with([
        'a rate limit' => [fn (): Closure => fn () => throw RateLimitedException::forProvider('anthropic'), 'rate_limited'],
        'an empty credit balance' => [fn (): Closure => fn () => throw InsufficientCreditsException::forProvider('anthropic'), 'quota_exceeded'],
        'an overloaded provider' => [fn (): Closure => fn () => throw ProviderOverloadedException::forProvider('anthropic'), 'unavailable'],
        'a connection that timed out' => [fn (): Closure => fn () => throw ProviderConnectionException::forProvider('anthropic', 0, new ConnectionException('cURL error 28: Operation timed out')), 'timeout'],
        'a refused connection' => [fn (): Closure => fn () => throw ProviderConnectionException::forProvider('anthropic', 0, new ConnectionException('Connection refused')), 'unavailable'],
    ]);

    it('refuses a provider that is not offered before prompting', function (): void {
        TranslationAgent::fake(fn (): array => ['title' => 'Titel'])->preventStrayPrompts();

        expect(fn () => app(LaravelAiClient::class)->structured(linCodexSeamRequest(provider: 'azure')))
            ->toThrow(AiCallFailed::class, 'AI call failed: unavailable');

        TranslationAgent::assertPromptedTimes(0);
    });

    it('tests the connection through the anonymous agent', function (): void {
        AnonymousAgent::fake(['OK']);

        expect(app(LaravelAiClient::class)->testConnection('anthropic', null, 'sk-stored'))->toBeNull();

        AnonymousAgent::assertPrompted(fn (AgentPrompt $p): bool => $p->prompt === 'Reply with the single word OK.'
            && $p->timeout === 10);

        AnonymousAgent::fake(fn () => throw RateLimitedException::forProvider('anthropic'));

        expect(app(LaravelAiClient::class)->testConnection('anthropic', null, 'sk-stored'))->toBe('rate_limited');
    });
});
