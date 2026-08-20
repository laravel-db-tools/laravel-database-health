<?php

namespace LaravelDbTools\LaravelDatabaseHealth;

use Illuminate\Support\ServiceProvider;
use LaravelDbTools\LaravelDatabaseHealth\Commands\ConnectionsCommand;
use LaravelDbTools\LaravelDatabaseHealth\Commands\DatabaseHealthCommand;
use LaravelDbTools\LaravelDatabaseHealth\Commands\IndexesCommand;
use LaravelDbTools\LaravelDatabaseHealth\Commands\OptimizeCommand;
use LaravelDbTools\LaravelDatabaseHealth\Commands\TablesCommand;
use LaravelDbTools\LaravelDatabaseHealth\Services\AlertManager;
use LaravelDbTools\LaravelDatabaseHealth\Services\DatabaseHealthRunner;

class DatabaseHealthServiceProvider extends ServiceProvider
{
    /**
     * YB - 20-08-2026 Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/database-health.php',
            'database-health'
        );

        $this->app->singleton(AlertManager::class, function () {
            return new AlertManager();
        });

        $this->app->singleton(DatabaseHealthRunner::class, function ($app) {
            return new DatabaseHealthRunner(
                $app['db'],
                $app,
                $app->make(AlertManager::class)
            );
        });

        $this->app->singleton('database-health', function ($app) {
            return new DatabaseHealth($app->make(DatabaseHealthRunner::class));
        });
    }

    /**
     * YB - 20-08-2026 Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/database-health.php' => config_path('database-health.php'),
            ], 'database-health-config');

            $this->commands([
                DatabaseHealthCommand::class,
                TablesCommand::class,
                ConnectionsCommand::class,
                IndexesCommand::class,
                OptimizeCommand::class,
            ]);
        }
    }
}
