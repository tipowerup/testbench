<?php

declare(strict_types=1);

namespace Tipowerup\Testbench\Tests;

use Tipowerup\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The testbench package doesn't have its own extension — return its root.
     */
    protected function getExtensionBasePath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * No extension providers needed for self-testing.
     *
     * @return array<int, class-string>
     */
    protected function getExtensionProviders(): array
    {
        return [];
    }
}
