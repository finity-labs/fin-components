<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Helpers;

use Stringable;

/**
 * Simple value wrapper for passing non-model values to the token replacer.
 *
 * Usage:
 *   ->models([
 *       'user' => $user,
 *       'url'  => new TokenValue($verificationUrl),
 *   ])
 *
 * In template: {{ url }} will be replaced with the value.
 */
class TokenValue implements Stringable
{
    public function __construct(
        protected readonly mixed $value,
    ) {}

    public static function make(mixed $value): static
    {
        return new static($value);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function __get(string $name): mixed
    {
        if ($name === 'value') {
            return $this->value;
        }

        return null;
    }
}
