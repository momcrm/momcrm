<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

it('updates the password', function (): void {
    $user = UserEloquentModel::factory()->create();

    actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

it('requires the correct current password', function (): void {
    actingAs(UserEloquentModel::factory()->create())
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('security.edit'));
});

it('records the password change in the account activity', function (): void {
    $user = UserEloquentModel::factory()->create();

    actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    expect(ActivityEloquentModel::query()->where('user_id', $user->id)->pluck('type')->all())
        ->toBe([ActivityTypeEnum::PasswordChanged->value]);
});

it('lets someone who signs in with codes set a first password without a current one', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['password' => null]);

    actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'password' => 'a-long-passphrase',
            'password_confirmation' => 'a-long-passphrase',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('a-long-passphrase', (string) $user->fresh()->password))->toBeTrue();
});

it('still needs the current password to change an existing one', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'password' => 'a-long-passphrase',
            'password_confirmation' => 'a-long-passphrase',
        ])
        ->assertSessionHasErrors(['current_password' => 'The current password field is required.']);
});
