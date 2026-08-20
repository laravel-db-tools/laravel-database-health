<?php

namespace LaravelDbTools\LaravelDatabaseHealth;

use Illuminate\Support\ServiceProvider;
use LaravelDbTools\LaravelDatabaseHealth\Commands\DatabaseHealthCommand;

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

        $this->app->singleton('database-health', function () {
            return new DatabaseHealth();
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
            ]);
        }
    }
}
