<?php

namespace YashBodar\LaravelDatabaseHealth\Commands;

use Illuminate\Console\Command;

class DatabaseHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run database health and diagnostic checks';

    /**
     * YB - 20-08-2026 Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Laravel Database Health');
        $this->comment('Package foundation initialized successfully. Health checks will be executed here.');

        return self::SUCCESS;
    }
}
