<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Analyzers;

use Illuminate\Database\ConnectionInterface;
use Throwable;

class MySqlAnalyzer implements DatabaseAnalyzerInterface
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
        $db = $connection->getDatabaseName();

        $sizeResult = $connection->selectOne(
            'SELECT COALESCE(SUM(data_length + index_length), 0) AS size_bytes FROM information_schema.TABLES WHERE table_schema = ?',
            [$db]
        );

        $tablesResult = $connection->selectOne(
            "SELECT COUNT(*) AS total_tables FROM information_schema.TABLES WHERE table_schema = ? AND table_type = 'BASE TABLE'",
            [$db]
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
        $db = $connection->getDatabaseName();

        $rows = $connection->select(
            "SELECT 
                table_name AS `table`,
                engine AS `engine`,
                table_rows AS `rows`,
                data_length AS `data_size`,
                index_length AS `index_size`,
                (data_length + index_length) AS `total_size`,
                data_free AS `data_free`,
                ROUND(CASE WHEN (data_length + data_free) > 0 THEN (data_free / (data_length + data_free)) * 100 ELSE 0 END, 2) AS `fragmentation_percent`
            FROM information_schema.TABLES
            WHERE table_schema = ? AND table_type = 'BASE TABLE'
            ORDER BY `total_size` DESC",
            [$db]
        );

        return array_map(function ($row) {
            $item = (array) $row;
            return [
                'table' => (string) $item['table'],
                'engine' => (string) ($item['engine'] ?? 'InnoDB'),
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
        $threadsRow = $connection->selectOne("SHOW STATUS LIKE 'Threads_connected'");
        $maxRow = $connection->selectOne("SHOW VARIABLES LIKE 'max_connections'");

        $active = (int) ($threadsRow->Value ?? $threadsRow->value ?? 1);
        $max = (int) ($maxRow->Value ?? $maxRow->value ?? 151);
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
        $rows = $connection->select(
            "SELECT id, user, host, db, command, time, state, info 
             FROM information_schema.processlist 
             WHERE command != 'Sleep' AND time >= ? AND info IS NOT NULL",
            [(int) $thresholdSeconds]
        );

        return array_map(fn ($r) => (array) $r, $rows);
    }

    /**
     * YB - 20-08-2026 Get unindexed foreign key suggestions.
     */
    public function getMissingIndexes(ConnectionInterface $connection): array
    {
        $db = $connection->getDatabaseName();

        // Columns ending in _id that do not have an index on them
        $sql = "SELECT 
                    c.TABLE_NAME AS `table`, 
                    c.COLUMN_NAME AS `column`
                FROM information_schema.COLUMNS c
                LEFT JOIN information_schema.STATISTICS s 
                    ON c.TABLE_SCHEMA = s.TABLE_SCHEMA 
                    AND c.TABLE_NAME = s.TABLE_NAME 
                    AND c.COLUMN_NAME = s.COLUMN_NAME
                WHERE c.TABLE_SCHEMA = ?
                    AND c.COLUMN_NAME LIKE '%_id'
                    AND s.COLUMN_NAME IS NULL
                ORDER BY c.TABLE_NAME, c.COLUMN_NAME";

        $rows = $connection->select($sql, [$db]);

        return array_map(fn ($r) => (array) $r, $rows);
    }

    /**
     * YB - 20-08-2026 Optimize table.
     */
    public function optimizeTable(ConnectionInterface $connection, string $table): bool
    {
        $escaped = str_replace('`', '``', $table);
        $connection->statement("OPTIMIZE TABLE `{$escaped}`");

        return true;
    }
}
