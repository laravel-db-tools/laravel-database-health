<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use LaravelDbTools\LaravelDatabaseHealth\DatabaseHealthServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * YB - 20-08-2026 Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * YB - 20-08-2026 Get package providers for Orchestra Testbench.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            DatabaseHealthServiceProvider::class,
        ];
    }

    /**
     * YB - 20-08-2026 Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
