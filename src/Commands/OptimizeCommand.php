<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use LaravelDbTools\LaravelDatabaseHealth\Analyzers\AnalyzerFactory;
use Throwable;

class OptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:health:optimize 
                            {--table= : Optimize a specific table only}
                            {--connection= : The database connection to target}
                            {--force : Force optimization without confirmation prompt}
                            {--threshold=20 : Only optimize tables with fragmentation >= threshold percentage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize/vacuum database tables to reclaim unused storage and defragment indexes';

    /**
     * YB - 20-08-2026 Execute the console command.
     */
    public function handle(DatabaseManager $db): int
    {
        $connName = $this->option('connection') ?: config('database-health.connection') ?: config('database.default');
        $connection = $db->connection($connName);
        $threshold = (float) $this->option('threshold');
        $targetTable = $this->option('table');

        $analyzer = AnalyzerFactory::make($connection);

        $this->newLine();
        $this->line(sprintf(' <fg=white;bg=blue;options=bold> DATABASE STORAGE OPTIMIZATION </>  <fg=gray>Connection: [%s]</>', $connName));
        $this->newLine();

        if ($targetTable) {
            $tablesToOptimize = [$targetTable];
        } else {
            $allTables = $analyzer->getTableStats($connection);
            $tablesToOptimize = [];

            foreach ($allTables as $t) {
                if (($t['fragmentation_percent'] ?? 0.0) >= $threshold || ($connection->getDriverName() === 'sqlite')) {
                    $tablesToOptimize[] = $t['table'];
                }
            }
        }

        if (empty($tablesToOptimize)) {
            $this->info(sprintf('✓ No tables found exceeding %.0f%% fragmentation threshold. Database storage is already optimal!', $threshold));
            $this->newLine();
            return self::SUCCESS;
        }

        $this->warn(sprintf('Tables queued for optimization: %s', implode(', ', $tablesToOptimize)));

        if (! $this->option('force') && ! $this->confirm('Proceed with table optimization?', true)) {
            $this->comment('Optimization cancelled by user.');
            return self::SUCCESS;
        }

        $this->output->progressStart(count($tablesToOptimize));

        $optimized = 0;
        $failed = 0;

        foreach ($tablesToOptimize as $table) {
            try {
                $analyzer->optimizeTable($connection, $table);
                $optimized++;
            } catch (Throwable $e) {
                $this->error(sprintf("\nFailed to optimize '%s': %s", $table, $e->getMessage()));
                $failed++;
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->newLine();

        $this->info(sprintf('✓ Optimization complete. %d table(s) optimized successfully (%d failed).', $optimized, $failed));
        $this->newLine();

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
