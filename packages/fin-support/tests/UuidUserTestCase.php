<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests;

use FinityLabs\FinSupport\Tests\Fixtures\UuidUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Boots the panel against a users table keyed by a UUID string.
 */
abstract class UuidUserTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('auth.providers.users.model', UuidUser::class);
    }

    protected function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
