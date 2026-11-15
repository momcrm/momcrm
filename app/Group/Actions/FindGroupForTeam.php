<?php

declare(strict_types=1);

namespace App\Group\Actions;

use App\Group\Group;
use App\Group\GroupEloquentModel;
use App\Group\Properties\TeamId;

final class FindGroupForTeam
{
    public static function byId(?string $id, ?string $teamId): Group
    {
        if ($id === null || $teamId === null) {
            return Group::new();
        }

        return Group::fromEloquentModel(GroupEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->find($id));
    }
}
