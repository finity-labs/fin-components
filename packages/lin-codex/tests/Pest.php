<?php

declare(strict_types=1);

use FinityLabs\LinCodex\Tests\CustomApiPrefixTestCase;
use FinityLabs\LinCodex\Tests\CustomHelpCenterTestCase;
use FinityLabs\LinCodex\Tests\CustomTableNamesTestCase;
use FinityLabs\LinCodex\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;

uses(TestCase::class)->in('Unit', 'Feature/Migrations', 'Feature/Models', 'Feature/Rendering', 'Feature/Http', 'Feature/Settings', 'Feature/Sources', 'Feature/Auth', 'Feature/Contexts', 'Feature/Locale', 'Feature/Reading', 'Feature/Search', 'Feature/Api', 'Feature/Stubs', 'Feature/Livewire', 'Feature/Views');
uses(CustomTableNamesTestCase::class)->in('Feature/CustomTableNames');
uses(CustomApiPrefixTestCase::class)->in('Feature/CustomApiPrefix');
uses(CustomHelpCenterTestCase::class)->in('Feature/CustomHelpCenter');

/**
 * Walk a value recursively and fail on any Eloquent model, any closure or
 * any object that is not a readonly class; enums, scalars and null pass.
 * Shared by every source test so a model can never leak through the
 * ContentSource contract.
 */
function linCodexAssertNoModels(mixed $value): void
{
    if (is_array($value)) {
        foreach ($value as $item) {
            linCodexAssertNoModels($item);
        }

        return;
    }

    if (! is_object($value) || $value instanceof UnitEnum) {
        return;
    }

    expect($value)->not->toBeInstanceOf(Model::class)
        ->and($value)->not->toBeInstanceOf(Closure::class)
        ->and((new ReflectionClass($value))->isReadOnly())->toBeTrue($value::class.' is not readonly');

    foreach ((new ReflectionObject($value))->getProperties() as $property) {
        linCodexAssertNoModels($property->getValue($value));
    }
}
