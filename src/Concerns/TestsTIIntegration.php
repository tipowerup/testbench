<?php

declare(strict_types=1);

namespace Tipowerup\Testbench\Concerns;

use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Models\Settings;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Assert;

trait TestsTIIntegration
{
    /**
     * Assert that params() can store and retrieve a value.
     */
    protected function assertParamsWork(string $key, mixed $value): void
    {
        Settings::set($key, $value, 'prefs');
        $retrieved = Settings::get($key, null, 'prefs');

        Assert::assertEquals(
            $value,
            $retrieved,
            "Expected params('{$key}') to return the stored value.",
        );
    }

    /**
     * Assert that an extension is registered with ExtensionManager.
     */
    protected function assertExtensionRegistered(string $code): void
    {
        $manager = resolve(ExtensionManager::class);

        Assert::assertTrue(
            $manager->hasExtension($code),
            "Expected extension '{$code}' to be registered.",
        );
    }

    /**
     * Assert that an extension can be installed, uninstalled, and reinstalled.
     */
    protected function assertTIInstallCycle(string $code): void
    {
        $manager = resolve(ExtensionManager::class);

        // Install
        $manager->installExtension($code);
        Assert::assertFalse(
            $manager->isDisabled($code),
            "Expected extension '{$code}' to be enabled after install.",
        );

        // Uninstall (disable)
        $manager->disableExtension($code);
        Assert::assertTrue(
            $manager->isDisabled($code),
            "Expected extension '{$code}' to be disabled after uninstall.",
        );

        // Reinstall (enable)
        $manager->enableExtension($code);
        Assert::assertFalse(
            $manager->isDisabled($code),
            "Expected extension '{$code}' to be enabled after reinstall.",
        );
    }

    /**
     * Assert that purging an extension drops its tables.
     *
     * @param  array<int, string>  $tables  Tables the extension creates.
     */
    protected function assertCleanPurge(string $code, array $tables): void
    {
        $manager = resolve(ExtensionManager::class);

        $manager->installExtension($code);

        foreach ($tables as $table) {
            Assert::assertTrue(
                Schema::hasTable($table),
                "Expected table '{$table}' to exist after installing '{$code}'.",
            );
        }

        $manager->purgeExtension($code);

        foreach ($tables as $table) {
            Assert::assertFalse(
                Schema::hasTable($table),
                "Expected table '{$table}' to be dropped after purging '{$code}'.",
            );
        }
    }

    /**
     * Assert that an extension registers navigation items correctly.
     *
     * @param  class-string  $extensionClass  The extension class to inspect.
     * @param  string  $parent  The parent navigation key (e.g., 'tools').
     * @param  string  $child  The child navigation key (e.g., 'installer').
     */
    protected function assertNavigationRegistered(string $extensionClass, string $parent, string $child): void
    {
        /** @var \Igniter\System\Classes\BaseExtension $extension */
        $extension = resolve($extensionClass);
        $navigation = $extension->registerNavigation();

        Assert::assertArrayHasKey(
            $parent,
            $navigation,
            "Expected navigation parent '{$parent}' to be registered.",
        );

        Assert::assertArrayHasKey(
            'child',
            $navigation[$parent],
            "Expected navigation parent '{$parent}' to have children.",
        );

        Assert::assertArrayHasKey(
            $child,
            $navigation[$parent]['child'],
            "Expected navigation child '{$child}' under parent '{$parent}'.",
        );
    }

    /**
     * Assert that an extension registers the expected permissions.
     *
     * @param  class-string  $extensionClass  The extension class to inspect.
     * @param  array<int, string>  $permissions  Expected permission codes.
     */
    protected function assertPermissionsRegistered(string $extensionClass, array $permissions): void
    {
        /** @var \Igniter\System\Classes\BaseExtension $extension */
        $extension = resolve($extensionClass);
        $registered = $extension->registerPermissions();

        foreach ($permissions as $permission) {
            Assert::assertArrayHasKey(
                $permission,
                $registered,
                "Expected permission '{$permission}' to be registered.",
            );
        }
    }
}
