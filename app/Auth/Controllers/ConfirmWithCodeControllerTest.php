<?php

declare(strict_types=1);

use App\Auth\AuthSession;
use App\EmailCode\Actions\VerifyEmailCode;
use App\EmailCode\EmailCodeEloquentModel;
use App\EmailCode\EmailCodePurposeEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('confirms it is you with a code and opens the protected page', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test', 'password' => null]);
    EmailCodeEloquentModel::factory()->create(['email' => 'sam@acme.test', 'purpose' => EmailCodePurposeEnum::Confirm->value, 'code_hash' => VerifyEmailCode::hash('123456')]);

    actingAs($user)->get(route('security.edit'))->assertRedirect(route('password.confirm'));

    actingAs($user)
        ->post(route('password.confirm.code'), ['code' => '123456'])
        ->assertRedirect(route('security.edit'))
        ->assertSessionHas(AuthSession::PASSWORD_CONFIRMED_AT);

    actingAs($user)->get(route('security.edit'))->assertOk();
});

it('does not accept a sign-in code for confirming', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    EmailCodeEloquentModel::factory()->create(['email' => 'sam@acme.test', 'purpose' => EmailCodePurposeEnum::SignIn->value, 'code_hash' => VerifyEmailCode::hash('123456')]);

    actingAs($user)
        ->post(route('password.confirm.code'), ['code' => '123456'])
        ->assertSessionHasErrors(['code' => 'That code has expired. Send a new one.'])
        ->assertSessionMissing(AuthSession::PASSWORD_CONFIRMED_AT);
});
