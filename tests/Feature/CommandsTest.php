<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Tests\Feature;

use LaravelDbTools\LaravelDatabaseHealth\Tests\TestCase;

class CommandsTest extends TestCase
{
    /**
     * YB - 20-08-2026 Test db:health:tables command.
     */
    public function test_tables_command(): void
    {
        $this->artisan('db:health:tables')
            ->expectsOutputToContain('DATABASE TABLE STORAGE BREAKDOWN')
            ->assertSuccessful();

        $this->artisan('db:health:tables --json')
            ->expectsOutputToContain('"total_tables"')
            ->assertSuccessful();
    }

    /**
     * YB - 20-08-2026 Test db:health:connections command.
     */
    public function test_connections_command(): void
    {
        $this->artisan('db:health:connections')
            ->expectsOutputToContain('DATABASE CONNECTION METRICS')
            ->assertSuccessful();

        $this->artisan('db:health:connections --json')
            ->expectsOutputToContain('"active_connections"')
            ->assertSuccessful();
    }

    /**
     * YB - 20-08-2026 Test db:health:indexes command.
     */
    public function test_indexes_command(): void
    {
        $this->artisan('db:health:indexes')
            ->expectsOutputToContain('FOREIGN KEY INDEX AUDIT')
            ->assertSuccessful();

        $this->artisan('db:health:indexes --json')
            ->expectsOutputToContain('"unindexed_count"')
            ->assertSuccessful();
    }

    /**
     * YB - 20-08-2026 Test db:health:optimize command.
     */
    public function test_optimize_command(): void
    {
        $this->artisan('db:health:optimize --force')
            ->expectsOutputToContain('DATABASE STORAGE OPTIMIZATION')
            ->assertSuccessful();
    }
}
