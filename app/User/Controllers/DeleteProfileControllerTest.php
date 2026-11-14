<?php

declare(strict_types=1);

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\AuditLog\AuditLogEloquentModel;
use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\assertModelMissing;

it('deletes the account and logs the user out', function (): void {
    $user = UserEloquentModel::factory()->create();

    actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    assertGuest();
    expect($user->fresh())->toBeNull();
});

it('deletes the teams the user owns', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $personalTeam = TeamEloquentModel::query()->findOrFail($user->current_team_id);

    actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors();

    assertModelMissing($personalTeam);
});

it('requires the correct password to delete the account', function (): void {
    $user = UserEloquentModel::factory()->create();

    actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});

it('removes the users account activity and audit rows but keeps team history', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $sharedTeam = TeamEloquentModel::factory()->create();
    CreateTeamUser::fromIds(teamId: $sharedTeam->id, userId: $user->id);
    RecordActivity::for(type: ActivityTypeEnum::SignedIn, userId: $user->id);
    $teamActivity = RecordActivity::for(type: ActivityTypeEnum::TeamSwitched, userId: $user->id, teamId: $sharedTeam->id);

    actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors();

    expect(ActivityEloquentModel::query()->whereNull('team_id')->exists())->toBeFalse()
        ->and(AuditLogEloquentModel::query()->where('auditable_type', 'user')->where('auditable_id', $user->id)->exists())->toBeFalse()
        ->and(ActivityEloquentModel::query()->findOrFail($teamActivity->getId()->toString())->user_id)->toBeNull();
});
