<?php

namespace LaravelDbTools\LaravelDatabaseHealth;

use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use LaravelDbTools\LaravelDatabaseHealth\Services\DatabaseHealthRunner;

class DatabaseHealth
{
    /**
     * Package version.
     */
    public const VERSION = '1.1.0';

    /**
     * YB - 20-08-2026 Constructor for DatabaseHealth.
     */
    public function __construct(
        protected ?DatabaseHealthRunner $runner = null
    ) {
    }

    /**
     * YB - 20-08-2026 Get current package version.
     */
    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * YB - 20-08-2026 Run health checks against specified database connection.
     *
     * @param string|null $connection
     * @return array<int, CheckResult>
     */
    public function check(?string $connection = null): array
    {
        $runner = $this->runner ?? app(DatabaseHealthRunner::class);

        return $runner->run($connection);
    }
}
