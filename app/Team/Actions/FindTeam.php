<?php

declare(strict_types=1);

namespace App\Team\Actions;

use App\Shared\Table;
use App\Team\Properties\Id;
use App\Team\Properties\Name;
use App\Team\Team;
use App\Team\TeamEloquentModel;
use App\TeamUser\Properties\CreatedAt;
use App\TeamUser\Properties\Id as TeamUserId;
use App\TeamUser\Properties\TeamId;
use App\TeamUser\Properties\UserId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class FindTeam
{
    public static function byId(?string $id): Team
    {
        if ($id === null) {
            return Team::new();
        }

        return Team::fromEloquentModel(TeamEloquentModel::query()->find($id));
    }

    /**
     * Find a team only if the given user belongs to it.
     */
    public static function byIdForMember(?string $id, string $userId): Team
    {
        if ($id === null) {
            return Team::new();
        }

        $team = self::memberOf($userId)
            ->where(Id::getDatabaseTableColumnNameWithTable(Table::Teams), $id)
            ->first();

        return Team::fromEloquentModel($team);
    }

    /**
     * The first team the user joined, used as a fallback current team.
     */
    public static function firstForMember(string $userId): Team
    {
        $team = self::memberOf($userId)
            ->orderBy(CreatedAt::getDatabaseTableColumnNameWithTable(Table::TeamUser))
            ->orderBy(TeamUserId::getDatabaseTableColumnNameWithTable(Table::TeamUser))
            ->first();

        return Team::fromEloquentModel($team);
    }

    /**
     * @return Collection<int, Team>
     */
    public static function allForMember(string $userId): Collection
    {
        $teams = self::memberOf($userId)
            ->orderBy(Name::getDatabaseTableColumnNameWithTable(Table::Teams))
            ->get();

        return Team::collect($teams->all(), 'fromEloquentModel');
    }

    /**
     * @return Builder<TeamEloquentModel>
     */
    private static function memberOf(string $userId): Builder
    {
        return TeamEloquentModel::query()
            ->select(Table::Teams->value.'.*')
            ->join(
                Table::TeamUser->value,
                TeamId::getDatabaseTableColumnNameWithTable(Table::TeamUser),
                '=',
                Id::getDatabaseTableColumnNameWithTable(Table::Teams),
            )
            ->where(UserId::getDatabaseTableColumnNameWithTable(Table::TeamUser), $userId);
    }
}
