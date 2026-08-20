<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Tests\Feature;

use LaravelDbTools\LaravelDatabaseHealth\Facades\DatabaseHealth;
use LaravelDbTools\LaravelDatabaseHealth\Tests\TestCase;

class DatabaseHealthCommandTest extends TestCase
{
    /**
     * YB - 20-08-2026 Test that the db:health command runs all checks and succeeds.
     */
    public function test_it_runs_db_health_command_successfully(): void
    {
        $this->artisan('db:health')
            ->expectsOutputToContain('DATABASE HEALTH DIAGNOSTICS')
            ->expectsOutputToContain('Database Connectivity')
            ->expectsOutputToContain('Query Response Time')
            ->expectsOutputToContain('Write Transaction Latency')
            ->expectsOutputToContain('Database Size & Tables')
            ->expectsOutputToContain('Table Storage Breakdown')
            ->expectsOutputToContain('Connection Pool & Threads')
            ->expectsOutputToContain('Table Fragmentation')
            ->expectsOutputToContain('Foreign Key Indexing')
            ->expectsOutputToContain('Long-Running Queries')
            ->expectsOutputToContain('HEALTHY')
            ->assertSuccessful();
    }

    /**
     * YB - 20-08-2026 Test that db:health supports JSON output.
     */
    public function test_it_outputs_json_format(): void
    {
        $this->artisan('db:health --json')
            ->expectsOutputToContain('"status": "ok"')
            ->assertSuccessful();
    }

    /**
     * YB - 20-08-2026 Test that the facade runs all diagnostic checks.
     */
    public function test_facade_runs_checks(): void
    {
        $results = DatabaseHealth::check();

        $this->assertCount(9, $results);
        $this->assertSame('Database Connectivity', $results[0]->checkName);
    }
}
