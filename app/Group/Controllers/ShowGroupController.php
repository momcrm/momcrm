<?php

declare(strict_types=1);

namespace App\Group\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Group\Actions\FindGroup;
use App\Group\Actions\FindGroupForTeam;
use App\Group\Exceptions\CouldNotFindGroup;
use App\Group\Group;
use App\Project\Actions\FindProject;
use App\Project\Controllers\ListProjectsController;
use App\Project\Project;
use App\Team\Scopes\CurrentTeamScope;
use App\TeamUser\Actions\FindTeamMembers;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\DueOn;
use App\Ticket\Properties\GroupId;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\ProjectId;
use App\Ticket\Properties\StatusId;
use App\Ticket\Ticket;
use App\Ticket\TicketDrawer;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketRelationship;
use App\Ticket\TicketStatusCategoryEnum;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A group's page: its projects, its open tickets (its own and those in its
 * projects) and its members.
 */
final class ShowGroupController
{
    /**
     * @throws CouldNotFindGroup
     */
    public function __invoke(Request $request, string $group): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $found = FindGroupForTeam::byId($group, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindGroup::notFound();
        }

        $groupId = $found->getId()->toString();

        $projects = FindProject::listForTeam($teamId, $user->id)
            ->filter(fn (Project $project): bool => $project->getGroupId()->equalsTo($groupId))
            ->values();
        $favoriteProjectIds = FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Project);

        return Inertia::render('groups/show', [
            'group' => [
                'id' => $groupId,
                'name' => $found->getName()->toString(),
                'description' => $found->getDescription()->toNullableString(),
                'hue' => $found->getHue()->toNullableInteger(),
                'icon' => $found->getIcon()->toNullableString(),
            ],
            'members' => FindGroup::membersOf($groupId),
            'projects' => $projects->map(fn (Project $project): array => ListProjectsController::listItem($project, $favoriteProjectIds))->values(),
            'tickets' => TicketEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->with([TicketRelationship::Project->value, TicketRelationship::Assignee->value, TicketRelationship::Customer->value, TicketRelationship::Labels->value])
                ->where(fn (Builder $query) => $query
                    ->where(GroupId::getDatabaseTableColumnName(), $groupId)
                    ->orWhereIn(ProjectId::getDatabaseTableColumnName(), $projects->map(fn (Project $project): string => $project->getId()->toString())->all()))
                ->whereNull(ArchivedAt::getDatabaseTableColumnName())
                ->whereNull(ParentId::getDatabaseTableColumnName())
                ->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Open->statusIds())
                ->orderByRaw('due_on is null, due_on')
                ->orderBy(DueOn::getDatabaseTableColumnName())
                ->limit(15)
                ->get()
                ->map(fn (TicketEloquentModel $model): array => ListTicketsController::listItem(Ticket::fromEloquentModel($model)))
                ->values(),
            'teamMembers' => FindTeamMembers::optionsForTeam($teamId),
            'can' => AccessAbilities::for($user, AccessResourceEnum::Group, $found),
            'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
            ...TicketDrawer::options($teamId, $user->id),
            'drawer' => TicketDrawer::forRequest($request),
        ]);
    }
}
