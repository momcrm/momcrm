<?php

declare(strict_types=1);

namespace App\Group\Controllers;

use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Group\Actions\FindGroupForTeam;
use App\Group\Exceptions\CouldNotFindGroup;
use App\Group\GroupRoleEnum;
use App\Project\Actions\FindProject;
use App\Project\Controllers\ListProjectsController;
use App\Project\ProjectEloquentModel;
use App\Shared\ColorEnum;
use App\TeamUser\Actions\FindTeamMembers;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\DueOn;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\ProjectId;
use App\Ticket\Properties\Status;
use App\Ticket\TicketDrawer;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketRelationship;
use App\Ticket\TicketStatusEnum;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A group's page: its projects, the open tickets in them and its members.
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
        $model = FindGroupForTeam::byId($group, $teamId);

        if ($model === null) {
            throw CouldNotFindGroup::notFound();
        }

        $projects = FindProject::listForTeam($teamId, $user->id)->where('group_id', $model->id)->values();
        $favoriteProjectIds = FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Project);

        return Inertia::render('groups/show', [
            'group' => [
                'id' => $model->id,
                'name' => $model->name,
                'description' => $model->description,
                'color' => $model->color,
                'icon' => $model->icon,
            ],
            'members' => $model->members()
                ->orderBy('name')
                ->get()
                ->map(fn (UserEloquentModel $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->getRelationValue('pivot')?->getAttribute('role') ?? GroupRoleEnum::Member->value,
                ])
                ->values(),
            'projects' => $projects->map(fn (ProjectEloquentModel $project): array => ListProjectsController::listItem($project, $favoriteProjectIds))->values(),
            'tickets' => TicketEloquentModel::query()
                ->withoutGlobalScopes()
                ->with([TicketRelationship::Project->value, TicketRelationship::Assignee->value, TicketRelationship::Customer->value, TicketRelationship::Labels->value])
                ->whereIn(ProjectId::getDatabaseTableColumnName(), $projects->modelKeys())
                ->whereNull(ArchivedAt::getDatabaseTableColumnName())
                ->whereNull(ParentId::getDatabaseTableColumnName())
                ->whereIn(Status::getDatabaseTableColumnName(), TicketStatusEnum::openValues())
                ->orderByRaw('due_on is null, due_on')
                ->orderBy(DueOn::getDatabaseTableColumnName())
                ->limit(15)
                ->get()
                ->map(fn (TicketEloquentModel $ticket): array => ListTicketsController::listItem($ticket))
                ->values(),
            'teamMembers' => FindTeamMembers::optionsForTeam($teamId),
            'colors' => ColorEnum::values(),
            'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
            ...TicketDrawer::options($teamId),
            'drawer' => TicketDrawer::forRequest($request),
        ]);
    }
}
