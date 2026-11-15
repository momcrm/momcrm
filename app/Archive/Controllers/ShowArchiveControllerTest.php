<?php

declare(strict_types=1);

use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('lists the team’s archived tickets and projects', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    TicketEloquentModel::factory()->archived()->create([...$team, 'title' => 'Old ticket']);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Live ticket']);
    ProjectEloquentModel::factory()->archived()->create([...$team, 'name' => 'Old project']);
    TicketEloquentModel::factory()->archived()->create();

    actingAs($user)
        ->get(route('archive', ['tab' => 'projects']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('archive')
            ->where('tab', 'projects')
            ->has('tickets', 1)
            ->where('tickets.0.title', 'Old ticket')
            ->where('projects.0.name', 'Old project'),
        );
});

it('tells the page which archived items the person may restore or delete', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    TicketEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)
        ->get(route('archive'))
        ->assertInertia(fn (Assert $page) => $page->where('tickets.0.can.restore', false)->where('tickets.0.can.delete', false));

    actingAs($owner)
        ->get(route('archive'))
        ->assertInertia(fn (Assert $page) => $page->where('tickets.0.can.restore', true)->where('tickets.0.can.delete', true));
});
