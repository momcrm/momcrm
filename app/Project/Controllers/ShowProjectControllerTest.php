<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Milestone\MilestoneEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('sums up a project on its overview', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Refit']);
    $team = ['team_id' => $user->current_team_id, 'project_id' => $project->id];
    TicketEloquentModel::factory()->create([...$team, 'status' => 'closed']);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Due soon', 'due_on' => now()->addDay()->toDateString()]);
    MilestoneEloquentModel::factory()->create(['project_id' => $project->id, 'name' => 'Go live']);

    actingAs($user)
        ->get(route('projects.show', $project->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')
            ->where('tab', 'overview')
            ->where('project.name', 'Refit')
            ->where('statusCounts', ['closed' => 1, 'new' => 1])
            ->where('upcoming.0.title', 'Due soon')
            ->where('milestones.0.name', 'Go live')
            ->has('options.labels'),
        );
});

it('limits the ticket tabs to the project', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'project_id' => $project->id, 'title' => 'In project']);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'title' => 'Elsewhere']);

    actingAs($user)
        ->get(route('projects.show', ['project' => $project->id, 'view' => 'board']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tab', 'board')
            ->where('filters.project', $project->id)
            ->where('tickets.total', 1)
            ->where('tickets.data.0.title', 'In project'),
        );
});

it("returns not found for another team's project", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('projects.show', ProjectEloquentModel::factory()->create()->id))
        ->assertNotFound();
});

it("lists a project's files", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    AttachmentEloquentModel::factory()->on($project)->create(['name' => 'plan.pdf']);

    actingAs($user)
        ->get(route('projects.show', ['project' => $project->id, 'view' => 'files']))
        ->assertInertia(fn (Assert $page) => $page->where('tab', 'files')->where('attachments.0.name', 'plan.pdf'));
});
