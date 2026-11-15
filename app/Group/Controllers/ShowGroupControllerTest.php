<?php

declare(strict_types=1);

use App\Group\GroupEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows a group's projects, their open tickets and its members", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $group = GroupEloquentModel::factory()->create([...$team, 'name' => 'Support']);
    $group->members()->attach($user->id, ['role' => 'lead']);
    $project = ProjectEloquentModel::factory()->create([...$team, 'group_id' => $group->id, 'name' => 'Refit']);
    ProjectEloquentModel::factory()->create($team);
    TicketEloquentModel::factory()->create([...$team, 'project_id' => $project->id, 'title' => 'Open one']);
    TicketEloquentModel::factory()->closed()->create([...$team, 'project_id' => $project->id]);

    actingAs($user)
        ->get(route('groups.show', $group->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('groups/show')
            ->where('group.name', 'Support')
            ->where('members.0.role', 'lead')
            ->has('projects', 1)
            ->where('projects.0.name', 'Refit')
            ->has('tickets', 1)
            ->where('tickets.0.title', 'Open one'),
        );
});

it("returns not found for another team's group", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('groups.show', GroupEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
