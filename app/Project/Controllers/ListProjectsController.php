<?php

declare(strict_types=1);

namespace App\Project\Controllers;

use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Project\Actions\FindProject;
use App\Project\Project;
use App\Project\ProjectFormOptions;
use App\Project\ProjectStatusEnum;
use App\User\User;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The team's projects as cards, a list or a table, with search, status and
 * label filters and a sort.
 */
final class ListProjectsController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $search = $request->string('search')->trim()->toString() ?: null;
        $status = ProjectStatusEnum::tryFrom($request->string('status')->toString())?->value;
        $sort = in_array($request->query('sort'), FindProject::SORTS, true) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $label = Str::isUuid($request->string('label')->toString()) ? $request->string('label')->toString() : null;
        $view = in_array($request->query('view'), ['list', 'table'], true) ? (string) $request->query('view') : 'grid';
        $favoriteIds = FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Project);

        return Inertia::render('projects/index', [
            'projects' => FindProject::listForTeam($teamId, $user->id, $search, $status, $sort, $direction, $label)
                ->map(fn (Project $project): array => self::listItem($project, $favoriteIds))
                ->values(),
            'filters' => ['search' => $search, 'status' => $status, 'label' => $label, 'sort' => $sort, 'direction' => $direction, 'view' => $view],
            ...ProjectFormOptions::forTeam($teamId),
        ]);
    }

    /**
     * A project with its customer, lead, members and ticket progress, for
     * cards, rows and the project page header.
     *
     * @param  array<int, string>  $favoriteIds
     * @return array<string, mixed>
     */
    public static function listItem(Project $project, array $favoriteIds): array
    {
        return [
            ...$project->toSummary(),
            'customer_name' => $project->getCustomer()->toNullableData()?->getName()->toString(),
            'lead_name' => $project->getLead()->toNullableData()?->getName()->toString(),
            'labels' => $project->getLabels()->toDataCollection()->toTags(),
            'members' => $project->getMembers()->toDataCollection()
                ->map(fn (User $member): array => ['id' => $member->getId()->toString(), 'name' => $member->getName()->toString()])
                ->values()
                ->all(),
            'tickets_count' => $project->getTicketsCount()->toInteger(),
            'closed_tickets_count' => $project->getClosedTicketsCount()->toInteger(),
            'overdue_tickets_count' => $project->getOverdueTicketsCount()->toInteger(),
            'is_favorite' => in_array($project->getId()->toString(), $favoriteIds, true),
            'updated_at' => $project->getUpdatedAt()->toNullableISOString(),
        ];
    }
}
