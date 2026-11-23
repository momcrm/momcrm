<?php

declare(strict_types=1);

namespace App\Attendance;

use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceListEnum;
use App\LeaveRequest\Actions\FindLeaveRequest;
use App\LeaveRequest\LeaveRequest;

/**
 * Approved leave for the calendar, as all-day entries next to tickets. What
 * kind of leave (and its colour) is shown only to owners and admins and to
 * the person on leave; everyone else just sees that they're away.
 */
final class LeaveCalendarLayer
{
    /**
     * @return list<array{id: string, user_id: string, name: string, leave_type: ?string, hue: ?int, starts_on: string, ends_on: string, part: string}>
     */
    public static function forTeam(string $teamId, string $from, string $to, string $viewerId, bool $canSeeEveryone): array
    {
        $names = AttendanceTimezone::membersOfTeam($teamId)->mapWithKeys(fn (array $member): array => [$member['id'] => $member['name']])->all();
        $types = FindChoice::listForTeam($teamId, ChoiceListEnum::LeaveType, withArchived: true)
            ->mapWithKeys(fn (Choice $type): array => [$type->getId()->toString() => ['name' => $type->getName()->toString(), 'hue' => $type->getHue()->toNullableInteger()]])
            ->all();

        return array_values(FindLeaveRequest::approvedBetweenForTeam($teamId, $from, $to)
            ->filter(fn (LeaveRequest $leave): bool => isset($names[$leave->getUserId()->toString()]))
            ->map(function (LeaveRequest $leave) use ($names, $types, $viewerId, $canSeeEveryone): array {
                $userId = $leave->getUserId()->toString();
                $showType = $canSeeEveryone || $userId === $viewerId;
                $type = $types[$leave->getLeaveTypeId()->toString()] ?? null;

                return [
                    'id' => $leave->getId()->toString(),
                    'user_id' => $userId,
                    'name' => $names[$userId],
                    'leave_type' => $showType ? $type['name'] ?? null : null,
                    'hue' => $showType ? $type['hue'] ?? null : null,
                    'starts_on' => $leave->getStartsOn()->toDateString(),
                    'ends_on' => $leave->getEndsOn()->toDateString(),
                    'part' => $leave->getPart()->toEnum()->value,
                ];
            })
            ->all());
    }
}
