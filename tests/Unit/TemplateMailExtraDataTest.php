<?php

declare(strict_types=1);

use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Models\EmailTemplate;

beforeEach(function () {
    EmailTemplate::create([
        'key' => 'extra-data-test',
        'name' => ['en' => 'Extra Data Test'],
        'category' => 'transactional',
        'subject' => ['en' => 'Hello'],
        'body' => ['en' => '<p>Body</p>'],
        'is_active' => true,
    ]);
});

it('passes extra data via with() to the view', function () {
    $mail = TemplateMail::make('extra-data-test')
        ->with('customVar', 'hello world');

    expect($mail->viewData)->toHaveKey('customVar')
        ->and($mail->viewData['customVar'])->toBe('hello world');
});

it('passes extra data via extraData() alias to the view', function () {
    $mail = TemplateMail::make('extra-data-test')
        ->extraData([
            'foo' => 'bar',
            'count' => 42,
        ]);

    expect($mail->viewData)->toHaveKey('foo')
        ->and($mail->viewData['foo'])->toBe('bar')
        ->and($mail->viewData['count'])->toBe(42);
});

it('returns the mailable instance for chaining', function () {
    $mail = TemplateMail::make('extra-data-test');

    expect($mail->extraData(['key' => 'value']))->toBe($mail)
        ->and($mail->with('another', 'value'))->toBe($mail);
});
