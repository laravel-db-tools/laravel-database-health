<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;

class DatabaseHealthDegraded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * YB - 20-08-2026 Constructor for DatabaseHealthDegraded event.
     *
     * @param array<int, CheckResult> $warningResults
     */
    public function __construct(
        public readonly string $connection,
        public readonly array $warningResults,
        public readonly float $totalDurationMs
    ) {
    }
}
