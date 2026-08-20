<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Results;

use LaravelDbTools\LaravelDatabaseHealth\Enums\HealthStatus;

class CheckResult
{
    /**
     * YB - 20-08-2026 Constructor for immutable diagnostic check result.
     *
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $checkName,
        public readonly HealthStatus $status,
        public readonly string $message,
        public readonly float $durationMs = 0.0,
        public readonly array $metadata = []
    ) {
    }

    /**
     * YB - 20-08-2026 Factory for OK result.
     *
     * @param array<string, mixed> $metadata
     */
    public static function ok(
        string $checkName,
        string $message,
        float $durationMs = 0.0,
        array $metadata = []
    ): self {
        return new self($checkName, HealthStatus::OK, $message, $durationMs, $metadata);
    }

    /**
     * YB - 20-08-2026 Factory for WARNING result.
     *
     * @param array<string, mixed> $metadata
     */
    public static function warning(
        string $checkName,
        string $message,
        float $durationMs = 0.0,
        array $metadata = []
    ): self {
        return new self($checkName, HealthStatus::WARNING, $message, $durationMs, $metadata);
    }

    /**
     * YB - 20-08-2026 Factory for CRITICAL failure result.
     *
     * @param array<string, mixed> $metadata
     */
    public static function critical(
        string $checkName,
        string $message,
        float $durationMs = 0.0,
        array $metadata = []
    ): self {
        return new self($checkName, HealthStatus::CRITICAL, $message, $durationMs, $metadata);
    }

    /**
     * YB - 20-08-2026 Convert result to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'check' => $this->checkName,
            'status' => $this->status->value,
            'message' => $this->message,
            'duration_ms' => round($this->durationMs, 2),
            'metadata' => $this->metadata,
        ];
    }
}
