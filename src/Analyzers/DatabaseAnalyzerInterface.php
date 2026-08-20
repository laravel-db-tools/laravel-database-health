<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Analyzers;

use Illuminate\Database\ConnectionInterface;

interface DatabaseAnalyzerInterface
{
    /**
     * YB - 20-08-2026 Get server information and version.
     *
     * @return array{version: string, driver: string, database: string}
     */
    public function getServerInfo(ConnectionInterface $connection): array;

    /**
     * YB - 20-08-2026 Get database total size in bytes and table count.
     *
     * @return array{size_bytes: int, table_count: int}
     */
    public function getDatabaseSize(ConnectionInterface $connection): array;

    /**
     * YB - 20-08-2026 Get detailed table metrics (rows, data size, index size, fragmentation).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTableStats(ConnectionInterface $connection): array;

    /**
     * YB - 20-08-2026 Get connection metrics (active, max, utilization).
     *
     * @return array{active_connections: int, max_connections: int, usage_percent: float}
     */
    public function getConnectionStats(ConnectionInterface $connection): array;

    /**
     * YB - 20-08-2026 Get long running queries.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLongRunningQueries(ConnectionInterface $connection, float $thresholdSeconds): array;

    /**
     * YB - 20-08-2026 Get unindexed foreign key suggestions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMissingIndexes(ConnectionInterface $connection): array;

    /**
     * YB - 20-08-2026 Optimize or vacuum a table.
     */
    public function optimizeTable(ConnectionInterface $connection, string $table): bool;
}
