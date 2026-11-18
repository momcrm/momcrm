<?php

declare(strict_types=1);

use App\Team\Team;
use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Gate;

it('lets owners and admins invite people, but not members or outsiders', function (?TeamRoleEnum $role, bool $allowed): void {
    $team = TeamEloquentModel::factory()->create();
    $user = UserEloquentModel::factory()->create();

    if ($role !== null) {
        CreateTeamUser::fromIds(teamId: $team->id, userId: $user->id, role: $role);
    }

    expect(Gate::forUser($user)->allows('invite', Team::fromEloquentModel($team)))->toBe($allowed);
})->with([
    'owner' => [TeamRoleEnum::Owner, true],
    'admin' => [TeamRoleEnum::Admin, true],
    'member' => [TeamRoleEnum::Member, false],
    'not in the team' => [null, false],
]);

it('lets owners and admins manage connectors, but not members or outsiders', function (?TeamRoleEnum $role, bool $allowed): void {
    $team = TeamEloquentModel::factory()->create();
    $user = UserEloquentModel::factory()->create();

    if ($role !== null) {
        CreateTeamUser::fromIds(teamId: $team->id, userId: $user->id, role: $role);
    }

    expect(Gate::forUser($user)->allows('manageConnectors', Team::fromEloquentModel($team)))->toBe($allowed);
})->with([
    'owner' => [TeamRoleEnum::Owner, true],
    'admin' => [TeamRoleEnum::Admin, true],
    'member' => [TeamRoleEnum::Member, false],
    'not in the team' => [null, false],
]);

it('lets owners and admins connect mailboxes, but not members or outsiders', function (?TeamRoleEnum $role, bool $allowed): void {
    $team = TeamEloquentModel::factory()->create();
    $user = UserEloquentModel::factory()->create();

    if ($role !== null) {
        CreateTeamUser::fromIds(teamId: $team->id, userId: $user->id, role: $role);
    }

    expect(Gate::forUser($user)->allows('manageEmail', Team::fromEloquentModel($team)))->toBe($allowed);
})->with([
    'owner' => [TeamRoleEnum::Owner, true],
    'admin' => [TeamRoleEnum::Admin, true],
    'member' => [TeamRoleEnum::Member, false],
    'not in the team' => [null, false],
]);
