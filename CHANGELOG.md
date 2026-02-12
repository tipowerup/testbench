# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-02-13

### Added
- Initial release with dual-mode testing support (host app and standalone CI)
- `TestCase` base class extending Orchestra Testbench
- `TestsMigrations` trait for migration cycle assertions
  - `assertMigrationCycle()` - verify tables are created and dropped correctly
  - `assertSurvivesInstallCycles()` - test multiple install/uninstall cycles
  - `assertNoCoreTables()` - ensure migrations don't modify core TI tables
  - `assertProperDownMethods()` - verify all migrations have non-empty down() methods
  - `assertSchemaAcceptsSeedData()` - validate schema structure after rollback/migrate
- `TestsTIIntegration` trait for TI-specific assertions
  - `assertParamsWork()` - test TI settings storage/retrieval
  - `assertExtensionRegistered()` - verify extension registration
  - `assertTIInstallCycle()` - test extension install/uninstall/reinstall
  - `assertCleanPurge()` - verify purging drops all tables
  - `assertNavigationRegistered()` - check admin navigation items
  - `assertPermissionsRegistered()` - verify permission registration
- `MocksTIServices` trait for pre-built TI service mocks
  - `mockExtensionManager()` - mock ExtensionManager with sensible defaults
  - `mockHubManager()` - mock HubManager (theme management)
  - `mockUpdateManager()` - mock UpdateManager
  - `mockPackageManifest()` - mock PackageManifest
  - `mockService()` - generic service mocker with custom stubs
- Smart bootstrap that auto-detects host app vs standalone mode
- Automatic temp directory cleanup after tests
- SQLite in-memory database configuration for isolated testing
- Orchestra Testbench integration (Laravel 11 + 12 support)

[Unreleased]: https://github.com/tipowerup/testbench/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/tipowerup/testbench/releases/tag/v0.1.0
