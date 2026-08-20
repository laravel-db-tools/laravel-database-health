<?php

namespace YashBodar\LaravelDatabaseHealth;

class DatabaseHealth
{
    /**
     * Package version.
     */
    public const VERSION = '1.0.0';

    /**
     * YB - 20-08-2026 Get current package version.
     */
    public function version(): string
    {
        return self::VERSION;
    }
}
