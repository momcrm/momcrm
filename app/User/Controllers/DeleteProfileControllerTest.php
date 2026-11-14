<?php

declare(strict_types=1);

use App\Team\TeamEloquentModel;
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
