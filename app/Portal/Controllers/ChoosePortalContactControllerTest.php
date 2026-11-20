<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Portal\PortalSession;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\withSession;

it('signs in as the chosen customer', function (): void {
    [, $second] = ContactEloquentModel::factory()->withPortalAccess()->count(2)->create(['email' => 'sam@acme.test'])->all();

    withSession([PortalSession::VERIFIED_EMAIL => 'sam@acme.test'])
        ->post(route('portal.choose.store', $second->id))
        ->assertRedirect(route('portal.home'));

    assertAuthenticatedAs($second, 'portal');
});

it("can't choose someone else's contact", function (): void {
    ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'sam@acme.test']);
    $other = ContactEloquentModel::factory()->withPortalAccess()->create(['email' => 'kim@acme.test']);

    withSession([PortalSession::VERIFIED_EMAIL => 'sam@acme.test'])
        ->post(route('portal.choose.store', $other->id))
        ->assertNotFound();

    assertGuest('portal');
});

it('lets a signed-in contact switch to their other customer', function (): void {
    [$first, $second] = ContactEloquentModel::factory()->withPortalAccess()->count(2)->create(['email' => 'sam@acme.test'])->all();

    actingAs($first, 'portal')
        ->post(route('portal.choose.store', $second->id))
        ->assertRedirect(route('portal.home'));

    assertAuthenticatedAs($second, 'portal');
});
