<?php

declare(strict_types=1);

namespace App\Timesheet\Actions;

use App\Shared\Table;
use App\TeamUser\TeamRoleEnum;
use App\Timesheet\Notifications\TimesheetReviewedNotification;
use App\Timesheet\Notifications\TimesheetSubmittedNotification;
use App\Timesheet\Timesheet;
use App\Timesheet\TimesheetEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Sends submitted timesheets to the team's owners and admins, and their
 * answers back to the member. Nobody is told about their own action.
 */
final class NotifyTimesheetReviewers
{
    public static function ofSubmission(Timesheet $timesheet): void
    {
        $model = self::model($timesheet);
        $member = UserEloquentModel::query()->find($timesheet->getUserId()->toString());

        if ($model === null || $member === null) {
            return;
        }

        UserEloquentModel::query()
            ->whereIn('id', DB::table(Table::TeamUser->value)
                ->where('team_id', $timesheet->getTeamId()->toString())
                ->whereIn('role', [TeamRoleEnum::Owner->value, TeamRoleEnum::Admin->value])
                ->where('user_id', '!=', $member->id)
                ->pluck('user_id')
                ->all())
            ->get()
            ->each(fn (UserEloquentModel $reviewer) => $reviewer->notify(new TimesheetSubmittedNotification($model, $member, self::describe($timesheet))));
    }

    public static function ofDecision(Timesheet $timesheet, string $reviewerId): void
    {
        $model = self::model($timesheet);
        $reviewer = UserEloquentModel::query()->find($reviewerId);

        if ($model === null || $reviewer === null || $reviewerId === $timesheet->getUserId()->toString()) {
            return;
        }

        UserEloquentModel::query()->find($timesheet->getUserId()->toString())
            ?->notify(new TimesheetReviewedNotification($model, $reviewer, self::describe($timesheet)));
    }

    /**
     * The period in words, such as "Mon 16 Nov – Sun 22 Nov".
     */
    public static function describe(Timesheet $timesheet): string
    {
        return $timesheet->getStartsOn()->toCarbon()->translatedFormat('D j M').' – '.$timesheet->getEndsOn()->toCarbon()->translatedFormat('D j M');
    }

    private static function model(Timesheet $timesheet): ?TimesheetEloquentModel
    {
        return TimesheetEloquentModel::query()->withoutGlobalScopes()->find($timesheet->getId()->toString());
    }
}
