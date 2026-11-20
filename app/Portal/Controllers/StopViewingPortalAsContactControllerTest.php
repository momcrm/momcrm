<?php

declare(strict_types=1);

use App\Portal\PortalSession;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;

it('leaves the portal and goes back to the contact in the app', function (): void {
    $contact = portalContact();
    $owner = UserEloquentModel::query()->where('current_team_id', $contact->team_id)->sole();

    actingAs($owner)
        ->actingAs($contact, 'portal')
        ->withSession([PortalSession::VIEWING_AS => $owner->id])
        ->post(route('portal.view-as.stop'))
        ->assertRedirectToRoute('contacts.edit', $contact->id)
        ->assertSessionMissing(PortalSession::VIEWING_AS);

    assertGuest('portal');
    assertAuthenticatedAs($owner, 'web');
});
