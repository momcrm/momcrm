<?php

declare(strict_types=1);

namespace App\Project\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Attachment\Actions\FindAttachment;
use App\Attachment\AttachmentEloquentModel;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Group\GroupEloquentModel;
use App\Project\Actions\FindProject;
use App\Project\Exceptions\CouldNotFindProject;
use App\Project\Project;
use App\Project\ProjectFormOptions;
use App\Project\ProjectOverview;
use App\Project\ProjectRelationship;
use App\Shared\MorphMapKeyEnum;
use App\Ticket\TicketCalendar;
use App\Ticket\TicketDrawer;
use App\Ticket\TicketListFilters;
use App\Ticket\TicketTimeline;
use App\Ticket\TicketViewProps;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A project's page. The Overview tab sums it up; the List, Board and Table
 * tabs are the ticket views limited to the project, Calendar and Timeline
 * plan its dates, and Files lists its uploads.
 */
final class ShowProjectController
{
    public const array TABS = ['overview', TicketListFilters::VIEW_LIST, TicketListFilters::VIEW_BOARD, TicketListFilters::VIEW_TABLE, 'calendar', 'timeline', 'files'];

    /**
     * @throws CouldNotFindProject
     */
    public function __invoke(Request $request, string $project): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $model = FindProject::withDetailsForTeam($project, $teamId, $user->id);

        if ($model === null) {
            throw CouldNotFindProject::notFound();
        }

        $tab = in_array($request->query('view'), self::TABS, true) ? (string) $request->query('view') : 'overview';
        $group = $model->getRelationValue(ProjectRelationship::Group->value);

        return Inertia::render('projects/show', [
            'project' => [
                ...ListProjectsController::listItem($model, FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Project)),
                'group_name' => $group instanceof GroupEloquentModel ? $group->name : null,
            ],
            'tab' => $tab,
            'can' => AccessAbilities::for($user, AccessResourceEnum::Project, Project::fromEloquentModel($model)),
            'milestoneCan' => AccessAbilities::for($user, AccessResourceEnum::Milestone),
            ...match ($tab) {
                'files' => [
                    'attachments' => FindAttachment::forAttachable(MorphMapKeyEnum::Project, $model->id)
                        ->map(fn (AttachmentEloquentModel $attachment): array => FindAttachment::listItem($attachment))
                        ->values()
                        ->all(),
                    'attachmentCan' => AccessAbilities::for($user, AccessResourceEnum::Attachment),
                    'favoriteTicketIds' => [],
                    ...TicketDrawer::options($teamId, $user->id),
                    'drawer' => null,
                ],
                'calendar', 'timeline' => [
                    ...($tab === 'calendar' ? TicketCalendar::forRequest($request, $model->id) : TicketTimeline::forRequest($request, $model->id)),
                    'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
                    ...TicketDrawer::options($teamId, $user->id),
                    'drawer' => TicketDrawer::forRequest($request),
                ],
                'overview' => [
                    ...ProjectOverview::for($model->id),
                    'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
                    ...TicketDrawer::options($teamId, $user->id),
                    'drawer' => TicketDrawer::forRequest($request),
                ],
                default => TicketViewProps::for($request, TicketListFilters::fromRequest($request)->forProject($model->id)),
            },
            ...ProjectFormOptions::forTeam($teamId),
        ]);
    }
}
