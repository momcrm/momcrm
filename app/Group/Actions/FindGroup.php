<?php

declare(strict_types=1);

namespace App\Group\Actions;

use App\Group\GroupEloquentModel;
use App\Group\Properties\Name;
use App\Group\Properties\TeamId;
use Illuminate\Support\Collection;

final class FindGroup
{
    /**
     * Every group in the team, for pickers and the sidebar.
     *
     * @return Collection<int, array{id: string, name: string, color: string, icon: ?string}>
     */
    public static function optionsForTeam(string $teamId): Collection
    {
        return GroupEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get()
            ->map(fn (GroupEloquentModel $group): array => ['id' => $group->id, 'name' => $group->name, 'color' => $group->color, 'icon' => $group->icon]);
    }
}
