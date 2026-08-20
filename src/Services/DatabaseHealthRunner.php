<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use LaravelDbTools\LaravelDatabaseHealth\Contracts\CheckInterface;
use LaravelDbTools\LaravelDatabaseHealth\Enums\HealthStatus;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use Throwable;

class DatabaseHealthRunner
{
    /**
     * YB - 20-08-2026 Constructor for DatabaseHealthRunner.
     */
    public function __construct(
        protected DatabaseManager $db,
        protected Container $container,
        protected ?AlertManager $alertManager = null
    ) {
    }

    /**
     * YB - 20-08-2026 Run all or filtered diagnostic checks against the specified connection.
     *
     * @param string|null $connectionName
     * @param array<int, string>|null $filterChecks
     * @return array<int, CheckResult>
     */
    public function run(?string $connectionName = null, ?array $filterChecks = null): array
    {
        $startTime = microtime(true);
        $connection = $this->getConnection($connectionName);
        $connName = $connectionName ?? (string) config('database-health.connection', config('database.default'));
        $checks = $this->getRegisteredChecks();
        $results = [];

        foreach ($checks as $checkClass) {
            if ($filterChecks && !empty($filterChecks)) {
                $matches = false;
                foreach ($filterChecks as $filter) {
                    if (str_contains(strtolower($checkClass), strtolower($filter))) {
                        $matches = true;
                        break;
                    }
                }
                if (! $matches) {
                    continue;
                }
            }

            $results[] = $this->runCheck($checkClass, $connection);
        }

        $durationMs = (microtime(true) - $startTime) * 1000;

        // Process alerts and events
        $alertManager = $this->alertManager ?? $this->container->make(AlertManager::class);
        $alertManager->handle($connName, $results, $durationMs);

        return $results;
    }

    /**
     * YB - 20-08-2026 Execute a single check instance safely with error containment.
     *
     * @param class-string<CheckInterface>|CheckInterface $check
     */
    public function runCheck(string|CheckInterface $check, ConnectionInterface $connection): CheckResult
    {
        $startTime = microtime(true);

        try {
            $instance = is_string($check) ? $this->container->make($check) : $check;

            if (! $instance instanceof CheckInterface) {
                return CheckResult::critical(
                    is_string($check) ? $check : get_class($check),
                    'Configured check must implement ' . CheckInterface::class,
                    (microtime(true) - $startTime) * 1000
                );
            }

            return $instance->run($connection);
        } catch (Throwable $e) {
            $name = is_string($check) ? $check : get_class($check);

            return CheckResult::critical(
                $name,
                'Unexpected error executing check: ' . $e->getMessage(),
                (microtime(true) - $startTime) * 1000,
                ['exception' => get_class($e), 'message' => $e->getMessage()]
            );
        }
    }

    /**
     * YB - 20-08-2026 Calculate overall health status from results array.
     *
     * @param array<int, CheckResult> $results
     */
    public function calculateOverallStatus(array $results): HealthStatus
    {
        $hasWarning = false;

        foreach ($results as $result) {
            if ($result->status->isCritical()) {
                return HealthStatus::CRITICAL;
            }
            if ($result->status->isWarning()) {
                $hasWarning = true;
            }
        }

        return $hasWarning ? HealthStatus::WARNING : HealthStatus::OK;
    }

    /**
     * YB - 20-08-2026 Get the database connection to run diagnostics against.
     */
    public function getConnection(?string $connectionName = null): ConnectionInterface
    {
        $name = $connectionName ?? config('database-health.connection');

        return $this->db->connection($name);
    }

    /**
     * YB - 20-08-2026 Get list of registered check classes from configuration.
     *
     * @return array<int, class-string<CheckInterface>>
     */
    public function getRegisteredChecks(): array
    {
        return config('database-health.checks', []);
    }
}
