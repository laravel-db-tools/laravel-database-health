<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Analyzers;

use Illuminate\Database\ConnectionInterface;
use Throwable;

class SqlServerAnalyzer implements DatabaseAnalyzerInterface
{
    /**
     * YB - 20-08-2026 Get server information and version.
     */
    public function getServerInfo(ConnectionInterface $connection): array
    {
        /** @var \Illuminate\Database\Connection $connection */
        $version = 'unknown';
        try {
            $versionRow = $connection->selectOne('SELECT @@VERSION as version');
            $version = (string) ($versionRow->version ?? reset($versionRow) ?: 'SQL Server');
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
        $sizeResult = $connection->selectOne(
            'SELECT COALESCE(SUM(size) * 8 * 1024, 0) AS size_bytes FROM sys.master_files WHERE database_id = DB_ID()'
        );

        $tablesResult = $connection->selectOne(
            'SELECT COUNT(*) AS total_tables FROM sys.tables'
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
                    t.name AS [table],
                    'SQL Server' AS [engine],
                    p.rows AS [rows],
                    SUM(a.total_pages) * 8 * 1024 AS [total_size],
                    SUM(a.used_pages) * 8 * 1024 AS [data_size],
                    (SUM(a.total_pages) - SUM(a.used_pages)) * 8 * 1024 AS [index_size],
                    0 AS [data_free],
                    0.0 AS [fragmentation_percent]
                FROM sys.tables t
                INNER JOIN sys.indexes i ON t.object_id = i.object_id
                INNER JOIN sys.partitions p ON i.object_id = p.object_id AND i.index_id = p.index_id
                INNER JOIN sys.allocation_units a ON p.partition_id = a.container_id
                WHERE t.is_ms_shipped = 0 AND i.OBJECT_ID > 255
                GROUP BY t.Name, p.Rows
                ORDER BY [total_size] DESC";

        $rows = $connection->select($sql);

        return array_map(function ($row) {
            $item = (array) $row;
            return [
                'table' => (string) $item['table'],
                'engine' => 'SQL Server',
                'rows' => (int) ($item['rows'] ?? 0),
                'data_size' => (int) ($item['data_size'] ?? 0),
                'index_size' => (int) ($item['index_size'] ?? 0),
                'total_size' => (int) ($item['total_size'] ?? 0),
                'data_free' => 0,
                'fragmentation_percent' => 0.0,
            ];
        }, $rows);
    }

    /**
     * YB - 20-08-2026 Get connection metrics.
     */
    public function getConnectionStats(ConnectionInterface $connection): array
    {
        $activeRow = $connection->selectOne("SELECT COUNT(*) AS active FROM sys.dm_exec_sessions WHERE is_user_process = 1");
        $maxRow = $connection->selectOne("SELECT @@MAX_CONNECTIONS AS max_conn");

        $active = (int) ($activeRow->active ?? 1);
        $max = (int) ($maxRow->max_conn ?? 32767);
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
        return [];
    }

    /**
     * YB - 20-08-2026 Get unindexed foreign key suggestions.
     */
    public function getMissingIndexes(ConnectionInterface $connection): array
    {
        return [];
    }

    /**
     * YB - 20-08-2026 Optimize table.
     */
    public function optimizeTable(ConnectionInterface $connection, string $table): bool
    {
        $connection->statement("ALTER INDEX ALL ON [{$table}] REBUILD");
        return true;
    }
}
