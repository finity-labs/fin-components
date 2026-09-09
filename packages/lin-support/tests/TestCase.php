<?php

declare(strict_types=1);

namespace FinityLabs\LinSupport\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Package test harness. lin-support registers no provider: it is plain
 * classes and one console trait, so Testbench's bare application is enough,
 * and the fixture command in tests/Fixtures is registered by the tests that
 * run it.
 */
class TestCase extends Orchestra {}
