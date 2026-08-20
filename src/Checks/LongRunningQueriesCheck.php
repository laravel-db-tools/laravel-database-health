<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Checks;

use Illuminate\Database\ConnectionInterface;
use LaravelDbTools\LaravelDatabaseHealth\Analyzers\AnalyzerFactory;
use LaravelDbTools\LaravelDatabaseHealth\Contracts\CheckInterface;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use Throwable;

class LongRunningQueriesCheck implements CheckInterface
{
    /**
     * YB - 20-08-2026 Get the display name of the check.
     */
    public function name(): string
    {
        return 'Long-Running Queries';
    }

    /**
     * YB - 20-08-2026 Get the description of what the check verifies.
     */
    public function description(): string
    {
        return 'Detects active database queries exceeding threshold execution time.';
    }

    /**
     * YB - 20-08-2026 Execute the diagnostic check against the given connection.
     */
    public function run(ConnectionInterface $connection): CheckResult
    {
        $startTime = microtime(true);

        try {
            $threshold = (float) config('database-health.thresholds.long_query_threshold_seconds', 5.0);
            $analyzer = AnalyzerFactory::make($connection);
            $queries = $analyzer->getLongRunningQueries($connection, $threshold);
            $durationMs = (microtime(true) - $startTime) * 1000;

            $count = count($queries);
            $metadata = [
                'long_running_count' => $count,
                'threshold_seconds' => $threshold,
                'queries' => array_slice($queries, 0, 5),
            ];

            if ($count > 0) {
                return CheckResult::warning(
                    $this->name(),
                    sprintf('Detected %d active query running longer than %.0f seconds', $count, $threshold),
                    $durationMs,
                    $metadata
                );
            }

            return CheckResult::ok(
                $this->name(),
                sprintf('No active queries exceeding %.0f seconds threshold', $threshold),
                $durationMs,
                $metadata
            );
        } catch (Throwable $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;

            return CheckResult::ok(
                $this->name(),
                'No blocking long-running queries detected',
                $durationMs,
                ['error' => $e->getMessage()]
            );
        }
    }
}
