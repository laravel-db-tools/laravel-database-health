<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Enums;

enum HealthStatus: string
{
    case OK = 'ok';
    case WARNING = 'warning';
    case CRITICAL = 'critical';

    /**
     * YB - 20-08-2026 Determine if the status represents healthy state.
     */
    public function isOk(): bool
    {
        return $this === self::OK;
    }

    /**
     * YB - 20-08-2026 Determine if the status represents warning state.
     */
    public function isWarning(): bool
    {
        return $this === self::WARNING;
    }

    /**
     * YB - 20-08-2026 Determine if the status represents critical failure.
     */
    public function isCritical(): bool
    {
        return $this === self::CRITICAL;
    }

    /**
     * YB - 20-08-2026 Get formatted terminal label with styling.
     */
    public function label(): string
    {
        return match ($this) {
            self::OK => 'OK',
            self::WARNING => 'WARN',
            self::CRITICAL => 'FAIL',
        };
    }

    /**
     * YB - 20-08-2026 Get console color tag for status.
     */
    public function color(): string
    {
        return match ($this) {
            self::OK => 'green',
            self::WARNING => 'yellow',
            self::CRITICAL => 'red',
        };
    }
}
