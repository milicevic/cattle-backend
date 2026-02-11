<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CalvingDueSoonNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $tagNumber,
        public ?string $name,
        public int $daysRemaining,
        public string $expectedCalvingDate,
        public string $priority = 'medium'
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'calving_due_soon',
            'priority' => $this->priority,
            'message' => "{$this->tagNumber} is due to calve in {$this->daysRemaining} days",
            'tag_number' => $this->tagNumber,
            'name' => $this->name,
            'days_remaining' => $this->daysRemaining,
            'expected_calving_date' => $this->expectedCalvingDate,
        ];
    }
}
