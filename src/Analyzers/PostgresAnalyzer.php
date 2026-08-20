<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Analyzers;

use Illuminate\Database\ConnectionInterface;
use Throwable;

class PostgresAnalyzer implements DatabaseAnalyzerInterface
{
    /**
     * YB - 20-08-2026 Get server information and version.
     */
    public function getServerInfo(ConnectionInterface $connection): array
    {
        /** @var \Illuminate\Database\Connection $connection */
        $version = 'unknown';
        try {
            $version = (string) $connection->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (Throwable) {
            // Fallback
        }

        return [
            'version' => $version,
            'driver' => $connection->getDriverName(),
            'database' => $connection->getDatabaseName(),
        ];
    }

    /**
     * YB - 20-08-2026 Get database total size in bytes and table count.
     */
    public function getDatabaseSize(ConnectionInterface $connection): array
    {
        $sizeResult = $connection->selectOne('SELECT pg_database_size(current_database()) AS size_bytes');
        $tablesResult = $connection->selectOne(
            "SELECT COUNT(*) AS total_tables FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'"
        );

        return [
            'size_bytes' => (int) ($sizeResult->size_bytes ?? 0),
            'table_count' => (int) ($tablesResult->total_tables ?? 0),
        ];
    }

    /**
     * YB - 20-08-2026 Get detailed table metrics.
     */
    public function getTableStats(ConnectionInterface $connection): array
    {
        $sql = "SELECT
                    relname AS table,
                    'PostgreSQL' AS engine,
                    n_live_tup AS rows,
                    pg_relation_size(relid) AS data_size,
                    pg_indexes_size(relid) AS index_size,
                    pg_total_relation_size(relid) AS total_size,
                    (n_dead_tup) AS data_free,
                    ROUND(CASE WHEN (n_live_tup + n_dead_tup) > 0 THEN (n_dead_tup::float / (n_live_tup + n_dead_tup)) * 100 ELSE 0 END::numeric, 2) AS fragmentation_percent
                FROM pg_stat_user_tables
                ORDER BY total_size DESC";

        $rows = $connection->select($sql);

        return array_map(function ($row) {
            $item = (array) $row;
            return [
                'table' => (string) $item['table'],
                'engine' => 'PostgreSQL',
                'rows' => (int) ($item['rows'] ?? 0),
                'data_size' => (int) ($item['data_size'] ?? 0),
                'index_size' => (int) ($item['index_size'] ?? 0),
                'total_size' => (int) ($item['total_size'] ?? 0),
                'data_free' => (int) ($item['data_free'] ?? 0),
                'fragmentation_percent' => (float) ($item['fragmentation_percent'] ?? 0.0),
            ];
        }, $rows);
    }

    /**
     * YB - 20-08-2026 Get connection metrics.
     */
    public function getConnectionStats(ConnectionInterface $connection): array
    {
        $activeRow = $connection->selectOne("SELECT COUNT(*) AS active FROM pg_stat_activity WHERE state IS NOT NULL");
        $maxRow = $connection->selectOne("SHOW max_connections");

        $active = (int) ($activeRow->active ?? 1);
        $max = (int) ($maxRow->max_connections ?? 100);
        $usagePercent = $max > 0 ? round(($active / $max) * 100, 2) : 0.0;

        return [
            'active_connections' => $active,
            'max_connections' => $max,
            'usage_percent' => $usagePercent,
        ];
    }

    /**
     * YB - 20-08-2026 Get long running queries.
     */
    public function getLongRunningQueries(ConnectionInterface $connection, float $thresholdSeconds): array
    {
        $sql = "SELECT pid AS id, usename AS user, client_addr AS host, datname AS db, state, query AS info,
                       EXTRACT(EPOCH FROM (NOW() - query_start)) AS time
                FROM pg_stat_activity
                WHERE state != 'idle' 
                  AND EXTRACT(EPOCH FROM (NOW() - query_start)) >= ?
                  AND query IS NOT NULL";

        $rows = $connection->select($sql, [$thresholdSeconds]);

        return array_map(fn ($r) => (array) $r, $rows);
    }

    /**
     * YB - 20-08-2026 Get unindexed foreign key suggestions.
     */
    public function getMissingIndexes(ConnectionInterface $connection): array
    {
        $sql = "SELECT 
                    t.table_name AS table,
                    c.column_name AS column
                FROM information_schema.tables t
                JOIN information_schema.columns c 
                    ON t.table_name = c.table_name 
                    AND t.table_schema = c.table_schema
                WHERE t.table_schema = 'public'
                    AND t.table_type = 'BASE TABLE'
                    AND c.column_name LIKE '%_id'
                ORDER BY t.table_name, c.column_name";

        $rows = $connection->select($sql);

        return array_map(fn ($r) => (array) $r, $rows);
    }

    /**
     * YB - 20-08-2026 Optimize table.
     */
    public function optimizeTable(ConnectionInterface $connection, string $table): bool
    {
        $escaped = str_replace('"', '""', $table);
        $connection->statement("VACUUM ANALYZE \"{$escaped}\"");

        return true;
    }
}
