<?php

declare(strict_types=1);

namespace Tipowerup\Testbench\Concerns;

use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Classes\HubManager;
use Igniter\System\Classes\PackageManifest;
use Igniter\System\Classes\UpdateManager;
use Mockery;
use Mockery\MockInterface;

/**
 * @mixin \Tipowerup\Testbench\TestCase
 */
trait MocksTIServices
{
    /**
     * Create a mock ExtensionManager with sensible defaults.
     *
     * @param  array<string, mixed>  $overrides  Method stubs to override defaults.
     */
    protected function mockExtensionManager(array $overrides = []): MockInterface
    {
        $mock = Mockery::mock(ExtensionManager::class);

        $defaults = [
            'hasExtension' => true,
            'getExtension' => null,
            'getExtensions' => [],
            'installExtension' => true,
            'uninstallExtension' => true,
            'enableExtension' => true,
            'disableExtension' => true,
            'isDisabled' => false,
            'purgeExtension' => true,
        ];

        foreach (array_merge($defaults, $overrides) as $method => $returnValue) {
            $mock->shouldReceive($method)->andReturn($returnValue)->byDefault();
        }

        $this->app->instance(ExtensionManager::class, $mock);

        return $mock;
    }

    /**
     * Create a mock HubManager (theme management) with sensible defaults.
     *
     * @param  array<string, mixed>  $overrides  Method stubs to override defaults.
     */
    protected function mockHubManager(array $overrides = []): MockInterface
    {
        $mock = Mockery::mock(HubManager::class);

        $defaults = [
            'getDetails' => [],
            'applyItems' => true,
            'getInstalledItems' => [],
        ];

        foreach (array_merge($defaults, $overrides) as $method => $returnValue) {
            $mock->shouldReceive($method)->andReturn($returnValue)->byDefault();
        }

        $this->app->instance(HubManager::class, $mock);

        return $mock;
    }

    /**
     * Create a mock UpdateManager with sensible defaults.
     *
     * @param  array<string, mixed>  $overrides  Method stubs to override defaults.
     */
    protected function mockUpdateManager(array $overrides = []): MockInterface
    {
        $mock = Mockery::mock(UpdateManager::class);

        $defaults = [
            'migrate' => null,
            'purge' => null,
            'rollback' => null,
            'isLastCheckDue' => false,
        ];

        foreach (array_merge($defaults, $overrides) as $method => $returnValue) {
            $mock->shouldReceive($method)->andReturn($returnValue)->byDefault();
        }

        $this->app->instance(UpdateManager::class, $mock);

        return $mock;
    }

    /**
     * Create a mock PackageManifest with sensible defaults.
     *
     * @param  array<string, mixed>  $overrides  Method stubs to override defaults.
     */
    protected function mockPackageManifest(array $overrides = []): MockInterface
    {
        $mock = Mockery::mock(PackageManifest::class);

        $defaults = [
            'extensions' => [],
            'themes' => [],
            'packages' => [],
            'build' => null,
        ];

        foreach (array_merge($defaults, $overrides) as $method => $returnValue) {
            $mock->shouldReceive($method)->andReturn($returnValue)->byDefault();
        }

        $this->app->instance(PackageManifest::class, $mock);

        return $mock;
    }

    /**
     * Create a mock for any service class and bind it to the container.
     *
     * @param  class-string  $class  The class to mock.
     * @param  array<string, mixed>  $stubs  Method stubs.
     */
    protected function mockService(string $class, array $stubs = []): MockInterface
    {
        $mock = Mockery::mock($class);

        foreach ($stubs as $method => $returnValue) {
            $mock->shouldReceive($method)->andReturn($returnValue)->byDefault();
        }

        $this->app->instance($class, $mock);

        return $mock;
    }
}
