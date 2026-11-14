<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
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

it('records the rename in the team activity', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $previousName = TeamEloquentModel::query()->findOrFail($user->current_team_id)->name;

    actingAs($user)->patch(route('team.update'), ['name' => 'Acme Sales']);

    $activity = ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TeamRenamed->value)->sole();

    expect($activity->team_id)->toBe($user->current_team_id)
        ->and($activity->user_id)->toBe($user->id)
        // toEqual: MySQL JSON columns do not keep key order.
        ->and($activity->details)->toEqual(['from' => $previousName, 'to' => 'Acme Sales']);
});
