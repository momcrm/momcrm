<?php

declare(strict_types=1);

namespace App\Dashboard\Controllers;

use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceListEnum;
use App\Dashboard\WorkspaceNumbers;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Project\Actions\FindProject;
use App\Project\Controllers\ListProjectsController;
use App\Project\Project;
use App\TeamUser\Actions\FindTeamMembers;
use App\Ticket\TicketStatusCategoryEnum;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The workspace at a glance: headline numbers, every project's progress,
 * tickets by status, each member's workload and the milestones coming up.
 */
final class ShowOverviewController
{
    public function __invoke(Request $request): Response
    {
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $teamId = (string) $user->current_team_id;
        $statusCounts = WorkspaceNumbers::statusCounts($teamId);
        $total = array_sum($statusCounts);
        $closedStatusIds = FindChoice::listForTeam($teamId, ChoiceListEnum::TicketStatus, withArchived: true)
            ->filter(fn (Choice $status): bool => $status->hasCategory(TicketStatusCategoryEnum::Closed))
            ->ids();

        return Inertia::render('overview', [
            'stats' => [
                ...WorkspaceNumbers::headline($teamId),
                'tickets' => $total,
                'completion_rate' => $total === 0 ? 0 : (int) round(array_sum(array_intersect_key($statusCounts, array_flip($closedStatusIds))) / $total * 100),
                'members' => FindTeamMembers::optionsForTeam($teamId)->count(),
            ],
            'portfolio' => FindProject::listForTeam($teamId, $user->id, sort: 'due_on')
                ->map(fn (Project $project): array => ListProjectsController::listItem(
                    $project,
                    FindFavorite::idsForUser($teamId, $user->id, FavoritableTypeEnum::Project),
                ))
                ->values(),
            'statusCounts' => $statusCounts,
            'workload' => WorkspaceNumbers::workload($teamId),
            'milestones' => WorkspaceNumbers::upcomingMilestones($teamId),
        ]);
    }
}
