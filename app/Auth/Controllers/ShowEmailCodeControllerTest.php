<?php

declare(strict_types=1);

use App\Auth\AuthSession;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

it('asks for the code emailed to the address', function (): void {
    withSession([AuthSession::PENDING_EMAIL => 'sam@acme.test'])
        ->get(route('auth.code'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/code')->where('email', 'sam@acme.test')->where('minutesValid', 10));
});

it('starts over when no code was sent', function (): void {
    get(route('auth.code'))->assertRedirectToRoute('signup');
});
