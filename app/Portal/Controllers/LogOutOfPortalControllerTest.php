<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;

it('signs out of the portal but not out of the app', function (): void {
    $staff = UserEloquentModel::factory()->withPersonalTeam()->create();
    $contact = ContactEloquentModel::factory()->withPortalAccess()->create();

    actingAs($staff)
        ->actingAs($contact, 'portal')
        ->post(route('portal.logout'))
        ->assertRedirectToRoute('portal.login');

    assertGuest('portal');
    assertAuthenticatedAs($staff, 'web');
});
