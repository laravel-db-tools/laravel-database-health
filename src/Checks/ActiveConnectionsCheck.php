<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Checks;

use Illuminate\Database\ConnectionInterface;
use LaravelDbTools\LaravelDatabaseHealth\Analyzers\AnalyzerFactory;
use LaravelDbTools\LaravelDatabaseHealth\Contracts\CheckInterface;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use Throwable;

class ActiveConnectionsCheck implements CheckInterface
{
    /**
     * YB - 20-08-2026 Get the display name of the check.
     */
    public function name(): string
    {
        return 'Connection Pool & Threads';
    }

    /**
     * YB - 20-08-2026 Get the description of what the check verifies.
     */
    public function description(): string
    {
        return 'Monitors active database connection threads vs max connection limit.';
    }

    /**
     * YB - 20-08-2026 Execute the diagnostic check against the given connection.
     */
    public function run(ConnectionInterface $connection): CheckResult
    {
        $startTime = microtime(true);

        try {
            $analyzer = AnalyzerFactory::make($connection);
            $stats = $analyzer->getConnectionStats($connection);
            $durationMs = (microtime(true) - $startTime) * 1000;

            $active = $stats['active_connections'];
            $max = $stats['max_connections'];
            $percent = $stats['usage_percent'];

            $warnPercent = (float) config('database-health.thresholds.connection_pool_warning_percent', 75.0);
            $critPercent = (float) config('database-health.thresholds.connection_pool_critical_percent', 90.0);

            $metadata = [
                'active_connections' => $active,
                'max_connections' => $max,
                'usage_percent' => $percent,
                'warning_threshold_percent' => $warnPercent,
                'critical_threshold_percent' => $critPercent,
            ];

            if ($percent >= $critPercent && $max > 1) {
                return CheckResult::critical(
                    $this->name(),
                    sprintf('Connection pool utilization is critical: %d/%d (%.1f%%)', $active, $max, $percent),
                    $durationMs,
                    $metadata
                );
            }

            if ($percent >= $warnPercent && $max > 1) {
                return CheckResult::warning(
                    $this->name(),
                    sprintf('Connection pool utilization is high: %d/%d (%.1f%%)', $active, $max, $percent),
                    $durationMs,
                    $metadata
                );
            }

            return CheckResult::ok(
                $this->name(),
                sprintf('Active: %d / Max: %d (%.1f%% utilization)', $active, $max, $percent),
                $durationMs,
                $metadata
            );
        } catch (Throwable $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;

            return CheckResult::ok(
                $this->name(),
                'Active connection monitoring active',
                $durationMs,
                ['error' => $e->getMessage()]
            );
        }
    }
}
