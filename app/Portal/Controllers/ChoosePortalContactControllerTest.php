<?php

declare(strict_types=1);

use App\Auth\AuthSession;
use App\Contact\ContactEloquentModel;
use App\Portal\PortalSession;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\withSession;

it('signs in as the chosen customer', function (): void {
    [, $second] = ContactEloquentModel::factory()->withPortalAccess()->count(2)->create(['email' => 'sam@acme.test'])->all();

    withSession([AuthSession::PROVEN_EMAIL => 'sam@acme.test'])
        ->post(route('portal.choose.store', $second->id))
        ->assertRedirect(route('home'));

    assertAuthenticatedAs($second, 'portal');
});

it("can't choose someone else's contact", function (): void {
    ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);
    $other = ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'kim@acme.test']);

    withSession([AuthSession::PROVEN_EMAIL => 'sam@acme.test'])
        ->post(route('portal.choose.store', $other->id))
        ->assertNotFound();

    assertGuest('portal');
});

it('lets a signed-in contact switch to their other customer', function (): void {
    [$first, $second] = ContactEloquentModel::factory()->withPortalAccess()->count(2)->create(['email' => 'sam@acme.test'])->all();

    actingAs($first, 'portal')
        ->withSession([AuthSession::PROVEN_EMAIL => 'sam@acme.test'])
        ->post(route('portal.choose.store', $second->id))
        ->assertRedirect(route('home'));

    assertAuthenticatedAs($second, 'portal');
});

it('signs staff out when they choose a customer portal', function (): void {
    $staff = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);

    actingAs($staff)
        ->post(route('portal.choose.store', $contact->id))
        ->assertRedirect(route('home'));

    assertAuthenticatedAs($contact, 'portal');
    assertGuest('web');
});

it("doesn't let staff viewing as a contact switch to the contact's other customers", function (): void {
    $staff = UserEloquentModel::factory()->withPersonalTeam()->create();
    [$viewed, $elsewhere] = ContactEloquentModel::factory()->withPortalAccess()->count(2)->create(['email' => 'sam@acme.test'])->all();

    actingAs($staff)
        ->actingAs($viewed, 'portal')
        ->withSession([PortalSession::VIEWING_AS => $staff->id, AuthSession::PROVEN_EMAIL => 'sam@acme.test'])
        ->post(route('portal.choose.store', $elsewhere->id))
        ->assertNotFound();

    assertAuthenticatedAs($viewed, 'portal');
});
