<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Portal\PortalSession;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;

it('signs a staff member viewing as a contact out of the portal but not out of the app', function (): void {
    $staff = UserEloquentModel::factory()->withPersonalTeam()->create();
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create();

    actingAs($staff)
        ->actingAs($contact, 'portal')
        ->withSession([PortalSession::VIEWING_AS => $staff->id])
        ->post(route('portal.logout'))
        ->assertRedirectToRoute('login');

    assertGuest('portal');
    assertAuthenticatedAs($staff, 'web');
});
