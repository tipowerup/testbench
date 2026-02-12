<?php

declare(strict_types=1);

namespace Tipowerup\Testbench;

use Igniter\Flame\ServiceProvider as FlameServiceProvider;
use Igniter\Flame\Support\Facades\Igniter;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Whether to run TI core system migrations in setUp().
     * Set to false in subclasses that don't need TI core tables.
     */
    protected bool $runCoreMigrations = true;

    private ?string $tempDir = null;

    /**
     * Get the absolute path to the extension's root directory.
     */
    abstract protected function getExtensionBasePath(): string;

    /**
     * Get the extension's service provider class(es) to register.
     *
     * @return array<int, class-string>
     */
    abstract protected function getExtensionProviders(): array;

    /**
     * Determine the current testbench mode.
     */
    protected function getTestbenchMode(): string
    {
        return $_ENV['TI_TESTBENCH_MODE'] ?? 'standalone';
    }

    /**
     * Get the host TI root path (available in host mode).
     */
    protected function getHostRoot(): ?string
    {
        return $_ENV['TI_TESTBENCH_HOST_ROOT'] ?? null;
    }

    /**
     * Get package providers for Orchestra Testbench.
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return array_merge(
            [FlameServiceProvider::class],
            $this->getExtensionProviders(),
        );
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // SQLite :memory: database for test isolation
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Array cache driver
        $app['config']->set('cache.default', 'array');

        // TI route config
        $app['config']->set('igniter-routes.adminUri', '/admin');

        // TI system config — point extensions/themes to temp dirs
        $tempDir = $this->getTempDir();
        $app['config']->set('igniter-system.extensionsPath', $tempDir.'/extensions');
        $app['config']->set('igniter-system.themesPath', $tempDir.'/themes');
        $app['config']->set('igniter-system.tempPath', $tempDir.'/temp');

        // Prevent extension filesystem scanning
        Igniter::autoloadExtensions(false);
    }

    /**
     * Run TI core system migrations to set up required tables
     * (settings, extensions, extension_settings, etc.).
     *
     * Only runs system migrations by default. Admin migrations (orders,
     * menus, locations) contain SQLite-incompatible operations. Extensions
     * that need admin tables should call runTiAdminMigrations() explicitly.
     */
    protected function runTiCoreMigrations(): void
    {
        $corePath = $this->resolveTiCorePath();

        if ($corePath === null) {
            $this->markTestSkipped('TastyIgniter core not found. Core migrations cannot run.');
        }

        $systemDir = $corePath.'/database/migrations/system';
        if (is_dir($systemDir)) {
            $this->loadMigrationsFrom($systemDir);
        }
    }

    /**
     * Run TI admin migrations (orders, menus, locations, etc.).
     *
     * Note: Some admin migrations use column modifications that are
     * incompatible with SQLite. Use with caution in :memory: databases.
     */
    protected function runTiAdminMigrations(): void
    {
        $corePath = $this->resolveTiCorePath();

        if ($corePath === null) {
            $this->markTestSkipped('TastyIgniter core not found. Admin migrations cannot run.');
        }

        $adminDir = $corePath.'/database/migrations/admin';
        if (is_dir($adminDir)) {
            $this->loadMigrationsFrom($adminDir);
        }
    }

    /**
     * Resolve the path to tastyigniter/core package.
     */
    protected function resolveTiCorePath(): ?string
    {
        $cwd = getcwd();

        // Check CWD's vendor first
        if ($cwd !== false) {
            $path = $cwd.'/vendor/tastyigniter/core';
            if (is_dir($path)) {
                return $path;
            }
        }

        // Check extension's own vendor
        $path = $this->getExtensionBasePath().'/vendor/tastyigniter/core';
        if (is_dir($path)) {
            return $path;
        }

        // In host mode, check the host project's vendor
        $hostRoot = $this->getHostRoot();
        if ($hostRoot !== null) {
            $path = $hostRoot.'/vendor/tastyigniter/core';
            if (is_dir($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Run extension-specific migrations.
     */
    protected function runExtensionMigrations(string $path): void
    {
        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->runCoreMigrations) {
            $this->runTiCoreMigrations();
        }
    }

    /**
     * Clean up temp directories after tests.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $tempDir = $this->getTempDir();
        if (is_dir($tempDir)) {
            $this->removeDirectory($tempDir);
        }
    }

    /**
     * Get the temp directory path for this test process.
     */
    private function getTempDir(): string
    {
        if ($this->tempDir === null) {
            $this->tempDir = sys_get_temp_dir().'/ti-testbench-'.getmypid().'-'.spl_object_id($this);
        }

        return $this->tempDir;
    }

    /**
     * Recursively remove a directory.
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $path = $item->getPathname();
            if ($item->isDir() && !$item->isLink()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
