<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InseminationDueNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $tagNumber,
        public ?string $name,
        public int $daysSinceCalving,
        public int $daysUntilIdeal,
        public bool $isOverdue,
        public bool $isInWindow,
        public bool $isApproaching,
        public string $lastCalvingDate,
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
        $message = '';
        if ($this->isOverdue) {
            $daysOverdue = $this->daysSinceCalving - 90;
            $message = "{$this->tagNumber} is {$daysOverdue} days overdue for insemination";
        } elseif ($this->isInWindow) {
            $daysInWindow = $this->daysSinceCalving - 50;
            $message = "{$this->tagNumber} is in ideal insemination window ({$daysInWindow} days into window)";
        } else {
            $message = "{$this->tagNumber} is approaching insemination window ({$this->daysUntilIdeal} days until ideal start)";
        }

        return [
            'type' => 'insemination_due',
            'priority' => $this->priority,
            'message' => $message,
            'tag_number' => $this->tagNumber,
            'name' => $this->name,
            'days_since_calving' => $this->daysSinceCalving,
            'days_until_ideal' => max(0, $this->daysUntilIdeal),
            'is_overdue' => $this->isOverdue,
            'is_in_window' => $this->isInWindow,
            'is_approaching' => $this->isApproaching,
            'last_calving_date' => $this->lastCalvingDate,
        ];
    }
}
