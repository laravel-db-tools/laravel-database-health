<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use LaravelDbTools\LaravelDatabaseHealth\Analyzers\AnalyzerFactory;

class IndexesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:health:indexes 
                            {--connection= : The database connection to inspect}
                            {--json : Output index analysis as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan database schema for unindexed foreign keys and missing index opportunities';

    /**
     * YB - 20-08-2026 Execute the console command.
     */
    public function handle(DatabaseManager $db): int
    {
        $connName = $this->option('connection') ?: config('database-health.connection') ?: config('database.default');
        $connection = $db->connection($connName);

        $analyzer = AnalyzerFactory::make($connection);
        $missing = $analyzer->getMissingIndexes($connection);

        if ($this->option('json')) {
            $this->output->writeln((string) json_encode([
                'connection' => $connName,
                'unindexed_count' => count($missing),
                'unindexed_foreign_keys' => $missing,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line(sprintf(' <fg=white;bg=blue;options=bold> FOREIGN KEY INDEX AUDIT </>  <fg=gray>Connection: [%s]</>', $connName));
        $this->newLine();

        if (empty($missing)) {
            $this->info('✓ All foreign key convention columns (*_id) appear to have dedicated indexes!');
            $this->newLine();
            return self::SUCCESS;
        }

        $this->warn(sprintf('Found %d unindexed foreign key column(s) that may cause full table scans in joins:', count($missing)));
        $this->newLine();

        $rows = array_map(function (array $item) {
            $suggestedSql = sprintf('ALTER TABLE `%s` ADD INDEX idx_%s_%s (`%s`);', $item['table'], $item['table'], $item['column'], $item['column']);

            return [
                sprintf('<fg=white;options=bold>%s</>', $item['table']),
                sprintf('<fg=yellow>%s</>', $item['column']),
                sprintf('<fg=gray>%s</>', $suggestedSql),
            ];
        }, $missing);

        $this->table(['Table Name', 'Unindexed Column', 'Suggested Index Migration SQL'], $rows);
        $this->newLine();

        return self::SUCCESS;
    }
}
