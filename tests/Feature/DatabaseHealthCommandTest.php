<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Tests\Feature;

use LaravelDbTools\LaravelDatabaseHealth\Tests\TestCase;

class DatabaseHealthCommandTest extends TestCase
{
    /**
     * YB - 20-08-2026 Test that the db:health command runs successfully.
     */
    public function test_it_runs_db_health_command_successfully(): void
    {
        $this->artisan('db:health')
            ->expectsOutput('Laravel Database Health')
            ->assertSuccessful();
    }

    /**
     * YB - 20-08-2026 Test that the configuration is merged properly.
     */
    public function test_it_loads_default_package_configuration(): void
    {
        $this->assertTrue(config()->has('database-health'));
    }
}
