<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Contracts;

use Illuminate\Database\ConnectionInterface;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;

interface CheckInterface
{
    /**
     * YB - 20-08-2026 Get the display name of the check.
     */
    public function name(): string;

    /**
     * YB - 20-08-2026 Get the description of what the check verifies.
     */
    public function description(): string;

    /**
     * YB - 20-08-2026 Execute the diagnostic check against the given connection.
     */
    public function run(ConnectionInterface $connection): CheckResult;
}
