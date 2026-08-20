<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Tests\Feature;

use Illuminate\Support\Facades\Event;
use LaravelDbTools\LaravelDatabaseHealth\Events\DatabaseHealthChecked;
use LaravelDbTools\LaravelDatabaseHealth\Facades\DatabaseHealth;
use LaravelDbTools\LaravelDatabaseHealth\Tests\TestCase;

class EventsTest extends TestCase
{
    /**
     * YB - 20-08-2026 Test that DatabaseHealthChecked event is dispatched during checks.
     */
    public function test_it_dispatches_health_checked_event(): void
    {
        Event::fake([DatabaseHealthChecked::class]);

        DatabaseHealth::check();

        Event::assertDispatched(DatabaseHealthChecked::class, function ($event) {
            return $event->status->isOk() && count($event->results) === 9;
        });
    }
}
