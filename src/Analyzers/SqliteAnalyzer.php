<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Analyzers;

use Illuminate\Database\ConnectionInterface;
use Throwable;

class SqliteAnalyzer implements DatabaseAnalyzerInterface
{
    /**
     * YB - 20-08-2026 Get server information and version.
     */
    public function getServerInfo(ConnectionInterface $connection): array
    {
        /** @var \Illuminate\Database\Connection $connection */
        $version = 'unknown';
        try {
            $versionRow = $connection->selectOne('SELECT sqlite_version() as version');
            $version = (string) ($versionRow->version ?? reset($versionRow) ?: '3');
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
        $size = 0;

        if ($db !== ':memory:' && file_exists($db)) {
            $size = (int) filesize($db);
        } else {
            $pageCount = $connection->selectOne('PRAGMA page_count;');
            $pageSize = $connection->selectOne('PRAGMA page_size;');

            $pCount = (int) ($pageCount->page_count ?? (is_array($pageCount) ? reset($pageCount) : 0));
            $pSize = (int) ($pageSize->page_size ?? (is_array($pageSize) ? reset($pageSize) : 4096));

            $size = $pCount * $pSize;
        }

        $tablesResult = $connection->selectOne(
            "SELECT COUNT(*) AS total_tables FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
        );

        $tableCount = (int) ($tablesResult->total_tables ?? (is_array($tablesResult) ? reset($tablesResult) : 0));

        return [
            'size_bytes' => $size,
            'table_count' => $tableCount,
        ];
    }

    /**
     * YB - 20-08-2026 Get detailed table metrics.
     */
    public function getTableStats(ConnectionInterface $connection): array
    {
        $tables = $connection->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $stats = [];

        foreach ($tables as $t) {
            $tableName = is_object($t) ? $t->name : $t['name'];
            $countRow = $connection->selectOne("SELECT COUNT(*) AS total FROM \"{$tableName}\"");
            $rows = (int) ($countRow->total ?? (is_array($countRow) ? reset($countRow) : 0));

            $stats[] = [
                'table' => $tableName,
                'engine' => 'SQLite',
                'rows' => $rows,
                'data_size' => 0,
                'index_size' => 0,
                'total_size' => 0,
                'data_free' => 0,
                'fragmentation_percent' => 0.0,
            ];
        }

        return $stats;
    }

    /**
     * YB - 20-08-2026 Get connection metrics.
     */
    public function getConnectionStats(ConnectionInterface $connection): array
    {
        return [
            'active_connections' => 1,
            'max_connections' => 1,
            'usage_percent' => 100.0,
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
        $tables = $connection->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $missing = [];

        foreach ($tables as $t) {
            $tableName = is_object($t) ? $t->name : $t['name'];
            $cols = $connection->select("PRAGMA table_info(\"{$tableName}\")");
            $indexes = $connection->select("PRAGMA index_list(\"{$tableName}\")");

            $indexedCols = [];
            foreach ($indexes as $idx) {
                $idxName = is_object($idx) ? $idx->name : $idx['name'];
                $idxInfo = $connection->select("PRAGMA index_info(\"{$idxName}\")");
                foreach ($idxInfo as $info) {
                    $indexedCols[] = is_object($info) ? $info->name : $info['name'];
                }
            }

            foreach ($cols as $col) {
                $colName = is_object($col) ? $col->name : $col['name'];
                if (str_ends_with($colName, '_id') && !in_array($colName, $indexedCols, true)) {
                    $missing[] = [
                        'table' => $tableName,
                        'column' => $colName,
                    ];
                }
            }
        }

        return $missing;
    }

    /**
     * YB - 20-08-2026 Optimize table.
     */
    public function optimizeTable(ConnectionInterface $connection, string $table): bool
    {
        $connection->statement('VACUUM');
        return true;
    }
}
