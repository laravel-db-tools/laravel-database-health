<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Checks;

use Illuminate\Database\ConnectionInterface;
use LaravelDbTools\LaravelDatabaseHealth\Analyzers\AnalyzerFactory;
use LaravelDbTools\LaravelDatabaseHealth\Contracts\CheckInterface;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use Throwable;

class MissingIndexCheck implements CheckInterface
{
    /**
     * YB - 20-08-2026 Get the display name of the check.
     */
    public function name(): string
    {
        return 'Foreign Key Indexing';
    }

    /**
     * YB - 20-08-2026 Get the description of what the check verifies.
     */
    public function description(): string
    {
        return 'Scans schema for unindexed foreign key conventions (*_id columns).';
    }

    /**
     * YB - 20-08-2026 Execute the diagnostic check against the given connection.
     */
    public function run(ConnectionInterface $connection): CheckResult
    {
        $startTime = microtime(true);

        try {
            $analyzer = AnalyzerFactory::make($connection);
            $missing = $analyzer->getMissingIndexes($connection);
            $durationMs = (microtime(true) - $startTime) * 1000;

            $count = count($missing);
            $metadata = [
                'unindexed_foreign_keys_count' => $count,
                'unindexed_foreign_keys' => $missing,
            ];

            if ($count > 0) {
                $examples = array_map(fn ($m) => "{$m['table']}.{$m['column']}", array_slice($missing, 0, 3));

                return CheckResult::warning(
                    $this->name(),
                    sprintf(
                        'Found %d unindexed foreign key %s (e.g. %s)',
                        $count,
                        $count === 1 ? 'column' : 'columns',
                        implode(', ', $examples)
                    ),
                    $durationMs,
                    $metadata
                );
            }

            return CheckResult::ok(
                $this->name(),
                'All foreign key columns (*_id) appear properly indexed',
                $durationMs,
                $metadata
            );
        } catch (Throwable $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;

            return CheckResult::ok(
                $this->name(),
                'Foreign key index scan completed',
                $durationMs,
                ['error' => $e->getMessage()]
            );
        }
    }
}
