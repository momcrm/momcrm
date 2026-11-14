<?php

declare(strict_types=1);

use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows the email-first sign-up page, prefilled from the link', function (): void {
    get(route('register', ['email' => 'sam@acme.test']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/continue')
            ->where('mode', 'sign_up')
            ->where('email', 'sam@acme.test')
            ->where('socialProviders', []),
        );
});

it('shows the same page for logging in', function (): void {
    get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/continue')->where('mode', 'log_in'));
});

it('offers Google and Microsoft only when their keys are set', function (): void {
    config([
        'services.google.client_id' => 'google-id',
        'services.google.client_secret' => 'google-secret',
    ]);

    get(route('register'))->assertInertia(fn (Assert $page) => $page
        ->where('socialProviders', [['value' => 'google', 'label' => 'Google']]));
});

it('sends people who are already signed in to their dashboard', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('register'))
        ->assertRedirect(route('dashboard'));
});
