<?php

declare(strict_types=1);

namespace App\Project\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Attachment\Actions\FindAttachment;
use App\Attachment\Attachment;
use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Project\Actions\FindProject;
use App\Project\Exceptions\CouldNotFindProject;
use App\Project\Project;
use App\Project\ProjectFormOptions;
use App\Project\ProjectOverview;
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
        $found = FindProject::withDetailsForTeam($project, $teamId, $user->id);

        if ($found->isNull()) {
            throw CouldNotFindProject::notFound();
        }

        $projectId = $found->getId()->toString();
        $tab = in_array($request->query('view'), self::TABS, true) ? (string) $request->query('view') : 'overview';

        $overview = $tab === 'overview' ? $this->overview($projectId, $teamId, $user->id) : [];
        /** @var list<array{custom_fields: array<string, mixed>}> $milestones */
        $milestones = $overview['milestones'] ?? [];

        return Inertia::render('projects/show', [
            'project' => [
                ...ListProjectsController::listItem($found, FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Project)),
                'group_name' => $found->getGroup()->toNullableData()?->getName()->toString(),
            ],
            'customFieldLabels' => [
                ...CustomFieldRelationLabels::for($teamId, CustomFieldTargetEnum::Project, $found->getCustomFields()->toValues()),
                ...CustomFieldRelationLabels::for($teamId, CustomFieldTargetEnum::Milestone, ...array_column($milestones, 'custom_fields')),
            ],
            'linkedRecords' => FindLinkedRecords::to($teamId, $user->id, CustomFieldRelationEnum::Project, $projectId),
            'tab' => $tab,
            'can' => AccessAbilities::for($user, AccessResourceEnum::Project, $found),
            'milestoneCan' => AccessAbilities::for($user, AccessResourceEnum::Milestone),
            ...match ($tab) {
                'files' => [
                    'attachments' => FindAttachment::forAttachable(MorphMapKeyEnum::Project, $projectId)
                        ->map(fn (Attachment $attachment): array => FindAttachment::listItem($attachment))
                        ->values()
                        ->all(),
                    'attachmentCan' => AccessAbilities::for($user, AccessResourceEnum::Attachment),
                    'favoriteTicketIds' => [],
                    ...TicketDrawer::options($teamId, $user->id),
                    'drawer' => null,
                ],
                'calendar', 'timeline' => [
                    ...($tab === 'calendar' ? TicketCalendar::forRequest($request, $projectId) : TicketTimeline::forRequest($request, $projectId)),
                    'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
                    ...TicketDrawer::options($teamId, $user->id),
                    'drawer' => TicketDrawer::forRequest($request),
                ],
                'overview' => [
                    ...$overview,
                    'favoriteTicketIds' => FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Ticket),
                    ...TicketDrawer::options($teamId, $user->id),
                    'drawer' => TicketDrawer::forRequest($request),
                ],
                default => TicketViewProps::for($request, TicketListFilters::fromRequest($request)->forProject($projectId)),
            },
            ...ProjectFormOptions::forTeam($teamId),
        ]);
    }

    /**
     * The Overview tab's data, with what links to each milestone.
     *
     * @return array<string, mixed>
     */
    private function overview(string $projectId, string $teamId, string $userId): array
    {
        $overview = ProjectOverview::for($projectId);
        /** @var list<array{id: string}> $milestones */
        $milestones = $overview['milestones'];
        $linked = FindLinkedRecords::toMany($teamId, $userId, CustomFieldRelationEnum::Milestone, array_column($milestones, 'id'));

        return [
            ...$overview,
            'milestones' => array_map(fn (array $milestone): array => [...$milestone, 'linked_records' => $linked[$milestone['id']] ?? []], $milestones),
        ];
    }
}
