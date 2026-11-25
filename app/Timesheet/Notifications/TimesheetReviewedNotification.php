<?php

declare(strict_types=1);

namespace App\Timesheet\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotification;
use App\Inbox\InboxPayload;
use App\Timesheet\TimesheetEloquentModel;
use App\Timesheet\TimesheetStatusEnum;
use App\User\UserEloquentModel;

/**
 * Tells a member their timesheet was approved, or sent back with a note.
 */
final class TimesheetReviewedNotification extends InboxNotification
{
    /**
     * @param  string  $period  the period in words
     */
    public function __construct(
        public readonly TimesheetEloquentModel $timesheet,
        public readonly UserEloquentModel $reviewer,
        public readonly string $period,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forTimesheet(
            InboxKindEnum::Update,
            $this->timesheet,
            $this->reviewer,
            "Timesheet: {$this->period}",
            $this->timesheet->status === TimesheetStatusEnum::Approved->value ? __('approved your timesheet') : __('sent your timesheet back'),
            $this->timesheet->review_note,
        );
    }
}
