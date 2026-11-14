<?php

declare(strict_types=1);

use App\Auth\AuthSession;
use App\EmailCode\Actions\VerifyEmailCode;
use App\EmailCode\EmailCodeEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\withSession;

function emailCodeFor(string $email, string $code = '123456', array $attributes = []): EmailCodeEloquentModel
{
    return EmailCodeEloquentModel::factory()->create(['email' => $email, 'code_hash' => VerifyEmailCode::hash($code), ...$attributes]);
}

it('logs an existing account in', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    emailCodeFor('sam@acme.test');

    withSession([AuthSession::PENDING_EMAIL => 'sam@acme.test'])
        ->post(route('auth.code.verify'), ['code' => '123456'])
        ->assertRedirect(route('dashboard'));

    assertAuthenticatedAs($user);
});

it('sends a two-factor account to the two-factor challenge', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->withTwoFactor()->create(['email' => 'sam@acme.test']);
    emailCodeFor('sam@acme.test');

    withSession([AuthSession::PENDING_EMAIL => 'sam@acme.test'])
        ->post(route('auth.code.verify'), ['code' => '123456'])
        ->assertRedirectToRoute('two-factor.login')
        ->assertSessionHas('login.id', $user->id);

    assertGuest();
});

it('moves a new address on to "What should we call you?"', function (): void {
    emailCodeFor('new@acme.test');

    withSession([AuthSession::PENDING_EMAIL => 'new@acme.test'])
        ->post(route('auth.code.verify'), ['code' => '123456'])
        ->assertRedirectToRoute('signup.profile')
        ->assertSessionHas(AuthSession::VERIFIED_EMAIL, 'new@acme.test');

    assertGuest();
});

it('rejects a wrong code and counts the try', function (): void {
    $emailCode = emailCodeFor('sam@acme.test');

    withSession([AuthSession::PENDING_EMAIL => 'sam@acme.test'])
        ->post(route('auth.code.verify'), ['code' => '654321'])
        ->assertSessionHasErrors(['code' => "That code isn't right. Check the email and try again."]);

    expect($emailCode->fresh()->attempts)->toBe(1);
    assertGuest();
});

it('rejects an expired code', function (): void {
    emailCodeFor('sam@acme.test', attributes: ['expires_at' => now()->subMinute()]);

    withSession([AuthSession::PENDING_EMAIL => 'sam@acme.test'])
        ->post(route('auth.code.verify'), ['code' => '123456'])
        ->assertSessionHasErrors(['code' => 'That code has expired. Send a new one.']);
});

it('stops accepting a code after five wrong tries, even the right one', function (): void {
    emailCodeFor('sam@acme.test', attributes: ['attempts' => 5]);

    withSession([AuthSession::PENDING_EMAIL => 'sam@acme.test'])
        ->post(route('auth.code.verify'), ['code' => '123456'])
        ->assertSessionHasErrors(['code' => 'Too many wrong tries. Send a new code.']);

    assertGuest();
});

it('only accepts a code once', function (): void {
    UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    emailCodeFor('sam@acme.test', attributes: ['consumed_at' => now()]);

    withSession([AuthSession::PENDING_EMAIL => 'sam@acme.test'])
        ->post(route('auth.code.verify'), ['code' => '123456'])
        ->assertSessionHasErrors(['code' => 'That code has expired. Send a new one.']);
});
