<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource\Pages;

use Filament\Resources\Pages\EditRecord;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource;

/**
 * The edit page of the fixture resource.
 */
final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}
