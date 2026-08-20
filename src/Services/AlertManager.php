<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Services;

use Illuminate\Support\Facades\Notification;
use LaravelDbTools\LaravelDatabaseHealth\Enums\HealthStatus;
use LaravelDbTools\LaravelDatabaseHealth\Events\DatabaseHealthChecked;
use LaravelDbTools\LaravelDatabaseHealth\Events\DatabaseHealthDegraded;
use LaravelDbTools\LaravelDatabaseHealth\Events\DatabaseHealthFailed;
use LaravelDbTools\LaravelDatabaseHealth\Notifications\DatabaseHealthAlertNotification;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;
use Throwable;

class AlertManager
{
    /**
     * YB - 20-08-2026 Process diagnostic results, dispatch events, and fire alerts if needed.
     *
     * @param array<int, CheckResult> $results
     */
    public function handle(string $connection, array $results, float $durationMs): void
    {
        $criticals = array_values(array_filter($results, fn (CheckResult $r) => $r->status->isCritical()));
        $warnings = array_values(array_filter($results, fn (CheckResult $r) => $r->status->isWarning()));

        $overallStatus = HealthStatus::OK;
        if (!empty($criticals)) {
            $overallStatus = HealthStatus::CRITICAL;
        } elseif (!empty($warnings)) {
            $overallStatus = HealthStatus::WARNING;
        }

        // 1. Dispatch general event
        DatabaseHealthChecked::dispatch($connection, $overallStatus, $results, $durationMs);

        // 2. Dispatch specific events
        if (!empty($criticals)) {
            DatabaseHealthFailed::dispatch($connection, $criticals, $durationMs);
        } elseif (!empty($warnings)) {
            DatabaseHealthDegraded::dispatch($connection, $warnings, $durationMs);
        }

        // 3. Send notifications if enabled
        if (config('database-health.notifications.enabled', false) && !$overallStatus->isOk()) {
            $this->sendNotification($connection, $overallStatus, !empty($criticals) ? $criticals : $warnings, $durationMs);
        }
    }

    /**
     * YB - 20-08-2026 Dispatch notification to configured recipients.
     *
     * @param array<int, CheckResult> $issues
     */
    protected function sendNotification(string $connection, HealthStatus $status, array $issues, float $durationMs): void
    {
        try {
            $recipients = config('database-health.notifications.recipients', []);
            if (empty($recipients)) {
                return;
            }

            $notification = new DatabaseHealthAlertNotification($connection, $status, $issues, $durationMs);
            Notification::route('mail', $recipients)->notify($notification);
        } catch (Throwable) {
            // Error logged or suppressed so command doesn't crash
        }
    }
}
