<?php

declare(strict_types=1);

namespace App\Timesheet\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxPayload;
use App\Timesheet\TimesheetEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;

/**
 * Tells an owner or admin that a timesheet is waiting for approval.
 */
final class TimesheetSubmittedNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * @param  string  $period  the period in words
     */
    public function __construct(
        public readonly TimesheetEloquentModel $timesheet,
        public readonly UserEloquentModel $member,
        public readonly string $period,
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
        return InboxPayload::forTimesheet(InboxKindEnum::Assignment, $this->timesheet, $this->member, "Timesheet: {$this->period}", __('submitted their timesheet'));
    }
}
