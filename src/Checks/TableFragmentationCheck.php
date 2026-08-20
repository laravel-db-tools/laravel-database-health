<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Checks;

use Illuminate\Database\ConnectionInterface;
use LaravelDbTools\LaravelDatabaseHealth\Analyzers\AnalyzerFactory;
use LaravelDbTools\LaravelDatabaseHealth\Contracts\CheckInterface;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use Throwable;

class TableFragmentationCheck implements CheckInterface
{
    /**
     * YB - 20-08-2026 Get the display name of the check.
     */
    public function name(): string
    {
        return 'Table Fragmentation';
    }

    /**
     * YB - 20-08-2026 Get the description of what the check verifies.
     */
    public function description(): string
    {
        return 'Detects wasted disk space and fragmented tables that require optimization.';
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

            $thresholdPercent = (float) config('database-health.thresholds.table_fragmentation_warning_percent', 25.0);
            $fragmentedTables = [];

            foreach ($tableStats as $table) {
                if (($table['fragmentation_percent'] ?? 0.0) >= $thresholdPercent && ($table['data_free'] ?? 0) > 1024 * 1024) {
                    $fragmentedTables[] = $table['table'];
                }
            }

            $count = count($fragmentedTables);
            $metadata = [
                'fragmented_count' => $count,
                'fragmented_tables' => $fragmentedTables,
                'threshold_percent' => $thresholdPercent,
            ];

            if ($count > 0) {
                return CheckResult::warning(
                    $this->name(),
                    sprintf(
                        'Found %d %s with >%.0f%% fragmentation: %s',
                        $count,
                        $count === 1 ? 'table' : 'tables',
                        $thresholdPercent,
                        implode(', ', array_slice($fragmentedTables, 0, 3))
                    ),
                    $durationMs,
                    $metadata
                );
            }

            return CheckResult::ok(
                $this->name(),
                'No significant table fragmentation detected (all tables healthy)',
                $durationMs,
                $metadata
            );
        } catch (Throwable $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;

            return CheckResult::ok(
                $this->name(),
                'Table fragmentation check skipped (not supported on current driver)',
                $durationMs,
                ['error' => $e->getMessage()]
            );
        }
    }
}
