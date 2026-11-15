<?php

declare(strict_types=1);

use App\Milestone\MilestoneEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('sums up the workspace', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Ada']);
    $team = ['team_id' => $user->current_team_id];
    $project = ProjectEloquentModel::factory()->create([...$team, 'name' => 'Refit']);
    [$new, $inProgress, $closed] = array_map(fn (string $name): string => ticketStatusId($user->current_team_id, $name), ['New', 'In progress', 'Closed']);
    TicketEloquentModel::factory()->create([...$team, 'status_id' => $closed]);
    TicketEloquentModel::factory()->create([...$team, 'assignee_id' => $user->id, 'status_id' => $inProgress]);
    TicketEloquentModel::factory()->create([...$team, 'assignee_id' => $user->id]);
    TicketEloquentModel::factory()->create([...$team, 'assignee_id' => $user->id, 'parent_id' => TicketEloquentModel::factory()->create($team)->id]);
    MilestoneEloquentModel::factory()->create(['project_id' => $project->id, 'name' => 'Go live', 'due_on' => now()->addWeek()->toDateString()]);
    MilestoneEloquentModel::factory()->create(['project_id' => $project->id, 'completed_at' => now(), 'due_on' => now()->addWeek()->toDateString()]);

    actingAs($user)
        ->get(route('overview'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('overview')
            ->where('stats.tickets', 4)
            ->where('stats.completion_rate', 25)
            ->where('stats.members', 1)
            ->where('portfolio.0.name', 'Refit')
            ->where('statusCounts', fn ($counts): bool => collect($counts)->sortKeys()->all() === collect([$new => 2, $inProgress => 1, $closed => 1])->sortKeys()->all())
            ->where('workload.0.name', 'Ada')
            ->where('workload.0.total', 2)
            ->where('workload.0.statuses', fn ($statuses): bool => collect($statuses)->sortKeys()->all() === collect([$new => 1, $inProgress => 1])->sortKeys()->all())
            ->has('milestones', 1)
            ->where('milestones.0.project_name', 'Refit'),
        );
});
