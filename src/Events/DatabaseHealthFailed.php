<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;

class DatabaseHealthFailed
{
    use Dispatchable;
    use SerializesModels;

    /**
     * YB - 20-08-2026 Constructor for DatabaseHealthFailed event.
     *
     * @param array<int, CheckResult> $criticalResults
     */
    public function __construct(
        public readonly string $connection,
        public readonly array $criticalResults,
        public readonly float $totalDurationMs
    ) {
    }
}
