<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
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

it('records which profile fields changed, without their values', function (): void {
    $user = UserEloquentModel::factory()->create();

    actingAs($user)->patch(route('profile.update'), [
        'name' => 'Brand New Name',
        'email' => $user->email,
    ]);

    $activity = ActivityEloquentModel::query()->where('type', ActivityTypeEnum::ProfileUpdated->value)->sole();

    expect($activity->details)->toBe(['fields' => ['name']]);
});

it('records nothing when the profile is saved unchanged', function (): void {
    $user = UserEloquentModel::factory()->create();

    actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
    ]);

    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::ProfileUpdated->value)->exists())->toBeFalse();
});

it('saves a job title and timezone, and clears them when left empty', function (): void {
    $user = UserEloquentModel::factory()->create();
    $profile = ['name' => $user->name, 'email' => $user->email];

    actingAs($user)
        ->patch(route('profile.update'), [...$profile, 'job_title' => '  Head of Support ', 'timezone' => 'Australia/Perth'])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->only(['job_title', 'timezone']))->toBe(['job_title' => 'Head of Support', 'timezone' => 'Australia/Perth']);

    actingAs($user)
        ->patch(route('profile.update'), [...$profile, 'job_title' => '', 'timezone' => ''])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->only(['job_title', 'timezone']))->toBe(['job_title' => null, 'timezone' => null]);
});

it('keeps the job title and timezone when a save leaves them out', function (): void {
    $user = UserEloquentModel::factory()->create(['job_title' => 'Designer', 'timezone' => 'Europe/London']);

    actingAs($user)
        ->patch(route('profile.update'), ['name' => 'New Name', 'email' => $user->email])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->only(['job_title', 'timezone']))->toBe(['job_title' => 'Designer', 'timezone' => 'Europe/London']);
});

it('rejects a timezone that does not exist', function (): void {
    $user = UserEloquentModel::factory()->create();

    actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => $user->email, 'timezone' => 'Mars/Olympus'])
        ->assertSessionHasErrors('timezone');

    expect($user->refresh()->timezone)->toBeNull();
});
