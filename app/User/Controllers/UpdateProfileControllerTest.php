<?php

declare(strict_types=1);

use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\post;

it('updates the name and email and clears email verification', function (): void {
    $user = UserEloquentModel::factory()->create();

    actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

it('stores the email in lowercase so the user can still log in', function (): void {
    $user = UserEloquentModel::factory()->create();

    actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'Test@Example.com',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email)->toBe('test@example.com');

    post(route('logout'));

    post(route('login.store'), [
        'email' => 'Test@Example.com',
        'password' => 'password',
    ]);

    assertAuthenticatedAs($user);
});

it('rejects an email already taken regardless of case', function (): void {
    UserEloquentModel::factory()->create(['email' => 'taken@example.com']);

    actingAs(UserEloquentModel::factory()->create())
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'Taken@Example.com',
        ])
        ->assertSessionHasErrors('email')
        ->assertRedirect(route('profile.edit'));
});

it('keeps the email verified when the email address is unchanged', function (): void {
    $user = UserEloquentModel::factory()->create();

    actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});
