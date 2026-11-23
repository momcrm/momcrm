<?php

declare(strict_types=1);

namespace App\LeaveRequest\Actions;

use App\LeaveRequest\LeavePartEnum;
use App\LeaveRequest\LeaveRequest;
use App\LeaveRequest\LeaveRequestCollection;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\LeaveRequest\LeaveStatusEnum;
use App\LeaveRequest\Properties\CreatedAt;
use App\LeaveRequest\Properties\EndsOn;
use App\LeaveRequest\Properties\Id;
use App\LeaveRequest\Properties\StartsOn;
use App\LeaveRequest\Properties\Status;
use App\LeaveRequest\Properties\TeamId;
use App\LeaveRequest\Properties\UserId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

final class FindLeaveRequest
{
    public static function byIdForTeam(?string $id, ?string $teamId): LeaveRequest
    {
        if ($id === null || $teamId === null) {
            return LeaveRequest::new();
        }

        return LeaveRequest::fromEloquentModel(self::query($teamId)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->first());
    }

    /**
     * A member's requests, latest leave first.
     */
    public static function forMember(string $teamId, string $userId, int $limit = 100): LeaveRequestCollection
    {
        return LeaveRequest::collect(self::query($teamId)
            ->where(UserId::getDatabaseTableColumnName(), $userId)
            ->orderByDesc(StartsOn::getDatabaseTableColumnName())
            ->orderByDesc(CreatedAt::getDatabaseTableColumnName())
            ->limit($limit)
            ->get()->all(), 'fromEloquentModel');
    }

    /**
     * Requests waiting for an owner or admin, soonest leave first.
     */
    public static function pendingForTeam(string $teamId): LeaveRequestCollection
    {
        return LeaveRequest::collect(self::query($teamId)
            ->where(Status::getDatabaseTableColumnName(), LeaveStatusEnum::Pending->value)
            ->orderBy(StartsOn::getDatabaseTableColumnName())
            ->get()->all(), 'fromEloquentModel');
    }

    /**
     * Approved leave touching any day from one date to another, inclusive.
     */
    public static function approvedBetweenForTeam(string $teamId, string $from, string $to): LeaveRequestCollection
    {
        return LeaveRequest::collect(self::query($teamId)
            ->where(Status::getDatabaseTableColumnName(), LeaveStatusEnum::Approved->value)
            ->where(StartsOn::getDatabaseTableColumnName(), '<=', $to)
            ->where(EndsOn::getDatabaseTableColumnName(), '>=', $from)
            ->orderBy(StartsOn::getDatabaseTableColumnName())
            ->get()->all(), 'fromEloquentModel');
    }

    /**
     * Whether the member already has pending or approved leave on any of
     * these days. A morning and an afternoon on the same day don't clash.
     */
    public static function overlapsForMember(string $teamId, string $userId, string $startsOn, string $endsOn, LeavePartEnum $part): bool
    {
        return self::query($teamId)
            ->where(UserId::getDatabaseTableColumnName(), $userId)
            ->whereIn(Status::getDatabaseTableColumnName(), LeaveStatusEnum::active())
            ->where(StartsOn::getDatabaseTableColumnName(), '<=', $endsOn)
            ->where(EndsOn::getDatabaseTableColumnName(), '>=', $startsOn)
            ->get()
            ->contains(function (LeaveRequestEloquentModel $other) use ($part): bool {
                $otherPart = LeavePartEnum::from($other->part);

                return ! ($part->isHalfDay() && $otherPart->isHalfDay() && $part !== $otherPart);
            });
    }

    /**
     * @return Builder<LeaveRequestEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return LeaveRequestEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
