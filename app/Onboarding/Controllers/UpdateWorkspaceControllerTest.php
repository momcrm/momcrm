<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('names the workspace and moves on to the starting point', function (): void {
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create(['name' => 'Jane Smith']);

    $response = actingAs($user)->put(route('onboarding.workspace.update'), ['name' => 'Acme IT']);

    $response->assertRedirectToRoute('onboarding.start');
    expect(TeamEloquentModel::query()->findOrFail($user->current_team_id)->name)->toBe('Acme IT');
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TeamRenamed->value)->sole()->details)
        ->toEqual(['from' => "Jane's Team", 'to' => 'Acme IT']);
});

it('records no rename when the name is kept', function (): void {
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create(['name' => 'Jane Smith']);

    actingAs($user)
        ->put(route('onboarding.workspace.update'), ['name' => "Jane's Team"])
        ->assertRedirectToRoute('onboarding.start');

    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TeamRenamed->value)->exists())->toBeFalse();
});

it('requires a name', function (): void {
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create();

    actingAs($user)
        ->put(route('onboarding.workspace.update'), ['name' => ''])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);
});

it('forbids renaming a team the user does not own', function (): void {
    $team = TeamEloquentModel::factory()->create(['name' => 'Their Team']);
    $member = UserEloquentModel::factory()->notOnboarded()->create();
    CreateTeamUser::fromIds(teamId: $team->id, userId: $member->id);
    $member->forceFill(['current_team_id' => $team->id])->save();

    actingAs($member)
        ->put(route('onboarding.workspace.update'), ['name' => 'Mine now'])
        ->assertForbidden();

    expect($team->fresh()->name)->toBe('Their Team');
});
