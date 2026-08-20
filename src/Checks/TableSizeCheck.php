<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Checks;

use Illuminate\Database\ConnectionInterface;
use LaravelDbTools\LaravelDatabaseHealth\Analyzers\AnalyzerFactory;
use LaravelDbTools\LaravelDatabaseHealth\Contracts\CheckInterface;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use Throwable;

class TableSizeCheck implements CheckInterface
{
    /**
     * YB - 20-08-2026 Get the display name of the check.
     */
    public function name(): string
    {
        return 'Table Storage Breakdown';
    }

    /**
     * YB - 20-08-2026 Get the description of what the check verifies.
     */
    public function description(): string
    {
        return 'Analyzes table sizes, row counts, and identifies the largest database tables.';
    }

    /**
     * YB - 20-08-2026 Execute the diagnostic check against the given connection.
     */
    public function run(ConnectionInterface $connection): CheckResult
    {
        $startTime = microtime(true);

        try {
            $analyzer = AnalyzerFactory::make($connection);
            $tableStats = $analyzer->getTableStats($connection);
            $durationMs = (microtime(true) - $startTime) * 1000;

            $totalTables = count($tableStats);
            if ($totalTables === 0) {
                return CheckResult::ok(
                    $this->name(),
                    'No tables found in database.',
                    $durationMs,
                    ['total_tables' => 0]
                );
            }

            $topTable = $tableStats[0];
            $tableName = $topTable['table'];
            $tableRows = number_format($topTable['rows']);
            $tableSize = $this->formatBytes($topTable['total_size'] ?? 0);

            $message = sprintf('Total %d tables analyzed. Largest: "%s" (%s rows, %s)', $totalTables, $tableName, $tableRows, $tableSize);

            return CheckResult::ok(
                $this->name(),
                $message,
                $durationMs,
                [
                    'total_tables' => $totalTables,
                    'largest_table' => $tableName,
                    'tables' => array_slice($tableStats, 0, 5),
                ]
            );
        } catch (Throwable $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;

            return CheckResult::warning(
                $this->name(),
                'Could not analyze table storage: ' . $e->getMessage(),
                $durationMs,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * YB - 20-08-2026 Format byte count to human-readable size.
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
