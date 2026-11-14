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
    TicketEloquentModel::factory()->create([...$team, 'status' => 'closed']);
    TicketEloquentModel::factory()->create([...$team, 'assignee_id' => $user->id, 'status' => 'in_progress']);
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
            ->where('statusCounts', ['closed' => 1, 'in_progress' => 1, 'new' => 2])
            ->where('workload.0.name', 'Ada')
            ->where('workload.0.total', 2)
            ->where('workload.0.statuses', ['in_progress' => 1, 'new' => 1])
            ->has('milestones', 1)
            ->where('milestones.0.project_name', 'Refit'),
        );
});
