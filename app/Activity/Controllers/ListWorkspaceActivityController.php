<?php

declare(strict_types=1);

namespace App\Activity\Controllers;

use App\Activity\Activity;
use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityRelationship;
use App\Activity\ActivityTypeEnum;
use App\Activity\Properties\CreatedAt;
use App\Activity\Properties\Id;
use App\Activity\Properties\SubjectId;
use App\Activity\Properties\SubjectType;
use App\Activity\Properties\TeamId;
use App\Activity\Properties\Type;
use App\Activity\Properties\UserId;
use App\Project\ProjectVisibility;
use App\Shared\MorphMapKeyEnum;
use App\Shared\Types;
use App\TeamUser\Actions\FindTeamMembers;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * What the team has been doing on tickets and projects, newest first, with
 * filters by kind of change and by person. Sign-ins and other account events
 * stay in the owners' audit pages.
 */
final class ListWorkspaceActivityController
{
    /**
     * The kinds of change each filter shows; "all" is every kind of work.
     *
     * @var array<string, array<int, ActivityTypeEnum>>
     */
    private const array FILTERS = [
        'status' => [ActivityTypeEnum::TicketClosed, ActivityTypeEnum::TicketReopened, ActivityTypeEnum::TicketStatusChanged],
        'comments' => [ActivityTypeEnum::TicketCommented],
        'projects' => [ActivityTypeEnum::ProjectCreated, ActivityTypeEnum::ProjectStatusChanged, ActivityTypeEnum::ProjectArchived, ActivityTypeEnum::ProjectRestored],
        'files' => [ActivityTypeEnum::AttachmentAdded],
    ];

    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $filter = array_key_exists((string) $request->query('filter'), self::FILTERS) ? (string) $request->query('filter') : 'all';
        $types = $filter === 'all' ? ActivityTypeEnum::work() : self::FILTERS[$filter];
        $person = Str::isUuid((string) $request->query('person')) ? (string) $request->query('person') : null;

        return Inertia::render('activity', [
            'activities' => ActivityEloquentModel::query()
                ->with(ActivityRelationship::Actor->value)
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->whereIn(Type::getDatabaseTableColumnName(), array_map(fn (ActivityTypeEnum $type): string => $type->value, $types))
                ->when($person, fn (Builder $query, string $person) => $query->where(UserId::getDatabaseTableColumnName(), $person))
                ->tap(fn (Builder $query) => ProjectVisibility::constrainActivities($query->getQuery(), $user->id))
                ->orderByDesc(CreatedAt::getDatabaseTableColumnName())
                ->orderByDesc(Id::getDatabaseTableColumnName())
                ->paginate(40)
                ->withQueryString()
                ->through(fn (ActivityEloquentModel $model): array => [
                    ...Activity::fromEloquentModel($model)->toListItem(),
                    'url' => match ($model->getAttribute(SubjectType::getDatabaseTableColumnName())) {
                        MorphMapKeyEnum::Ticket->value => route('tickets.index', ['ticket' => Types::string($model->getAttribute(SubjectId::getDatabaseTableColumnName()))]),
                        MorphMapKeyEnum::Project->value => route('projects.show', Types::string($model->getAttribute(SubjectId::getDatabaseTableColumnName()))),
                        default => null,
                    },
                ]),
            'filters' => ['filter' => $filter, 'person' => $person],
            'members' => FindTeamMembers::optionsForTeam($teamId),
        ]);
    }
}
