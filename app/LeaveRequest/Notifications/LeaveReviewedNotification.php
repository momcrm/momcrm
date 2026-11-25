<?php

declare(strict_types=1);

namespace App\LeaveRequest\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotification;
use App\Inbox\InboxPayload;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\LeaveRequest\LeaveStatusEnum;
use App\User\UserEloquentModel;

/**
 * Tells a member their leave was approved or declined, with the note.
 */
final class LeaveReviewedNotification extends InboxNotification
{
    /**
     * @param  string  $leaveType  the type's name, such as "Annual leave"
     * @param  string  $days  the days in words
     */
    public function __construct(
        public readonly LeaveRequestEloquentModel $leave,
        public readonly UserEloquentModel $reviewer,
        public readonly string $leaveType,
        public readonly string $days,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forLeave(
            InboxKindEnum::Update,
            $this->leave,
            $this->reviewer,
            "{$this->leaveType}: {$this->days}",
            $this->leave->status === LeaveStatusEnum::Approved->value ? __('approved your leave') : __('declined your leave'),
            $this->leave->review_note,
        );
    }
}
