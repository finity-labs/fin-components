<?php

declare(strict_types=1);

use FinityLabs\FinSupport\Panel\Concerns\ResolvesPanelUser;
use FinityLabs\FinSupport\Tests\Fixtures\User;

it('narrows the panel user id to an int, and to null for nobody', function (): void {
    $resolver = new class
    {
        use ResolvesPanelUser;

        public function id(): ?int
        {
            return $this->panelUserId();
        }
    };

    expect($resolver->id())->toBeNull();

    $user = User::create(['name' => 'Tester', 'email' => 'tester@example.com']);
    $this->usesPanel('admin', $user);

    expect($resolver->id())->toBe($user->id);
});
