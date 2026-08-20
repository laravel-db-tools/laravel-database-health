<?php

namespace LaravelDbTools\LaravelDatabaseHealth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use LaravelDbTools\LaravelDatabaseHealth\Enums\HealthStatus;
use LaravelDbTools\LaravelDatabaseHealth\Results\CheckResult;

class DatabaseHealthAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * YB - 20-08-2026 Constructor for DatabaseHealthAlertNotification.
     *
     * @param array<int, CheckResult> $failedResults
     */
    public function __construct(
        public readonly string $connection,
        public readonly HealthStatus $status,
        public readonly array $failedResults,
        public readonly float $durationMs
    ) {
    }

    /**
     * YB - 20-08-2026 Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return config('database-health.notifications.channels', ['mail']);
    }

    /**
     * YB - 20-08-2026 Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $level = $this->status->isCritical() ? 'error' : 'warning';
        $appName = config('app.name', 'Laravel Application');

        $mail = (new MailMessage())
            ->level($level)
            ->subject(sprintf('[%s] %s Alert: Database Health [%s]', $appName, strtoupper($this->status->value), $this->connection))
            ->greeting('Database Health Alert')
            ->line(sprintf('Database connection **%s** reported issues on environment **%s**.', $this->connection, config('app.env')));

        foreach ($this->failedResults as $result) {
            $mail->line(sprintf('• **%s** (%s): %s', $result->checkName, $result->status->label(), $result->message));
        }

        $mail->line(sprintf('Total diagnostic execution duration: **%.2f ms**.', $this->durationMs));

        return $mail;
    }

    /**
     * YB - 20-08-2026 Get array representation of notification.
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'connection' => $this->connection,
            'status' => $this->status->value,
            'duration_ms' => $this->durationMs,
            'issues' => array_map(fn (CheckResult $r) => $r->toArray(), $this->failedResults),
        ];
    }
}
