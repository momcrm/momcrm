<?php

declare(strict_types=1);

namespace App\LeaveRequest\Notifications;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotification;
use App\Inbox\InboxPayload;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\User\UserEloquentModel;

/**
 * Tells an owner or admin that someone asked for time off.
 */
final class LeaveRequestedNotification extends InboxNotification
{
    /**
     * @param  string  $leaveType  the type's name, such as "Annual leave"
     * @param  string  $days  the days in words
     */
    public function __construct(
        public readonly LeaveRequestEloquentModel $leave,
        public readonly UserEloquentModel $member,
        public readonly string $leaveType,
        public readonly string $days,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return InboxPayload::forLeave(
            InboxKindEnum::Assignment,
            $this->leave,
            $this->member,
            "{$this->leaveType}: {$this->days}",
            __('asked for time off'),
            $this->leave->reason,
        );
    }
}
