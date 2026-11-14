<?php

declare(strict_types=1);

namespace App\TeamUser\Actions;

use App\TeamUser\Properties\TeamId;
use App\TeamUser\Properties\UserId;
use App\TeamUser\TeamUser;
use App\TeamUser\TeamUserEloquentModel;

final class FindTeamUser
{
    public static function byTeamAndUser(string $teamId, string $userId): TeamUser
    {
        $teamUser = TeamUserEloquentModel::query()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(UserId::getDatabaseTableColumnName(), $userId)
            ->first();

        return TeamUser::fromEloquentModel($teamUser);
    }
}
