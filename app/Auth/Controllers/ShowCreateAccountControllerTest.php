<?php

declare(strict_types=1);

use App\Auth\AuthSession;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

it('asks the name of a person whose email is proven', function (): void {
    withSession([AuthSession::VERIFIED_EMAIL => 'sam@acme.test', AuthSession::SUGGESTED_NAME => ['first_name' => 'Sam', 'last_name' => 'Lee']])
        ->get(route('signup.profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/create-account')
            ->where('email', 'sam@acme.test')
            ->where('suggestedName', ['first_name' => 'Sam', 'last_name' => 'Lee']),
        );
});

it('starts over without a proven email', function (): void {
    get(route('signup.profile'))->assertRedirectToRoute('signup');
});
