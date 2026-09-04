<?php

declare(strict_types=1);

use FinityLabs\LinCodex\Tests\CustomTableNamesTestCase;
use FinityLabs\LinCodex\Tests\TestCase;

uses(TestCase::class)->in('Unit', 'Feature/Migrations', 'Feature/Models', 'Feature/Settings');
uses(CustomTableNamesTestCase::class)->in('Feature/CustomTableNames');
