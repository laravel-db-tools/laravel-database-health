<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Analyzers;

use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

class AnalyzerFactory
{
    /**
     * YB - 20-08-2026 Create appropriate analyzer instance for given connection driver.
     */
    public static function make(ConnectionInterface $connection): DatabaseAnalyzerInterface
    {
        /** @var \Illuminate\Database\Connection $connection */
        $driver = $connection->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => new MySqlAnalyzer(),
            'pgsql' => new PostgresAnalyzer(),
            'sqlite' => new SqliteAnalyzer(),
            'sqlsrv' => new SqlServerAnalyzer(),
            default => throw new InvalidArgumentException("Unsupported database driver [{$driver}] for health analyzer."),
        };
    }
}
