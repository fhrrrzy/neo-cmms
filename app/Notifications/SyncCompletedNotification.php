<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class SyncCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(private array $results, private int $durationSeconds) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Sync completed',
            'duration_seconds' => $this->durationSeconds,
            'results' => $this->results,
        ]);
    }
}
