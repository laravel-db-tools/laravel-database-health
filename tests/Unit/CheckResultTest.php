<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Tests\Unit;

use LaravelDbTools\LaravelDatabaseHealth\Enums\HealthStatus;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use LaravelDbTools\LaravelDatabaseHealth\Tests\TestCase;

class CheckResultTest extends TestCase
{
    /**
     * YB - 20-08-2026 Test check result factory methods.
     */
    public function test_check_result_factories(): void
    {
        $okResult = CheckResult::ok('Ping', 'Connected cleanly', 2.5, ['key' => 'val']);
        $this->assertSame(HealthStatus::OK, $okResult->status);
        $this->assertSame('Ping', $okResult->checkName);
        $this->assertSame('Connected cleanly', $okResult->message);
        $this->assertEquals(2.5, $okResult->durationMs);
        $this->assertSame(['key' => 'val'], $okResult->metadata);

        $warnResult = CheckResult::warning('Latency', 'High latency', 120.0);
        $this->assertSame(HealthStatus::WARNING, $warnResult->status);

        $critResult = CheckResult::critical('Connection', 'Down', 50.0);
        $this->assertSame(HealthStatus::CRITICAL, $critResult->status);

        $array = $okResult->toArray();
        $this->assertSame('Ping', $array['check']);
        $this->assertSame('ok', $array['status']);
        $this->assertSame('Connected cleanly', $array['message']);
        $this->assertSame(2.5, $array['duration_ms']);
    }
}
