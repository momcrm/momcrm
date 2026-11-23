<?php

declare(strict_types=1);

namespace App\Shift\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxPayload;
use App\Shift\ShiftEloquentModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;

/**
 * Tells a member they are still clocked in long after their day should have
 * ended, so they can clock out or have a manager correct the time.
 */
final class ShiftStillOpenNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * @param  string  $clockedIn  when they clocked in, as they would read it
     */
    public function __construct(
        public readonly ShiftEloquentModel $shift,
        public readonly string $clockedIn,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forShift(
            InboxKindEnum::Update,
            $this->shift,
            __('You’re still clocked in'),
            __('you clocked in :time. Clock out, or ask a manager to correct your time.', ['time' => $this->clockedIn]),
        );
    }
}
