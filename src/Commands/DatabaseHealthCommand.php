<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Commands;

use Illuminate\Console\Command;
use LaravelDbTools\LaravelDatabaseHealth\Enums\HealthStatus;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use LaravelDbTools\LaravelDatabaseHealth\Services\DatabaseHealthRunner;

class DatabaseHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:health 
                            {--connection= : The database connection to check}
                            {--check=* : Filter specific checks to run}
                            {--json : Output diagnostic results as structured JSON}
                            {--fail-on-warning : Exit with non-zero code if any check reports a warning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run comprehensive database diagnostics and health checks';

    /**
     * YB - 20-08-2026 Execute the console command.
     */
    public function handle(DatabaseHealthRunner $runner): int
    {
        $connectionName = $this->option('connection') ?: config('database-health.connection') ?: config('database.default');
        $filterChecks = $this->option('check');

        $startTime = microtime(true);
        $results = $runner->run($this->option('connection'), !empty($filterChecks) ? (array) $filterChecks : null);
        $totalDurationMs = (microtime(true) - $startTime) * 1000;
        $overallStatus = $runner->calculateOverallStatus($results);

        if ($this->option('json')) {
            $payload = [
                'connection' => $connectionName,
                'status' => $overallStatus->value,
                'total_duration_ms' => round($totalDurationMs, 2),
                'timestamp' => now()->toIso8601String(),
                'results' => array_map(fn (CheckResult $r) => $r->toArray(), $results),
            ];

            $this->output->writeln((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->resolveExitCode($overallStatus);
        }

        $this->newLine();
        $this->line(sprintf(' <fg=white;bg=blue;options=bold> DATABASE HEALTH DIAGNOSTICS </>  <fg=gray>Connection: [%s]</>', $connectionName));
        $this->newLine();

        if (empty($results)) {
            $this->warn(' No diagnostic checks were executed. Check your configuration or filters.');
            return self::SUCCESS;
        }

        $rows = array_map(function (CheckResult $result) {
            $statusBadge = match ($result->status) {
                HealthStatus::OK => '<fg=green;options=bold>✓ PASS</>',
                HealthStatus::WARNING => '<fg=yellow;options=bold>⚠ WARN</>',
                HealthStatus::CRITICAL => '<fg=red;options=bold>✗ FAIL</>',
            };

            return [
                $statusBadge,
                sprintf('<fg=white;options=bold>%s</>', $result->checkName),
                $result->message,
                sprintf('<fg=gray>%.2f ms</>', $result->durationMs),
            ];
        }, $results);

        $this->table(['Status', 'Diagnostic Check', 'Details', 'Latency'], $rows);

        $this->newLine();
        match ($overallStatus) {
            HealthStatus::OK => $this->line(sprintf(' <fg=black;bg=green;options=bold> HEALTHY </> <fg=green>All database diagnostic checks passed successfully in %.2f ms.</>', $totalDurationMs)),
            HealthStatus::WARNING => $this->line(sprintf(' <fg=black;bg=yellow;options=bold> WARNING </> <fg=yellow>Some checks reported warnings (total time: %.2f ms). Review warnings above.</>', $totalDurationMs)),
            HealthStatus::CRITICAL => $this->line(sprintf(' <fg=white;bg=red;options=bold> CRITICAL </> <fg=red>Database health checks encountered critical failures! Total time: %.2f ms.</>', $totalDurationMs)),
        };
        $this->newLine();

        return $this->resolveExitCode($overallStatus);
    }

    /**
     * YB - 20-08-2026 Determine process exit code based on status and options.
     */
    protected function resolveExitCode(HealthStatus $status): int
    {
        if ($status->isCritical()) {
            return self::FAILURE;
        }

        if ($status->isWarning() && $this->option('fail-on-warning')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
