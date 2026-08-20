<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use LaravelDbTools\LaravelDatabaseHealth\Analyzers\AnalyzerFactory;

class ConnectionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:health:connections 
                            {--connection= : The database connection to inspect}
                            {--json : Output connection statistics as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect active database connections, threads, and limits';

    /**
     * YB - 20-08-2026 Execute the console command.
     */
    public function handle(DatabaseManager $db): int
    {
        $connName = $this->option('connection') ?: config('database-health.connection') ?: config('database.default');
        $connection = $db->connection($connName);

        $analyzer = AnalyzerFactory::make($connection);
        $stats = $analyzer->getConnectionStats($connection);
        $serverInfo = $analyzer->getServerInfo($connection);

        if ($this->option('json')) {
            $this->output->writeln((string) json_encode([
                'connection' => $connName,
                'server' => $serverInfo,
                'stats' => $stats,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line(sprintf(' <fg=white;bg=blue;options=bold> DATABASE CONNECTION METRICS </>  <fg=gray>Connection: [%s]</>', $connName));
        $this->newLine();

        $percent = $stats['usage_percent'];
        $usageColor = $percent >= 90 ? 'red' : ($percent >= 75 ? 'yellow' : 'green');

        $rows = [
            ['Database Driver', $serverInfo['driver']],
            ['Server Version', $serverInfo['version']],
            ['Target Database', $serverInfo['database']],
            ['Active Connections', (string) $stats['active_connections']],
            ['Max Allowed Connections', (string) $stats['max_connections']],
            ['Pool Utilization', sprintf('<fg=%s;options=bold>%.2f%%</>', $usageColor, $percent)],
        ];

        $this->table(['Metric', 'Value'], $rows);
        $this->newLine();

        return self::SUCCESS;
    }
}
