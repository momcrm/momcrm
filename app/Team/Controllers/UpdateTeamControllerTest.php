<?php

declare(strict_types=1);

use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('lets the owner rename their current team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->patch(route('team.update'), ['name' => 'Acme Sales'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('team.edit'));

    expect(TeamEloquentModel::query()->findOrFail($user->current_team_id)->name)->toBe('Acme Sales');
});

it('requires a team name', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->from(route('team.edit'))
        ->patch(route('team.update'), ['name' => ''])
        ->assertSessionHasErrors(['name' => 'The name field is required.'])
        ->assertRedirect(route('team.edit'));
});

it('forbids members who do not own the team from renaming it', function (): void {
    $team = TeamEloquentModel::factory()->create(['name' => 'Original Name']);
    $member = UserEloquentModel::factory()->create();
    CreateTeamUser::fromIds(teamId: $team->id, userId: $member->id);
    $member->forceFill(['current_team_id' => $team->id])->save();

    actingAs($member)
        ->patch(route('team.update'), ['name' => 'Hijacked'])
        ->assertForbidden();

    expect($team->refresh()->name)->toBe('Original Name');
});
