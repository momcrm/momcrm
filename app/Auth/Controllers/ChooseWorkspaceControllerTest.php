<?php

declare(strict_types=1);

use App\Auth\AuthSession;
use App\Contact\ContactEloquentModel;
use App\Portal\PortalSession;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

it('signs in as staff for a proven address and out of the portal', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);

    actingAs($contact, 'portal')
        ->withSession([AuthSession::PROVEN_EMAIL => 'sam@acme.test'])
        ->post(route('choose.workspace'))
        ->assertRedirect(route('dashboard'));

    assertAuthenticatedAs($user, 'web');
    assertGuest('portal');
});

it('needs a proven address', function (): void {
    UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);

    post(route('choose.workspace'))->assertNotFound();

    assertGuest('web');
});

it("won't sign a contact in as staff by the contact's address on record", function (): void {
    UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);

    actingAs($contact, 'portal')
        ->withSession([AuthSession::PROVEN_EMAIL => 'kim@acme.test'])
        ->post(route('choose.workspace'))
        ->assertNotFound();

    assertGuest('web');
});

it("won't sign staff viewing as a contact in as someone else", function (): void {
    $staff = UserEloquentModel::factory()->withPersonalTeam()->create();
    UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);

    withSession([PortalSession::VIEWING_AS => $staff->id, AuthSession::PROVEN_EMAIL => 'sam@acme.test'])
        ->actingAs($staff)
        ->actingAs($contact, 'portal')
        ->post(route('choose.workspace'))
        ->assertNotFound();

    assertAuthenticatedAs($staff, 'web');
});
