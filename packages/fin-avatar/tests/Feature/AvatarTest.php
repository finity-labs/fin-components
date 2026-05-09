<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('renders avatar with custom colors provided in url', function () {
    $bg = urlencode('#ff0000');

    get(route('fin-avatar.render', [
        'initials' => 'FL',
        'bg' => $bg,
    ]))
        ->assertOk()
        ->assertSee('#ff0000');
});

it('renders avatar with default colors when parameters missing', function () {
    config()->set('fin-avatar.default_bg', '#1f2937');

    get(route('fin-avatar.render', ['initials' => 'FL']))
        ->assertOk()
        ->assertSee('#1f2937');
});
