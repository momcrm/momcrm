<?php

declare(strict_types=1);

namespace App\Label\Actions;

use App\Label\LabelEloquentModel;
use App\Label\Properties\Name;
use App\Label\Properties\TeamId;
use Illuminate\Support\Collection;

final class FindLabel
{
    /**
     * Every label in the team, for pickers and filters.
     *
     * @return Collection<int, array{id: string, name: string, color: string}>
     */
    public static function optionsForTeam(string $teamId): Collection
    {
        return LabelEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get()
            ->map(fn (LabelEloquentModel $label): array => ['id' => $label->id, 'name' => $label->name, 'color' => $label->color]);
    }
}
