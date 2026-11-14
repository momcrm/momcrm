<?php

declare(strict_types=1);

use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamUserEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('falls back to the first team when the current team is missing', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $personalTeamId = $user->current_team_id;
    $user->forceFill(['current_team_id' => null])->save();

    actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->refresh()->current_team_id)->toBe($personalTeamId);
});

it('falls back to the first team when the user was removed from their current team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $personalTeamId = $user->current_team_id;
    $formerTeam = TeamEloquentModel::factory()->create();
    $membership = CreateTeamUser::fromIds(teamId: $formerTeam->id, userId: $user->id);
    $user->forceFill(['current_team_id' => $formerTeam->id])->save();
    TeamUserEloquentModel::query()->whereKey($membership->getId()->toString())->delete();

    actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->refresh()->current_team_id)->toBe($personalTeamId);
});
