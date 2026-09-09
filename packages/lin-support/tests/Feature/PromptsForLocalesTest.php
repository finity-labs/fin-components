<?php

use FinityLabs\LinSupport\Tests\Fixtures\ConfigureLocalesCommand;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    Artisan::registerCommand(app(ConfigureLocalesCommand::class));
});

it('answers from the --locales option without a prompt', function (): void {
    $this->artisan('fixture:locales', ['--locales' => 'en, de'])
        ->expectsOutputToContain('Locales from --locales option: en, de')
        ->expectsOutput('en|English|gb')
        ->expectsOutput('de|Deutsch|de')
        ->assertSuccessful();
});

it('drops unknown codes from the option with a warning', function (): void {
    $this->artisan('fixture:locales', ['--locales' => 'en,xx,de'])
        ->expectsOutputToContain('Unknown locale codes ignored: xx')
        ->expectsOutput('en|English|gb')
        ->expectsOutput('de|Deutsch|de')
        ->doesntExpectOutputToContain('xx|')
        ->assertSuccessful();
});

it('folds duplicates from the option', function (): void {
    $this->artisan('fixture:locales', ['--locales' => 'de,de,en'])
        ->expectsOutput('de|Deutsch|de')
        ->expectsOutput('en|English|gb')
        ->assertSuccessful();
});
