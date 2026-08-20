<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelDbTools\LaravelDatabaseHealth\Enums\HealthStatus;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;

class DatabaseHealthChecked
{
    use Dispatchable;
    use SerializesModels;

    /**
     * YB - 20-08-2026 Constructor for DatabaseHealthChecked event.
     *
     * @param array<int, CheckResult> $results
     */
    public function __construct(
        public readonly string $connection,
        public readonly HealthStatus $status,
        public readonly array $results,
        public readonly float $totalDurationMs
    ) {
    }
}
