<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Unit;

use FinityLabs\FinSupport\Panel\Concerns\ResolvesPanelUser;
use FinityLabs\FinSupport\Panel\PanelUser;
use FinityLabs\FinSupport\Tests\Fixtures\UuidUser;
use FinityLabs\FinSupport\Tests\UuidUserTestCase;

class UuidPanelUserTest extends UuidUserTestCase
{
    public function test_the_panel_user_id_is_the_uuid_string_a_string_keyed_host_signs_in_with(): void
    {
        $resolver = $this->resolver();

        $this->assertNull($resolver->id());

        $user = UuidUser::create(['name' => 'Tester', 'email' => 'tester@example.com']);
        $this->usesPanel('admin', $user);

        $this->assertSame($user->getKey(), $resolver->id());
        $this->assertIsString($resolver->id());
    }

    public function test_the_static_helper_answers_the_same_uuid_outside_a_component(): void
    {
        $this->assertNull(PanelUser::id());

        $user = UuidUser::create(['name' => 'Tester', 'email' => 'tester@example.com']);
        $this->usesPanel('admin', $user);

        $this->assertSame($user->getKey(), PanelUser::id());
    }

    private function resolver(): object
    {
        return new class
        {
            use ResolvesPanelUser;

            public function id(): int|string|null
            {
                return $this->panelUserId();
            }
        };
    }
}
