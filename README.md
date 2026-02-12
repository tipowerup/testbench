# TI PowerUp Testbench

Shared testing foundation for [TI PowerUp](https://tipowerup.com) TastyIgniter v4 extensions. Provides full Laravel + TastyIgniter context in tests via [Orchestra Testbench](https://github.com/orchestral/testbench), with zero duplication across extensions.

## Requirements

- PHP ^8.3
- TastyIgniter Core ^v4.0

## Installation

```bash
composer require --dev tipowerup/testbench
```

## Setup

Each extension needs three changes:

### 1. `phpunit.xml.dist`

Point the bootstrap to the testbench:

```xml
<phpunit bootstrap="vendor/tipowerup/testbench/src/bootstrap.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Tests">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

### 2. `tests/TestCase.php`

Extend the testbench TestCase and implement the two abstract methods:

```php
<?php

declare(strict_types=1);

namespace Tipowerup\MyExtension\Tests;

use Tipowerup\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getExtensionBasePath(): string
    {
        return dirname(__DIR__);
    }

    protected function getExtensionProviders(): array
    {
        return [\Tipowerup\MyExtension\Extension::class];
    }
}
```

### 3. `tests/Pest.php`

Bind the TestCase to your test directories:

```php
<?php

declare(strict_types=1);

uses(Tipowerup\MyExtension\Tests\TestCase::class)->in('Feature');
uses()->in('Unit');
```

## What You Get

Every test extending the testbench TestCase automatically receives:

- **SQLite `:memory:` database** with foreign key constraints
- **Array cache driver** for isolation
- **TI core system tables** (`settings`, `extensions`, `extension_settings`, etc.)
- **`Igniter\Flame\ServiceProvider`** registered (cascades to all TI core providers)
- **Extension filesystem scanning disabled** (`autoloadExtensions(false)`)
- **Temp directories** for extensions/themes/temp paths (auto-cleaned)

### Disabling Core Migrations

If your test doesn't need TI core tables, set `$runCoreMigrations = false`:

```php
abstract class TestCase extends BaseTestCase
{
    protected bool $runCoreMigrations = false;

    // ...
}
```

### Running Admin Migrations

Admin migrations (orders, menus, locations) are opt-in because some are SQLite-incompatible:

```php
protected function setUp(): void
{
    parent::setUp();

    $this->runTiAdminMigrations();
}
```

### Running Extension Migrations

```php
protected function setUp(): void
{
    parent::setUp();

    $this->runExtensionMigrations(__DIR__.'/../database/migrations');
}
```

## Traits

### `TestsMigrations`

Migration quality assertions:

```php
use Tipowerup\Testbench\Concerns\TestsMigrations;

// Assert migrate up creates tables, rollback removes them
$this->assertMigrationCycle($path, ['my_table', 'my_other_table']);

// Assert N up/down cycles succeed without errors
$this->assertSurvivesInstallCycles($path, ['my_table'], cycles: 3);

// Assert migrations don't touch core TI tables
$this->assertNoCoreTables($path);

// Assert all migrations have non-empty down() methods
$this->assertProperDownMethods($path);

// Assert schema accepts seed data after rollback+migrate
$this->assertSchemaAcceptsSeedData($path, 'my_table', ['name' => 'test'], ['name']);
```

### `TestsTIIntegration`

TastyIgniter integration assertions:

```php
use Tipowerup\Testbench\Concerns\TestsTIIntegration;

$this->assertParamsWork('my_key', 'my_value');
$this->assertExtensionRegistered('tipowerup.myextension');
$this->assertTIInstallCycle('tipowerup.myextension');
$this->assertCleanPurge('tipowerup.myextension', ['my_table']);
$this->assertNavigationRegistered(Extension::class, 'tools', 'my-item');
$this->assertPermissionsRegistered(Extension::class, ['Tipowerup.MyExtension.Manage']);
```

### `MocksTIServices`

Pre-built mock factories with sensible defaults:

```php
use Tipowerup\Testbench\Concerns\MocksTIServices;

// Mock with defaults
$mock = $this->mockExtensionManager();

// Override specific stubs
$mock = $this->mockExtensionManager(['hasExtension' => false]);

// Available mocks
$this->mockExtensionManager($overrides);
$this->mockHubManager($overrides);
$this->mockUpdateManager($overrides);
$this->mockPackageManifest($overrides);
$this->mockService(MyService::class, ['method' => 'return_value']);
```

## Dual-Mode Bootstrap

The bootstrap auto-detects the environment:

| Mode | Condition | Use Case |
|------|-----------|----------|
| **Host** | Parent TI project found (`artisan` + `vendor/tastyigniter/core/`) | Local dev inside a TI app |
| **Standalone** | No parent TI project | CI pipelines, independent dev |

Both modes use Orchestra Testbench identically. The mode is available via `$this->getTestbenchMode()` for extensions that need conditional behavior.

## Development

```bash
# Run tests
composer test:pest

# Fix code style
composer test:lint-fix

# Run all checks
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.
