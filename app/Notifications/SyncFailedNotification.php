<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class SyncFailedNotification extends Notification
{
    use Queueable;

    public function __construct(private string $errorMessage) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Sync failed',
            'error' => $this->errorMessage,
        ]);
    }
}
