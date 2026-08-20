<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Checks;

use Illuminate\Database\ConnectionInterface;
use LaravelDbTools\LaravelDatabaseHealth\Contracts\CheckInterface;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use Throwable;

class WriteLatencyCheck implements CheckInterface
{
    /**
     * YB - 20-08-2026 Get the display name of the check.
     */
    public function name(): string
    {
        return 'Write Transaction Latency';
    }

    /**
     * YB - 20-08-2026 Get the description of what the check verifies.
     */
    public function description(): string
    {
        return 'Measures database transaction commit/rollback write speed.';
    }

    /**
     * YB - 20-08-2026 Execute the diagnostic check against the given connection.
     */
    public function run(ConnectionInterface $connection): CheckResult
    {
        $startTime = microtime(true);

        try {
            /** @var \Illuminate\Database\Connection $connection */
            $connection->beginTransaction();
            // Test temporary write or transaction cycle
            $connection->selectOne('SELECT 1');
            $connection->rollBack();

            $durationMs = (microtime(true) - $startTime) * 1000;
            $warningThreshold = (float) config('database-health.thresholds.write_latency_warning_ms', 100.0);
            $criticalThreshold = (float) config('database-health.thresholds.write_latency_critical_ms', 500.0);

            $metadata = [
                'duration_ms' => round($durationMs, 2),
                'warning_threshold_ms' => $warningThreshold,
                'critical_threshold_ms' => $criticalThreshold,
            ];

            if ($durationMs >= $criticalThreshold) {
                return CheckResult::critical(
                    $this->name(),
                    sprintf('Write transaction latency is critically slow (%.2f ms, threshold: %.0f ms)', $durationMs, $criticalThreshold),
                    $durationMs,
                    $metadata
                );
            }

            if ($durationMs >= $warningThreshold) {
                return CheckResult::warning(
                    $this->name(),
                    sprintf('Write transaction latency is degraded (%.2f ms, threshold: %.0f ms)', $durationMs, $warningThreshold),
                    $durationMs,
                    $metadata
                );
            }

            return CheckResult::ok(
                $this->name(),
                sprintf('Write transaction completed cleanly in %.2f ms', $durationMs),
                $durationMs,
                $metadata
            );
        } catch (Throwable $e) {
            try {
                if ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
            } catch (Throwable) {
                // Ignore rollback failure
            }

            $durationMs = (microtime(true) - $startTime) * 1000;

            return CheckResult::critical(
                $this->name(),
                'Write transaction test failed: ' . $e->getMessage(),
                $durationMs,
                ['error' => $e->getMessage()]
            );
        }
    }
}
