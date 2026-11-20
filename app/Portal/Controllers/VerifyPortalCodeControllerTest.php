<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\EmailCode\Actions\VerifyEmailCode;
use App\EmailCode\EmailCodeEloquentModel;
use App\Portal\PortalSession;
use App\User\UserEloquentModel;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\withSession;

function portalCodeFor(string $email, string $code = '123456'): EmailCodeEloquentModel
{
    return EmailCodeEloquentModel::factory()->create(['email' => $email, 'purpose' => 'portal_sign_in', 'code_hash' => VerifyEmailCode::hash($code)]);
}

it('signs the only portal contact for the address in', function (): void {
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);
    portalCodeFor('sam@acme.test');

    withSession([PortalSession::PENDING_EMAIL => 'sam@acme.test'])
        ->post(route('portal.login.code.verify'), ['code' => '123456'])
        ->assertRedirect(route('portal.home'));

    assertAuthenticatedAs($contact, 'portal');
    assertGuest('web');
});

it('asks which customer when the address is a portal contact at several', function (): void {
    ContactEloquentModel::factory()->withPortalAccess()->count(2)->create(['email' => 'sam@acme.test']);
    portalCodeFor('sam@acme.test');

    withSession([PortalSession::PENDING_EMAIL => 'sam@acme.test'])
        ->post(route('portal.login.code.verify'), ['code' => '123456'])
        ->assertRedirectToRoute('portal.choose')
        ->assertSessionHas(PortalSession::VERIFIED_EMAIL, 'sam@acme.test');

    assertGuest('portal');
});

it("doesn't accept a staff sign-in code", function (): void {
    ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);
    EmailCodeEloquentModel::factory()->create(['email' => 'sam@acme.test', 'code_hash' => VerifyEmailCode::hash('123456')]);

    withSession([PortalSession::PENDING_EMAIL => 'sam@acme.test'])
        ->post(route('portal.login.code.verify'), ['code' => '123456'])
        ->assertSessionHasErrors('code');

    assertGuest('portal');
});

it('never signs a contact in as staff, even with a staff account for the address', function (): void {
    UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);
    portalCodeFor('sam@acme.test');

    withSession([PortalSession::PENDING_EMAIL => 'sam@acme.test'])
        ->post(route('portal.login.code.verify'), ['code' => '123456']);

    assertGuest('web');
});
