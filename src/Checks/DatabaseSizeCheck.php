<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Checks;

use Illuminate\Database\ConnectionInterface;
use LaravelDbTools\LaravelDatabaseHealth\Contracts\CheckInterface;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use Throwable;

class DatabaseSizeCheck implements CheckInterface
{
    /**
     * YB - 20-08-2026 Get the display name of the check.
     */
    public function name(): string
    {
        return 'Database Size & Tables';
    }

    /**
     * YB - 20-08-2026 Get the description of what the check verifies.
     */
    public function description(): string
    {
        return 'Computes database size in bytes/MB and counts total tables.';
    }

    /**
     * YB - 20-08-2026 Execute the diagnostic check against the given connection.
     */
    public function run(ConnectionInterface $connection): CheckResult
    {
        $startTime = microtime(true);

        try {
            /** @var \Illuminate\Database\Connection $connection */
            $driver = $connection->getDriverName();
            $database = $connection->getDatabaseName();

            [$sizeBytes, $tableCount] = match ($driver) {
                'mysql', 'mariadb' => $this->getMysqlMetrics($connection, $database),
                'pgsql' => $this->getPgsqlMetrics($connection),
                'sqlite' => $this->getSqliteMetrics($connection, $database),
                'sqlsrv' => $this->getSqlsrvMetrics($connection),
                default => [0, 0],
            };

            $durationMs = (microtime(true) - $startTime) * 1000;
            $sizeMb = round($sizeBytes / (1024 * 1024), 2);

            $metadata = [
                'driver' => $driver,
                'table_count' => $tableCount,
                'size_bytes' => $sizeBytes,
                'size_mb' => $sizeMb,
                'formatted_size' => $this->formatBytes($sizeBytes),
            ];

            return CheckResult::ok(
                $this->name(),
                sprintf(
                    'Size: %s across %d %s',
                    $this->formatBytes($sizeBytes),
                    $tableCount,
                    $tableCount === 1 ? 'table' : 'tables'
                ),
                $durationMs,
                $metadata
            );
        } catch (Throwable $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;

            return CheckResult::warning(
                $this->name(),
                'Could not retrieve size metrics: ' . $e->getMessage(),
                $durationMs,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * YB - 20-08-2026 Retrieve metrics for MySQL/MariaDB database.
     *
     * @return array{int, int}
     */
    protected function getMysqlMetrics(ConnectionInterface $connection, string $database): array
    {
        $sizeResult = $connection->selectOne(
            'SELECT COALESCE(SUM(data_length + index_length), 0) AS size_bytes FROM information_schema.TABLES WHERE table_schema = ?',
            [$database]
        );

        $tablesResult = $connection->selectOne(
            "SELECT COUNT(*) AS total_tables FROM information_schema.TABLES WHERE table_schema = ? AND table_type = 'BASE TABLE'",
            [$database]
        );

        $size = (int) ($sizeResult->size_bytes ?? 0);
        $count = (int) ($tablesResult->total_tables ?? 0);

        return [$size, $count];
    }

    /**
     * YB - 20-08-2026 Retrieve metrics for PostgreSQL database.
     *
     * @return array{int, int}
     */
    protected function getPgsqlMetrics(ConnectionInterface $connection): array
    {
        $sizeResult = $connection->selectOne('SELECT pg_database_size(current_database()) AS size_bytes');
        $tablesResult = $connection->selectOne(
            "SELECT COUNT(*) AS total_tables FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'"
        );

        $size = (int) ($sizeResult->size_bytes ?? 0);
        $count = (int) ($tablesResult->total_tables ?? 0);

        return [$size, $count];
    }

    /**
     * YB - 20-08-2026 Retrieve metrics for SQLite database.
     *
     * @return array{int, int}
     */
    protected function getSqliteMetrics(ConnectionInterface $connection, string $database): array
    {
        $size = 0;
        if ($database !== ':memory:' && file_exists($database)) {
            $size = (int) filesize($database);
        } else {
            // For in-memory or fallback
            $pageCount = $connection->selectOne('PRAGMA page_count;');
            $pageSize = $connection->selectOne('PRAGMA page_size;');

            $pCount = (int) ($pageCount->page_count ?? reset($pageCount) ?: 0);
            $pSize = (int) ($pageSize->page_size ?? reset($pageSize) ?: 4096);

            $size = $pCount * $pSize;
        }

        $tablesResult = $connection->selectOne(
            "SELECT COUNT(*) AS total_tables FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
        );

        $count = (int) ($tablesResult->total_tables ?? reset($tablesResult) ?: 0);

        return [$size, $count];
    }

    /**
     * YB - 20-08-2026 Retrieve metrics for SQL Server database.
     *
     * @return array{int, int}
     */
    protected function getSqlsrvMetrics(ConnectionInterface $connection): array
    {
        $sizeResult = $connection->selectOne(
            'SELECT COALESCE(SUM(size) * 8 * 1024, 0) AS size_bytes FROM sys.master_files WHERE database_id = DB_ID()'
        );

        $tablesResult = $connection->selectOne(
            'SELECT COUNT(*) AS total_tables FROM sys.tables'
        );

        $size = (int) ($sizeResult->size_bytes ?? 0);
        $count = (int) ($tablesResult->total_tables ?? 0);

        return [$size, $count];
    }

    /**
     * YB - 20-08-2026 Format raw byte count into human-readable size string.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
