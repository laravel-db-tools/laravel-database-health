<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \LaravelDbTools\LaravelDatabaseHealth\DatabaseHealth
 */
class DatabaseHealth extends Facade
{
    /**
     * YB - 20-08-2026 Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'database-health';
    }
}
