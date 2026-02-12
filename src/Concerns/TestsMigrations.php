<?php

declare(strict_types=1);

namespace Tipowerup\Testbench\Concerns;

use DirectoryIterator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Assert;
use SplFileInfo;

trait TestsMigrations
{
    /**
     * Assert that running migrations creates the expected tables,
     * and rolling back removes them.
     *
     * @param  array<int, string>  $tables
     */
    protected function assertMigrationCycle(string $path, array $tables): void
    {
        $this->loadMigrationsFrom($path);
        $this->artisan('migrate', ['--database' => 'testing'])->run();

        foreach ($tables as $table) {
            Assert::assertTrue(
                Schema::hasTable($table),
                "Expected table '{$table}' to exist after migration.",
            );
        }

        $this->artisan('migrate:rollback', ['--database' => 'testing'])->run();

        foreach ($tables as $table) {
            Assert::assertFalse(
                Schema::hasTable($table),
                "Expected table '{$table}' to be removed after rollback.",
            );
        }
    }

    /**
     * Assert that migrations survive multiple up/down cycles without errors.
     *
     * @param  array<int, string>  $tables
     */
    protected function assertSurvivesInstallCycles(string $path, array $tables, int $cycles = 3): void
    {
        $this->loadMigrationsFrom($path);

        for ($i = 0; $i < $cycles; $i++) {
            $this->artisan('migrate', ['--database' => 'testing'])->run();

            foreach ($tables as $table) {
                Assert::assertTrue(
                    Schema::hasTable($table),
                    "Expected table '{$table}' to exist after migration cycle ".($i + 1).'.',
                );
            }

            $this->artisan('migrate:rollback', ['--database' => 'testing'])->run();
        }

        // Final install — tables should exist
        $this->artisan('migrate', ['--database' => 'testing'])->run();

        foreach ($tables as $table) {
            Assert::assertTrue(
                Schema::hasTable($table),
                "Expected table '{$table}' to exist after final migration.",
            );
        }
    }

    /**
     * Assert that migration files don't modify core TI tables.
     *
     * @param  array<int, string>  $coreTables  Core table names to check against.
     */
    protected function assertNoCoreTables(string $path, array $coreTables = []): void
    {
        $defaultCoreTables = [
            'orders', 'reservations', 'menus', 'categories', 'locations',
            'customers', 'users', 'staffs', 'pages', 'settings',
            'extensions', 'extension_settings', 'themes', 'languages',
            'currencies', 'countries', 'mail_templates', 'mail_layouts',
        ];

        $coreTables = array_merge($defaultCoreTables, $coreTables);
        $files = $this->getMigrationFiles($path);
        $schemaCalls = ['Schema::table(', 'Schema::create(', 'Schema::drop(', 'Schema::dropIfExists(', 'Schema::rename('];

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                Assert::fail("Unable to read migration file: {$file->getPathname()}");
            }

            foreach ($coreTables as $table) {
                foreach ($schemaCalls as $schemaCall) {
                    foreach (["'{$table}'", "\"{$table}\""] as $quotedTable) {
                        Assert::assertStringNotContainsString(
                            $schemaCall.$quotedTable,
                            $content,
                            "Migration {$file->getFilename()} references core TI table '{$table}' via {$schemaCall}.",
                        );
                    }
                }
            }
        }
    }

    /**
     * Assert that all migration files have non-empty down() methods.
     */
    protected function assertProperDownMethods(string $path): void
    {
        $files = $this->getMigrationFiles($path);

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                Assert::fail("Unable to read migration file: {$file->getPathname()}");
            }

            // Check that down() method exists
            Assert::assertMatchesRegularExpression(
                '/public\s+function\s+down\s*\(/s',
                $content,
                "Migration {$file->getFilename()} is missing a down() method.",
            );

            // Check that down() isn't empty — uses negative lookahead to ensure
            // the opening brace isn't immediately followed by only whitespace and a closing brace
            Assert::assertMatchesRegularExpression(
                '/public\s+function\s+down\s*\([^)]*\)[^{]*\{(?!\s*\})/s',
                $content,
                "Migration {$file->getFilename()} has an empty down() method.",
            );
        }
    }

    /**
     * Assert that the table schema after a full rollback+migrate cycle
     * still accepts the given seed data structure.
     *
     * Note: This does NOT test data survival through rollback. It tests
     * that the schema after re-migration is structurally compatible with
     * the expected data shape and all specified columns still exist.
     *
     * @param  array<string, mixed>  $seedData
     * @param  array<int, string>  $columns  Columns to verify in the re-migrated schema.
     */
    protected function assertSchemaAcceptsSeedData(
        string $path,
        string $table,
        array $seedData,
        array $columns,
    ): void {
        $this->loadMigrationsFrom($path);
        $this->artisan('migrate', ['--database' => 'testing'])->run();

        // Rollback and re-migrate to verify schema stability
        $this->artisan('migrate:rollback', ['--database' => 'testing'])->run();
        $this->artisan('migrate', ['--database' => 'testing'])->run();

        // Insert seed data into the re-migrated schema
        DB::table($table)->insert($seedData);

        $row = DB::table($table)->first();
        Assert::assertNotNull($row, "Expected table '{$table}' to accept seed data after re-migration.");

        foreach ($columns as $column) {
            Assert::assertObjectHasProperty($column, $row, "Column '{$column}' missing from table '{$table}'.");
            Assert::assertEquals(
                $seedData[$column] ?? null,
                $row->{$column},
                "Value in column '{$column}' does not match seed data.",
            );
        }
    }

    /**
     * Get all PHP migration files from a directory.
     *
     * @return array<int, SplFileInfo>
     */
    private function getMigrationFiles(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $files = [];
        $iterator = new DirectoryIterator($path);

        foreach ($iterator as $file) {
            if ($file->isDot() || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = new SplFileInfo($file->getPathname());
        }

        return $files;
    }
}
