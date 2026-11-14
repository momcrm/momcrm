<?php

declare(strict_types=1);

namespace App\Milestone\Actions;

use App\Milestone\Milestone;
use App\Milestone\MilestoneEloquentModel;
use App\Milestone\Properties\Id;
use App\Milestone\Properties\TeamId;
use App\Project\Actions\FindProject;
use App\Team\Scopes\CurrentTeamScope;

final class FindMilestone
{
    /**
     * A milestone of a project the person may see in their team.
     */
    public static function visibleByIdForTeam(?string $id, ?string $teamId, string $userId): Milestone
    {
        if ($id === null || $teamId === null) {
            return Milestone::new();
        }

        $milestone = Milestone::fromEloquentModel(MilestoneEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->first());

        if ($milestone->isNull() || FindProject::visibleByIdForTeam($milestone->getProjectId()->toString(), $teamId, $userId)->isNull()) {
            return Milestone::new();
        }

        return $milestone;
    }
}
