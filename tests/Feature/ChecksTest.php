<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Tests\Feature;

use Illuminate\Support\Facades\DB;
use LaravelDbTools\LaravelDatabaseHealth\Checks\ActiveConnectionsCheck;
use LaravelDbTools\LaravelDatabaseHealth\Checks\DatabaseConnectionCheck;
use LaravelDbTools\LaravelDatabaseHealth\Checks\DatabaseSizeCheck;
use LaravelDbTools\LaravelDatabaseHealth\Checks\LongRunningQueriesCheck;
use LaravelDbTools\LaravelDatabaseHealth\Checks\MissingIndexCheck;
use LaravelDbTools\LaravelDatabaseHealth\Checks\QueryResponseTimeCheck;
use LaravelDbTools\LaravelDatabaseHealth\Checks\TableFragmentationCheck;
use LaravelDbTools\LaravelDatabaseHealth\Checks\TableSizeCheck;
use LaravelDbTools\LaravelDatabaseHealth\Checks\WriteLatencyCheck;
use LaravelDbTools\LaravelDatabaseHealth\Enums\HealthStatus;
use LaravelDbTools\LaravelDatabaseHealth\Tests\TestCase;

class ChecksTest extends TestCase
{
    /**
     * YB - 20-08-2026 Test database connection check.
     */
    public function test_database_connection_check(): void
    {
        $check = new DatabaseConnectionCheck();
        $result = $check->run(DB::connection());

        $this->assertSame(HealthStatus::OK, $result->status);
        $this->assertSame('Database Connectivity', $result->checkName);
    }

    /**
     * YB - 20-08-2026 Test query response time check.
     */
    public function test_query_response_time_check(): void
    {
        $check = new QueryResponseTimeCheck();
        $result = $check->run(DB::connection());

        $this->assertSame(HealthStatus::OK, $result->status);
        $this->assertSame('Query Response Time', $result->checkName);
    }

    /**
     * YB - 20-08-2026 Test write transaction latency check.
     */
    public function test_write_latency_check(): void
    {
        $check = new WriteLatencyCheck();
        $result = $check->run(DB::connection());

        $this->assertSame(HealthStatus::OK, $result->status);
        $this->assertSame('Write Transaction Latency', $result->checkName);
    }

    /**
     * YB - 20-08-2026 Test database size check.
     */
    public function test_database_size_check(): void
    {
        $check = new DatabaseSizeCheck();
        $result = $check->run(DB::connection());

        $this->assertSame(HealthStatus::OK, $result->status);
        $this->assertArrayHasKey('table_count', $result->metadata);
    }

    /**
     * YB - 20-08-2026 Test table size check.
     */
    public function test_table_size_check(): void
    {
        $check = new TableSizeCheck();
        $result = $check->run(DB::connection());

        $this->assertSame(HealthStatus::OK, $result->status);
    }

    /**
     * YB - 20-08-2026 Test active connections check.
     */
    public function test_active_connections_check(): void
    {
        $check = new ActiveConnectionsCheck();
        $result = $check->run(DB::connection());

        $this->assertSame(HealthStatus::OK, $result->status);
    }

    /**
     * YB - 20-08-2026 Test table fragmentation check.
     */
    public function test_table_fragmentation_check(): void
    {
        $check = new TableFragmentationCheck();
        $result = $check->run(DB::connection());

        $this->assertSame(HealthStatus::OK, $result->status);
    }

    /**
     * YB - 20-08-2026 Test missing index check.
     */
    public function test_missing_index_check(): void
    {
        $check = new MissingIndexCheck();
        $result = $check->run(DB::connection());

        $this->assertSame(HealthStatus::OK, $result->status);
    }

    /**
     * YB - 20-08-2026 Test long running queries check.
     */
    public function test_long_running_queries_check(): void
    {
        $check = new LongRunningQueriesCheck();
        $result = $check->run(DB::connection());

        $this->assertSame(HealthStatus::OK, $result->status);
    }
}
