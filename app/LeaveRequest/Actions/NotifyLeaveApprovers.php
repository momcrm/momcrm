<?php

declare(strict_types=1);

namespace App\LeaveRequest\Actions;

use App\LeaveRequest\LeaveRequest;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\LeaveRequest\Notifications\LeaveRequestedNotification;
use App\LeaveRequest\Notifications\LeaveReviewedNotification;
use App\Shared\Table;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Sends leave requests to the team's owners and admins, and their answers
 * back to the member. Nobody is told about their own action.
 */
final class NotifyLeaveApprovers
{
    public static function ofRequest(LeaveRequest $leave, string $leaveType): void
    {
        $model = self::model($leave);
        $member = UserEloquentModel::query()->find($leave->getUserId()->toString());

        if ($model === null || $member === null) {
            return;
        }

        UserEloquentModel::query()
            ->whereIn('id', DB::table(Table::TeamUser->value)
                ->where('team_id', $leave->getTeamId()->toString())
                ->whereIn('role', [TeamRoleEnum::Owner->value, TeamRoleEnum::Admin->value])
                ->where('user_id', '!=', $member->id)
                ->pluck('user_id')
                ->all())
            ->get()
            ->each(fn (UserEloquentModel $approver) => $approver->notify(new LeaveRequestedNotification($model, $member, $leaveType, $leave->describeDays())));
    }

    public static function ofDecision(LeaveRequest $leave, string $reviewerId, string $leaveType): void
    {
        $model = self::model($leave);
        $reviewer = UserEloquentModel::query()->find($reviewerId);

        if ($model === null || $reviewer === null || $reviewerId === $leave->getUserId()->toString()) {
            return;
        }

        UserEloquentModel::query()->find($leave->getUserId()->toString())
            ?->notify(new LeaveReviewedNotification($model, $reviewer, $leaveType, $leave->describeDays()));
    }

    private static function model(LeaveRequest $leave): ?LeaveRequestEloquentModel
    {
        return LeaveRequestEloquentModel::query()->withoutGlobalScopes()->find($leave->getId()->toString());
    }
}
