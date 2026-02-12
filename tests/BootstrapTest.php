<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;

it('boots a valid application instance', function (): void {
    expect($this->app)->toBeInstanceOf(Application::class);
});

it('uses sqlite memory database', function (): void {
    $connection = config('database.default');
    $driver = config("database.connections.{$connection}.driver");
    $database = config("database.connections.{$connection}.database");

    expect($driver)->toBe('sqlite');
    expect($database)->toBe(':memory:');
});

it('uses array cache driver', function (): void {
    expect(config('cache.default'))->toBe('array');
});

it('has ti core settings table', function (): void {
    expect(Schema::hasTable('settings'))->toBeTrue();
});

it('has ti core extensions table', function (): void {
    expect(Schema::hasTable('extensions'))->toBeTrue();
});

it('has ti core extension_settings table', function (): void {
    expect(Schema::hasTable('extension_settings'))->toBeTrue();
});

it('detects correct testbench mode', function (): void {
    $mode = $_ENV['TI_TESTBENCH_MODE'] ?? null;

    expect($mode)->toBeIn(['host', 'standalone']);
});
