<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Tests\Unit;

use LaravelDbTools\LaravelDatabaseHealth\Enums\HealthStatus;
use LaravelDbTools\LaravelDatabaseHealth\Tests\TestCase;

class HealthStatusTest extends TestCase
{
    /**
     * YB - 20-08-2026 Test health status enum helper methods.
     */
    public function test_enum_helper_methods(): void
    {
        $ok = HealthStatus::OK;
        $warning = HealthStatus::WARNING;
        $critical = HealthStatus::CRITICAL;

        $this->assertTrue($ok->isOk());
        $this->assertFalse($ok->isWarning());
        $this->assertFalse($ok->isCritical());

        $this->assertTrue($warning->isWarning());
        $this->assertFalse($warning->isOk());

        $this->assertTrue($critical->isCritical());
        $this->assertFalse($critical->isOk());

        $this->assertSame('OK', $ok->label());
        $this->assertSame('WARN', $warning->label());
        $this->assertSame('FAIL', $critical->label());

        $this->assertSame('green', $ok->color());
        $this->assertSame('yellow', $warning->color());
        $this->assertSame('red', $critical->color());
    }
}
