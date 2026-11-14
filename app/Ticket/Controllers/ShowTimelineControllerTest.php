<?php

declare(strict_types=1);

use App\Milestone\MilestoneEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows eight weeks of dated tickets that overlap the window, with projects and milestones', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $project = ProjectEloquentModel::factory()->create([...$team, 'name' => 'Refit', 'starts_on' => '2026-12-01', 'due_on' => '2026-12-31']);
    TicketEloquentModel::factory()->create([...$team, 'project_id' => $project->id, 'title' => 'Spans in', 'starts_on' => '2026-11-20', 'due_on' => '2026-12-09']);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'One day', 'due_on' => '2026-12-20']);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Before', 'starts_on' => '2026-10-01', 'due_on' => '2026-10-05']);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Undated']);
    MilestoneEloquentModel::factory()->create(['project_id' => $project->id, 'name' => 'Go live', 'due_on' => '2026-12-18']);
    $hidden = ProjectEloquentModel::factory()->create([...$team, 'visibility' => 'restricted']);
    TicketEloquentModel::factory()->create([...$team, 'project_id' => $hidden->id, 'title' => 'Secret', 'due_on' => '2026-12-10']);

    actingAs($user)
        ->get(route('timeline', ['start' => '2026-12-02']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('timeline')
            ->where('timeline.zoom', 'weeks')
            ->where('timeline.from', '2026-11-30')
            ->where('timeline.to', '2027-01-24')
            ->where('tickets', fn ($tickets): bool => collect($tickets)->pluck('title')->all() === ['Spans in', 'One day'])
            ->where('projects.0.name', 'Refit')
            ->where('projects.0.due_on', '2026-12-31')
            ->where('milestones.0.name', 'Go live'),
        );
});

it('zooms out to six months', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('timeline', ['zoom' => 'months', 'start' => '2026-12-15']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timeline.from', '2026-12-01')
            ->where('timeline.to', '2027-05-31'),
        );
});

it("shows a project's timeline on its page", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'project_id' => $project->id, 'due_on' => now()->toDateString()]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'due_on' => now()->toDateString()]);

    actingAs($user)
        ->get(route('projects.show', ['project' => $project->id, 'view' => 'timeline']))
        ->assertInertia(fn (Assert $page) => $page->where('tab', 'timeline')->has('tickets', 1)->has('projects', 1));
});
