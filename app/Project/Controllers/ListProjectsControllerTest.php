<?php

declare(strict_types=1);

use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('lists the team’s live projects with their progress', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Refit']);
    TicketEloquentModel::factory()->closed()->create(['team_id' => $user->current_team_id, 'project_id' => $project->id]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'project_id' => $project->id, 'due_on' => now()->subDay()->toDateString()]);
    ProjectEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id]);
    ProjectEloquentModel::factory()->create();

    actingAs($user)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/index')
            ->has('projects', 1)
            ->where('projects.0.name', 'Refit')
            ->where('projects.0.tickets_count', 2)
            ->where('projects.0.closed_tickets_count', 1)
            ->where('projects.0.overdue_tickets_count', 1)
            ->has('projectOptions.members'),
        );
});

it('hides restricted projects from people who are not members', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $hidden = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'visibility' => 'restricted', 'name' => 'Hidden']);
    $mine = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'visibility' => 'restricted', 'name' => 'Mine']);
    $mine->members()->attach($user->id);

    actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page->has('projects', 1)->where('projects.0.name', 'Mine'));

    actingAs($user)->get(route('projects.show', $hidden->id))->assertNotFound();
});

it('searches, filters by status and sorts', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    ProjectEloquentModel::factory()->create([...$team, 'name' => 'Alpha refit', 'status' => 'in_progress', 'due_on' => '2026-12-20']);
    ProjectEloquentModel::factory()->create([...$team, 'name' => 'Beta refit', 'status' => 'in_progress', 'due_on' => '2026-12-01']);
    ProjectEloquentModel::factory()->create([...$team, 'name' => 'Gamma refit', 'status' => 'completed']);

    actingAs($user)
        ->get(route('projects.index', ['search' => 'refit', 'status' => 'in_progress', 'sort' => 'due_on']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 2)
            ->where('projects.0.name', 'Beta refit')
            ->where('filters.status', 'in_progress'),
        );
});
