<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Checks;

use Illuminate\Database\ConnectionInterface;
use LaravelDbTools\LaravelDatabaseHealth\Contracts\CheckInterface;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use Throwable;

class QueryResponseTimeCheck implements CheckInterface
{
    /**
     * YB - 20-08-2026 Get the display name of the check.
     */
    public function name(): string
    {
        return 'Query Response Time';
    }

    /**
     * YB - 20-08-2026 Get the description of what the check verifies.
     */
    public function description(): string
    {
        return 'Measures read query execution latency and checks against configured thresholds.';
    }

    /**
     * YB - 20-08-2026 Execute the diagnostic check against the given connection.
     */
    public function run(ConnectionInterface $connection): CheckResult
    {
        $startTime = microtime(true);

        try {
            $connection->selectOne('SELECT 1');

            $durationMs = (microtime(true) - $startTime) * 1000;
            $warningThreshold = (float) config('database-health.thresholds.query_latency_warning_ms', 50.0);
            $criticalThreshold = (float) config('database-health.thresholds.query_latency_critical_ms', 250.0);

            $metadata = [
                'duration_ms' => round($durationMs, 2),
                'warning_threshold_ms' => $warningThreshold,
                'critical_threshold_ms' => $criticalThreshold,
            ];

            if ($durationMs >= $criticalThreshold) {
                return CheckResult::critical(
                    $this->name(),
                    sprintf('Query response time is critically slow (%.2f ms, threshold: %.0f ms)', $durationMs, $criticalThreshold),
                    $durationMs,
                    $metadata
                );
            }

            if ($durationMs >= $warningThreshold) {
                return CheckResult::warning(
                    $this->name(),
                    sprintf('Query response time is degraded (%.2f ms, threshold: %.0f ms)', $durationMs, $warningThreshold),
                    $durationMs,
                    $metadata
                );
            }

            return CheckResult::ok(
                $this->name(),
                sprintf('Query responded quickly in %.2f ms', $durationMs),
                $durationMs,
                $metadata
            );
        } catch (Throwable $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;

            return CheckResult::critical(
                $this->name(),
                'Query execution failed: ' . $e->getMessage(),
                $durationMs,
                ['error' => $e->getMessage()]
            );
        }
    }
}
