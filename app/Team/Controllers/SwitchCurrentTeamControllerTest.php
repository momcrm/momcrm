<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\put;

it('redirects guests to the login page', function (): void {
    $team = TeamEloquentModel::factory()->create();

    put(route('current-team.update'), ['team_id' => $team->id])
        ->assertRedirect(route('login'));
});

it('switches to a team the user belongs to', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $otherTeam = TeamEloquentModel::factory()->create();
    CreateTeamUser::fromIds(teamId: $otherTeam->id, userId: $user->id);

    actingAs($user)
        ->from(route('dashboard'))
        ->put(route('current-team.update'), ['team_id' => $otherTeam->id])
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->current_team_id)->toBe($otherTeam->id);
});

it('returns 404 for a team the user does not belong to', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $personalTeamId = $user->current_team_id;
    $foreignTeam = TeamEloquentModel::factory()->create();

    actingAs($user)
        ->put(route('current-team.update'), ['team_id' => $foreignTeam->id])
        ->assertNotFound();

    expect($user->refresh()->current_team_id)->toBe($personalTeamId);
});

it('requires a team id', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->from(route('dashboard'))
        ->put(route('current-team.update'), [])
        ->assertSessionHasErrors(['team_id' => 'The team id field is required.']);
});

it('records the switch in the new teams activity', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $otherTeam = TeamEloquentModel::factory()->create();
    CreateTeamUser::fromIds(teamId: $otherTeam->id, userId: $user->id);

    actingAs($user)->put(route('current-team.update'), ['team_id' => $otherTeam->id]);

    $activity = ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TeamSwitched->value)->sole();

    expect($activity->team_id)->toBe($otherTeam->id)
        ->and($activity->user_id)->toBe($user->id);
});
