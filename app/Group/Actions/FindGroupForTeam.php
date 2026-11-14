<?php

declare(strict_types=1);

namespace App\Group\Actions;

use App\Group\GroupEloquentModel;
use App\Group\Properties\TeamId;

final class FindGroupForTeam
{
    public static function byId(?string $id, ?string $teamId): ?GroupEloquentModel
    {
        if ($id === null || $teamId === null) {
            return null;
        }

        return GroupEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->find($id);
    }
}
