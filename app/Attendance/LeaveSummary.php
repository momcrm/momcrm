<?php

declare(strict_types=1);

namespace App\Attendance;

use App\LeaveRequest\LeaveRequest;
use App\LeaveRequest\LeaveStatusEnum;
use Carbon\CarbonImmutable;

/**
 * A leave request as the Leave page shows it: who, what kind, which days
 * and how many working days that is, where it is, and whether the viewer may
 * still cancel it.
 */
final class LeaveSummary
{
    /**
     * @param  array<string, string>  $names  user id => name
     * @param  array<string, array{name: string, hue: ?int}>  $types  leave type id => name and colour
     * @return array{id: string, user_id: string, user_name: string, leave_type_id: string, leave_type: string, hue: ?int, starts_on: string, ends_on: string, part: string, days: ?float, status: string, reason: ?string, reviewed_by_name: ?string, reviewed_at: ?string, review_note: ?string, created_at: ?string, can_cancel: bool}
     */
    public static function toArray(LeaveRequest $leave, array $names, array $types, AttendanceCalendar $calendar, string $timezone, string $viewerId, bool $canManage): array
    {
        $userId = $leave->getUserId()->toString();
        $type = $types[$leave->getLeaveTypeId()->toString()] ?? ['name' => __('Leave'), 'hue' => null];
        $reviewer = $leave->getReviewedBy()->toNullableString();

        return [
            'id' => $leave->getId()->toString(),
            'user_id' => $userId,
            'user_name' => $names[$userId] ?? __('Someone who left'),
            'leave_type_id' => $leave->getLeaveTypeId()->toString(),
            'leave_type' => $type['name'],
            'hue' => $type['hue'],
            'starts_on' => $leave->getStartsOn()->toDateString(),
            'ends_on' => $leave->getEndsOn()->toDateString(),
            'part' => $leave->getPart()->toEnum()->value,
            'days' => $calendar->workdaysOf($leave, $timezone),
            'status' => $leave->getStatus()->toEnum()->value,
            'reason' => $leave->getReason()->toNullableString(),
            'reviewed_by_name' => $reviewer === null ? null : ($names[$reviewer] ?? null),
            'reviewed_at' => $leave->getReviewedAt()->toNullableISOString(),
            'review_note' => $leave->getReviewNote()->toNullableString(),
            'created_at' => $leave->getCreatedAt()->toNullableISOString(),
            'can_cancel' => self::canCancel($leave, $timezone, $viewerId, $canManage),
        ];
    }

    private static function canCancel(LeaveRequest $leave, string $timezone, string $viewerId, bool $canManage): bool
    {
        $leaveStatus = $leave->getStatus()->toEnum();

        if (! in_array($leaveStatus, [LeaveStatusEnum::Pending, LeaveStatusEnum::Approved], true)) {
            return false;
        }

        if ($canManage) {
            return true;
        }

        return $leave->getUserId()->toNullableString() === $viewerId
            && ($leaveStatus === LeaveStatusEnum::Pending || $leave->getStartsOn()->toDateString() > CarbonImmutable::now()->setTimezone($timezone)->toDateString());
    }
}
