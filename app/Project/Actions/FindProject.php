<?php

declare(strict_types=1);

namespace App\Project\Actions;

use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\Project\Properties\ArchivedAt;
use App\Project\Properties\Id;
use App\Project\Properties\Name;
use App\Project\Properties\TeamId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class FindProject
{
    public static function byIdForTeam(?string $id, ?string $teamId): Project
    {
        if ($id === null || $teamId === null) {
            return Project::new();
        }

        return Project::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * The team's live projects as pickers need them: id, name, colour and customer.
     *
     * @return Collection<int, array{id: string, name: string, color: string, customer_id: ?string}>
     */
    public static function optionsForTeam(string $teamId): Collection
    {
        return self::query($teamId)
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get()
            ->map(fn (ProjectEloquentModel $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'customer_id' => $project->customer_id,
            ]);
    }

    /**
     * @return Builder<ProjectEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return ProjectEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
