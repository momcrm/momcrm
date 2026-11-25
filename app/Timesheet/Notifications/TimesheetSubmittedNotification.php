<?php

declare(strict_types=1);

namespace App\Timesheet\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotification;
use App\Inbox\InboxPayload;
use App\Timesheet\TimesheetEloquentModel;
use App\User\UserEloquentModel;

/**
 * Tells an owner or admin that a timesheet is waiting for approval.
 */
final class TimesheetSubmittedNotification extends InboxNotification
{
    /**
     * @param  string  $period  the period in words
     */
    public function __construct(
        public readonly TimesheetEloquentModel $timesheet,
        public readonly UserEloquentModel $member,
        public readonly string $period,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forTimesheet(InboxKindEnum::Assignment, $this->timesheet, $this->member, "Timesheet: {$this->period}", __('submitted their timesheet'));
    }
}
