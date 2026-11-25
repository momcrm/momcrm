<?php

declare(strict_types=1);

use App\Team\SuspendedTeamAction;
use App\Team\Team;
use App\Team\TeamEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;

// Start with nothing on offer, whatever packages this installation has: one
// that suspends teams would otherwise answer in place of the tests.
beforeEach(fn () => app()->instance(SuspendedTeamAction::class, new SuspendedTeamAction));

it('shares what a member can do about their suspended team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    TeamEloquentModel::query()->whereKey($owner->current_team_id)->update(['suspended_at' => now()]);
    resolve(SuspendedTeamAction::class)->using(fn (Request $request, Team $team): array => [
        'label' => 'Subscribe',
        'href' => '/settings/subscription?team='.$team->getId()->toString(),
    ]);

    actingAs($owner)
        ->get(route('team.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('suspendedTeamAction', [
            'label' => 'Subscribe',
            'href' => '/settings/subscription?team='.$owner->current_team_id,
        ]));
});

it('shares nothing for a team that is not suspended', function (): void {
    resolve(SuspendedTeamAction::class)->using(fn (Request $request, Team $team): array => ['label' => 'Subscribe', 'href' => '/settings/subscription']);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('team.edit'))
        ->assertInertia(fn ($page) => $page->where('suspendedTeamAction', null));
});

it('shares nothing for a suspended team when nothing can be done about it', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    TeamEloquentModel::query()->whereKey($owner->current_team_id)->update(['suspended_at' => now()]);

    actingAs($owner)
        ->get(route('team.edit'))
        ->assertInertia(fn ($page) => $page->where('suspendedTeamAction', null));
});
