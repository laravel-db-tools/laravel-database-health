<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use LaravelDbTools\LaravelDatabaseHealth\Analyzers\AnalyzerFactory;

class TablesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:health:tables 
                            {--connection= : The database connection to inspect}
                            {--limit=20 : Maximum number of tables to list}
                            {--json : Output table statistics as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect detailed database table sizes, rows, and fragmentation';

    /**
     * YB - 20-08-2026 Execute the console command.
     */
    public function handle(DatabaseManager $db): int
    {
        $connName = $this->option('connection') ?: config('database-health.connection') ?: config('database.default');
        $connection = $db->connection($connName);
        $limit = (int) $this->option('limit');

        $analyzer = AnalyzerFactory::make($connection);
        $tables = $analyzer->getTableStats($connection);

        if ($this->option('json')) {
            $this->output->writeln((string) json_encode([
                'connection' => $connName,
                'total_tables' => count($tables),
                'tables' => $tables,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line(sprintf(' <fg=white;bg=blue;options=bold> DATABASE TABLE STORAGE BREAKDOWN </>  <fg=gray>Connection: [%s]</>', $connName));
        $this->newLine();

        if (empty($tables)) {
            $this->info('No tables found in this database.');
            return self::SUCCESS;
        }

        $displayed = array_slice($tables, 0, $limit);
        $rows = array_map(function (array $t) {
            $fragColor = $t['fragmentation_percent'] > 25 ? 'red' : ($t['fragmentation_percent'] > 10 ? 'yellow' : 'green');

            return [
                sprintf('<fg=white;options=bold>%s</>', $t['table']),
                $t['engine'],
                number_format($t['rows']),
                $this->formatBytes($t['data_size']),
                $this->formatBytes($t['index_size']),
                sprintf('<fg=cyan;options=bold>%s</>', $this->formatBytes($t['total_size'])),
                sprintf('<fg=%s>%.1f%%</>', $fragColor, $t['fragmentation_percent']),
            ];
        }, $displayed);

        $this->table(['Table Name', 'Engine', 'Estimated Rows', 'Data Size', 'Index Size', 'Total Size', 'Fragmentation'], $rows);

        if (count($tables) > $limit) {
            $this->comment(sprintf('Showing top %d of %d total tables. Use --limit=%d to view all.', $limit, count($tables), count($tables)));
        }
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * YB - 20-08-2026 Format byte count to human-readable size.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
