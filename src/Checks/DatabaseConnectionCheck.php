<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Checks;

use Illuminate\Database\ConnectionInterface;
use LaravelDbTools\LaravelDatabaseHealth\Contracts\CheckInterface;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use Throwable;

class DatabaseConnectionCheck implements CheckInterface
{
    /**
     * YB - 20-08-2026 Get the display name of the check.
     */
    public function name(): string
    {
        return 'Database Connectivity';
    }

    /**
     * YB - 20-08-2026 Get the description of what the check verifies.
     */
    public function description(): string
    {
        return 'Verifies database reachability, driver status, and ping latency.';
    }

    /**
     * YB - 20-08-2026 Execute the diagnostic check against the given connection.
     */
    public function run(ConnectionInterface $connection): CheckResult
    {
        $startTime = microtime(true);

        try {
            /** @var \Illuminate\Database\Connection $connection */
            $pdo = $connection->getPdo();
            $driver = $connection->getDriverName();
            $databaseName = $connection->getDatabaseName();

            // Fetch server version safely
            $version = 'unknown';
            try {
                $version = (string) $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            } catch (Throwable) {
                // Some mock PDO drivers might not support this attribute
            }

            $durationMs = (microtime(true) - $startTime) * 1000;
            $warningThreshold = (float) config('database-health.thresholds.connection_latency_warning_ms', 150.0);

            $metadata = [
                'driver' => $driver,
                'database' => $databaseName,
                'version' => $version,
                'latency_ms' => round($durationMs, 2),
            ];

            if ($durationMs > $warningThreshold) {
                return CheckResult::warning(
                    $this->name(),
                    sprintf(
                        'Connected to [%s] (v%s) with high latency (%.2f ms, threshold: %.0f ms)',
                        $driver,
                        $version,
                        $durationMs,
                        $warningThreshold
                    ),
                    $durationMs,
                    $metadata
                );
            }

            return CheckResult::ok(
                $this->name(),
                sprintf('Connected to [%s] (v%s) on database "%s"', $driver, $version, $databaseName),
                $durationMs,
                $metadata
            );
        } catch (Throwable $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;

            return CheckResult::critical(
                $this->name(),
                'Database connection failed: ' . $e->getMessage(),
                $durationMs,
                ['error' => $e->getMessage()]
            );
        }
    }
}
